<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Fascicolo Probante - {{ $student->full_name }}</title>
    <style>
        @page { margin: 32px 38px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1e293b; line-height: 1.45; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 13pt; font-weight: bold; color: #0f172a; }
        .section-title { font-size: 10.5pt; font-weight: bold; background: #f1f5f9; padding: 6px 10px; margin: 16px 0 8px 0; border-left: 4px solid #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 8.5pt; }
        th { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">DOCUMENTAZIONE PROBANTE DI FREQUENZA TELEMATICA (Art. 6 D.M. 26 giugno 2024, n. 88)</div>
        <div style="font-size: 9pt; color: #475569; margin-top: 4px;">
            Ente di Formazione: <strong>{{ $settings['provider_name'] }}</strong> • Provvedimento Aut.: <strong>{{ $course->authorization_details }}</strong>
        </div>
    </div>

    <div class="section-title">1. Anagrafica Partecipante & Riepilogo Monte Ore</div>
    <table>
        <tr>
            <th>Cognome e Nome</th>
            <td><strong>{{ $student->full_name }}</strong></td>
            <th>Codice Fiscale</th>
            <td><strong>{{ $student->fiscal_code }}</strong></td>
        </tr>
        <tr>
            <th>Luogo e Data di Nascita</th>
            <td>{{ $student->birth_city }} ({{ $student->birth_province }}), {{ $student->birth_date ? $student->birth_date->format('d/m/Y') : '' }}</td>
            <th>Email / Elenco Naz.</th>
            <td>{{ $student->email }} {{ $student->national_list_number ? '• ' . $student->national_list_number : '' }}</td>
        </tr>
        <tr>
            <th>Corso Frequentato</th>
            <td colspan="3"><strong>[{{ $course->code }}] {{ $course->title }}</strong> ({{ $course->thematic_area }})</td>
        </tr>
        <tr>
            <th>Ore Sincrone (Zoom)</th>
            <td><strong>{{ $enrollment->sync_hours_completed }} ore</strong> (su {{ $course->sync_required_hours }}h)</td>
            <th>Ore Asincrone (FAD)</th>
            <td><strong>{{ $enrollment->async_hours_completed }} ore</strong> (su {{ $course->async_required_hours }}h)</td>
        </tr>
        <tr>
            <th>Totale Ore Certificate</th>
            <td><strong>{{ $enrollment->total_hours_completed }} ore su {{ $course->total_hours }}h ({{ $enrollment->attendance_percent }}%)</strong></td>
            <th>Stato Idoneità</th>
            <td><strong>{{ $enrollment->is_eligible_for_certificate ? 'IDONEO (' . $enrollment->certificate_number . ')' : 'IN CORSO' }}</strong></td>
        </tr>
    </table>

    <div class="section-title">2. Stato Analitico dei Moduli Didattici (FAD Asincroni & Aula Live Zoom)</div>
    <table>
        <thead>
            <tr>
                <th>Modulo</th>
                <th>Titolo Modulo & Docente</th>
                <th>Durata Prevista</th>
                <th>Tempo Svolto Certificato</th>
                <th>% Completamento</th>
                <th>Data Completamento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($course->modules as $mod)
                @php
                    $mp = $moduleProgress[$mod->id] ?? null;
                    $zr = ($zoomRegistrants ?? collect())[$mod->id] ?? null;
                @endphp
                <tr>
                    <td>
                        Modulo {{ $mod->sort_order }}
                        @if($mod->video_source_type === 'zoom')
                            <br><strong style="color:#0284c7; font-size:7.5pt;">[LIVE ZOOM]</strong>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $mod->title }}</strong><br>
                        <span style="color:#64748b">{{ $mod->instructor_name }}</span>
                        @if($mod->video_source_type === 'zoom')
                            @if($mod->zoom_meeting_id)
                                <span style="color:#0284c7; font-size:7.5pt;"> • Meeting ID: {{ $mod->zoom_meeting_id }}</span>
                            @endif
                            @if($zr && $zr->zoom_registrant_id)
                                <br><span style="color:#0369a1; font-size:7.2pt;">Registrant ID Univoco: <strong>{{ $zr->zoom_registrant_id }}</strong></span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $mod->formatted_duration }} ({{ $mod->duration_seconds }}s)</td>
                    <td>{{ $mp ? round($mp->watched_seconds / 60, 1) . ' min (' . $mp->watched_seconds . 's)' : '0 min' }}</td>
                    <td><strong>{{ $mp ? $mp->completion_percent . '%' : '0%' }}</strong></td>
                    <td>{{ $mp && $mp->completed_at ? $mp->completed_at->format('d/m/Y H:i:s') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $hasZoomModules = $course->modules->contains(fn($m) => $m->video_source_type === 'zoom');
        $zoomRegList = isset($zoomRegistrants) ? $zoomRegistrants : collect();
    @endphp
    @if($hasZoomModules || $zoomRegList->isNotEmpty())
        <div class="section-title">2-bis. Registro Certificato Sessioni Sincrone Zoom (API Cloud & Registrant ID)</div>
        <table>
            <thead>
                <tr>
                    <th>Modulo Live</th>
                    <th>Meeting ID Zoom</th>
                    <th>Registrant ID Univoco</th>
                    <th>Primo Ingresso</th>
                    <th>Ultima Uscita</th>
                    <th>Tempo Certificato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($course->modules->where('video_source_type', 'zoom') as $zMod)
                    @php
                        $zr = $zoomRegList[$zMod->id] ?? null;
                        $mp = $moduleProgress[$zMod->id] ?? null;
                        $certSeconds = $zr ? $zr->attended_seconds : ($mp ? $mp->watched_seconds : 0);
                    @endphp
                    <tr>
                        <td><strong>M{{ $zMod->sort_order }} - {{ $zMod->title }}</strong></td>
                        <td>{{ $zMod->zoom_meeting_id ?: ($zr->zoom_meeting_id ?? 'Link Diretto') }}</td>
                        <td style="font-family: DejaVu Sans Mono, monospace; font-size: 7.8pt;">
                            {{ $zr?->zoom_registrant_id ?: 'TRACCIAMENTO-PORTALE' }}
                        </td>
                        <td>{{ $zr?->first_joined_at ? $zr->first_joined_at->format('d/m/Y H:i:s') : '-' }}</td>
                        <td>{{ $zr?->last_left_at ? $zr->last_left_at->format('d/m/Y H:i:s') : '-' }}</td>
                        <td><strong>{{ round($certSeconds / 60, 1) }} min ({{ $certSeconds }}s)</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">3. Log Certificato delle Sessioni di Connessione (Indirizzo IP / Sorgente & Timestamp)</div>
    <table>
        <thead>
            <tr>
                <th>Inizio Sessione</th>
                <th>Ultimo Heartbeat</th>
                <th>Modulo</th>
                <th>Intervallo Video</th>
                <th>Secondi Certificati</th>
                <th>Indirizzo IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($watchLogs as $wl)
                <tr>
                    <td>{{ $wl->started_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $wl->last_heartbeat_at?->format('d/m/Y H:i:s') }}</td>
                    <td>M{{ $wl->module?->sort_order }} - {{ $wl->module?->title }}</td>
                    <td>{{ gmdate('H:i:s', $wl->start_position_seconds) }} &rarr; {{ gmdate('H:i:s', $wl->end_position_seconds) }}</td>
                    <td><strong>{{ $wl->seconds_counted }}s</strong></td>
                    <td>{{ $wl->ip_address }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nessun log di sessione registrato.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
