<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fascicolo Probante Corso - {{ $course->code }}</title>
    @php
        $primaryColor = $settings['primary_color'] ?? '#a30000';
        $secondaryColor = $settings['secondary_color'] ?? '#11102e';
        $providerName = $settings['provider_name'] ?? 'Ente di Formazione Accreditato';
        $legalRep = $settings['provider_legal_rep'] ?? 'Dott.ssa Daniela De Vita';
        $platformName = $settings['platform_name'] ?? 'FormaLMS';

        $logoBase64 = null;
        if (!empty($settings['logo_url'])) {
            $cleaned = ltrim($settings['logo_url'], '/');
            $fullPath = public_path($cleaned);
            if (file_exists($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $mime = match(strtolower($ext)) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    default => 'image/png'
                };
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }
    @endphp
    <style>
        @page {
            size: a4 portrait;
            margin: 15mm 15mm 18mm 15mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Institutional Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid {{ $primaryColor }};
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-logo {
            max-height: 48px;
            max-width: 180px;
        }
        .header-text-right {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.3;
        }
        .doc-title-box {
            background-color: #f8fafc;
            border-left: 4px solid {{ $primaryColor }};
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: {{ $secondaryColor }};
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .doc-subtitle {
            font-size: 8pt;
            color: #475569;
            margin-top: 3px;
        }

        /* Section Headings */
        .section-header {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            background-color: #f1f5f9;
            border-left: 3px solid {{ $primaryColor }};
            padding: 5px 8px;
            margin-top: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4.5px 6px;
            font-size: 7.8pt;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.2pt;
        }
        table.info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.info-grid td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 8pt;
        }
        .label-cell {
            background-color: #f8fafc;
            font-weight: bold;
            color: #475569;
            width: 20%;
        }
        .value-cell {
            width: 30%;
            color: #0f172a;
        }

        /* Badges */
        .badge-ok {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-wait {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
        }

        /* Student block */
        .student-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background-color: #ffffff;
            margin-top: 10px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .student-card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 10px;
            font-weight: bold;
        }

        /* Page break utilities */
        .page-break {
            page-break-after: always;
        }

        /* Legal Sign Box */
        .signature-box {
            margin-top: 18px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 10px 14px;
            font-size: 8pt;
            page-break-inside: avoid;
        }
        .signature-line {
            border-bottom: 1px solid #475569;
            width: 230px;
            display: inline-block;
            margin-top: 24px;
        }
    </style>
</head>
<body>

    <!-- Institutional Header -->
    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo Ente" class="header-logo">
                @else
                    <div style="font-size: 13pt; font-weight: bold; color: {{ $primaryColor }};">
                        {{ $providerName }}
                    </div>
                @endif
            </td>
            <td class="header-text-right" style="width: 50%; vertical-align: middle;">
                <strong>{{ $providerName }}</strong><br>
                Codice ATECO: {{ $settings['ateco_code'] ?? '85.59.20' }} • CP ISTAT: {{ $settings['cp_istat_code'] ?? '2.5.1.5' }}<br>
                Sede: {{ $settings['provider_address'] ?? 'Via Nazionale, Roma' }}<br>
                Rappresentante Legale: <strong>{{ $legalRep }}</strong>
            </td>
        </tr>
    </table>

    <!-- Document Title Box -->
    <div class="doc-title-box">
        <div class="doc-title">Fascicolo Probante Generale di Corso</div>
        <div class="doc-subtitle">
            Documentazione probatoria ufficiale della frequenza telematica, tracciamento anti-skip e conformità didattica (D.M. 26 giugno 2024, n. 88 - Art. 6)
        </div>
    </div>

    <!-- SEZIONE 1: Dati Corso -->
    <div class="section-header">1. Quadro Generale del Progetto Formativo</div>
    <table class="info-grid">
        <tr>
            <td class="label-cell">Titolo Corso</td>
            <td class="value-cell" colspan="3"><strong>[{{ $course->code }}] {{ $course->title }}</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Tipologia / Modalità</td>
            <td class="value-cell">
                @if($course->has_sync_hours)
                    Ibrido ({{ $course->sync_required_hours }}h Zoom + {{ $course->async_required_hours }}h FAD)
                @else
                    100% Piattaforma Asincrona FAD ({{ $course->total_hours }}h)
                @endif
            </td>
            <td class="label-cell">Area Tematica</td>
            <td class="value-cell">{{ $course->thematic_area ?? 'Formazione Continua e Specialistica' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Provvedimento / Aut.</td>
            <td class="value-cell">{{ $course->authorization_details ?? 'Linee Guida Nazionali FAD' }}</td>
            <td class="label-cell">Soglia Min. Attestato</td>
            <td class="value-cell"><strong>{{ $course->min_attendance_percent }}% (pari a {{ $course->minimum_required_hours }}h)</strong></td>
        </tr>
        <tr>
            <td class="label-cell">Totale Corsisti Iscritti</td>
            <td class="value-cell"><strong>{{ $course->enrollments->count() }}</strong> partecipanti</td>
            <td class="label-cell">Attestati Rilasciati</td>
            <td class="value-cell"><strong>{{ $course->enrollments->where('is_eligible_for_certificate', true)->count() }}</strong> idonei certificati</td>
        </tr>
    </table>

    <!-- SEZIONE 2: Quadro Didattico e Moduli -->
    <div class="section-header">2. Articolazione Didattica e Moduli Formativi ({{ $course->modules->count() }} Moduli)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">Mod.</th>
                <th style="width: 44%;">Titolo Modulo Formativo</th>
                <th style="width: 24%;">Docente / Formatore</th>
                <th style="width: 14%;">Tipologia Risorsa</th>
                <th style="width: 12%; text-align: right;">Durata Certificata</th>
            </tr>
        </thead>
        <tbody>
            @forelse($course->modules as $mod)
                <tr>
                    <td style="text-align: center; font-weight: bold;">M{{ $mod->sort_order }}</td>
                    <td><strong>{{ $mod->title }}</strong></td>
                    <td>{{ $mod->instructor_name ?: 'Corpo Docenti Accreditato' }}</td>
                    <td>
                        @if($mod->video_source_type === 'zoom')
                            Aula Sincrona Live (ZOOM {{ $mod->zoom_meeting_id ? '#' . $mod->zoom_meeting_id : '' }})
                        @else
                            Video Lezione FAD Anti-skip ({{ strtoupper($mod->video_source_type ?? 'mp4') }})
                        @endif
                    </td>
                    <td style="text-align: right;"><strong>{{ $mod->formatted_duration }}</strong> ({{ $mod->duration_seconds }}s)</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">Nessun modulo didattico inserito.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SEZIONE 3: Registro Generale Corsisti -->
    <div class="section-header">3. Registro Generale Partecipanti e Riepilogo Frequenze</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 25%;">Corsista (Cognome e Nome)</th>
                <th style="width: 17%;">Codice Fiscale</th>
                <th style="width: 10%; text-align: center;">Ore Zoom</th>
                <th style="width: 10%; text-align: center;">Ore FAD</th>
                <th style="width: 11%; text-align: center;">Tot. Svolto</th>
                <th style="width: 9%; text-align: center;">% Freq.</th>
                <th style="width: 14%; text-align: center;">Esito / Attestato</th>
            </tr>
        </thead>
        <tbody>
            @forelse($course->enrollments as $idx => $enr)
                @php $u = $enr->user; @endphp
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $u->full_name }}</strong><br>
                        <span style="font-size: 7pt; color: #64748b;">{{ $u->email }}</span>
                    </td>
                    <td style="font-family: 'DejaVu Sans Mono', monospace; font-size: 7.5pt;">{{ $u->fiscal_code }}</td>
                    <td style="text-align: center;">{{ number_format($enr->sync_hours_completed, 1, ',', '') }}h / {{ $course->sync_required_hours }}h</td>
                    <td style="text-align: center;">{{ number_format($enr->async_hours_completed, 2, ',', '') }}h / {{ $course->async_required_hours }}h</td>
                    <td style="text-align: center;"><strong>{{ number_format($enr->total_hours_completed, 2, ',', '') }}h</strong> / {{ $course->total_hours }}h</td>
                    <td style="text-align: center; font-weight: bold;">{{ $enr->attendance_percent }}%</td>
                    <td style="text-align: center;">
                        @if($enr->is_eligible_for_certificate)
                            <span class="badge-ok">IDONEO<br><span style="font-size: 6.8pt;">{{ $enr->certificate_number }}</span></span>
                        @else
                            <span class="badge-wait">IN CORSO</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b;">Nessun corsista iscritto a questo corso.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- SEZIONE 4: Schede Probatorie e Telemetria Individuale -->
    <div class="section-header">4. Schede Analitiche Probatorie e Log di Visione per Singolo Corsista</div>
    <p style="font-size: 7.8pt; color: #475569; margin-top: 2px; margin-bottom: 8px;">
        Per ciascun corsista iscritto vengono riportati lo stato di fruizione modulo per modulo e i singoli log di sessione telemetrica (indirizzo IP, orari di fruizione, intervallo temporale e tracciamento certificato anti-skip).
    </p>

    @foreach($course->enrollments as $enrIndex => $enr)
        @php
            $u = $enr->user;
            $userProgress = ($progressMatrix[$u->id] ?? collect())->keyBy('course_module_id');
            $userWatchLogs = $watchLogsByUser[$u->id] ?? collect();
            $userZoomRegs = (($zoomRegistrantsByUser ?? [])[$u->id] ?? collect())->keyBy('course_module_id');
        @endphp

        <div class="student-card">
            <div class="student-card-header">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; border: none; padding: 0;">
                            <span style="font-size: 9pt; color: {{ $primaryColor }}; font-weight: bold;">{{ $enrIndex + 1 }}. {{ $u->full_name }}</span>
                            <span style="font-size: 7.5pt; color: #64748b;">(C.F. {{ $u->fiscal_code }})</span>
                        </td>
                        <td style="width: 50%; text-align: right; border: none; padding: 0; font-size: 7.8pt;">
                            Ore: <strong>{{ number_format($enr->total_hours_completed, 2, ',', '') }}h / {{ $course->total_hours }}h ({{ $enr->attendance_percent }}%)</strong>
                            &bull;
                            @if($enr->is_eligible_for_certificate)
                                <span class="badge-ok">IDONEO (Att. {{ $enr->certificate_number }})</span>
                            @else
                                <span class="badge-wait">IN CORSO</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div style="padding: 6px 8px;">
                <!-- Moduli del corsista -->
                <div style="font-weight: bold; font-size: 7.5pt; color: #334155; margin-bottom: 3px; text-transform: uppercase;">
                    &bull; Avanzamento Analitico Moduli Didattici (FAD & Live Zoom):
                </div>
                <table class="data-table" style="margin-bottom: 8px;">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Modulo</th>
                            <th style="width: 44%;">Titolo & Riferimento Aula</th>
                            <th style="width: 16%; text-align: right;">Durata Richiesta</th>
                            <th style="width: 16%; text-align: right;">Tempo Tracciato</th>
                            <th style="width: 14%; text-align: center;">Stato & %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($course->modules as $mod)
                            @php
                                $mp = $userProgress[$mod->id] ?? null;
                                $zr = $userZoomRegs[$mod->id] ?? null;
                            @endphp
                            <tr>
                                <td style="text-align: center;">
                                    M{{ $mod->sort_order }}
                                    @if($mod->video_source_type === 'zoom')
                                        <br><span style="color:#0284c7; font-weight:bold; font-size:6.5pt;">[ZOOM]</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $mod->title }}
                                    @if($mod->video_source_type === 'zoom')
                                        <br>
                                        <span style="color:#0369a1; font-size:6.8pt;">
                                            Meeting ID: {{ $mod->zoom_meeting_id ?: 'Diretto' }}
                                            @if($zr && $zr->zoom_registrant_id)
                                                &bull; Reg. ID: <strong>{{ $zr->zoom_registrant_id }}</strong>
                                            @endif
                                            @if($zr && $zr->first_joined_at)
                                                &bull; Ingr.: {{ $zr->first_joined_at->format('d/m H:i') }} - Usc.: {{ $zr->last_left_at?->format('H:i') ?? '-' }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: right;">{{ $mod->formatted_duration }} ({{ $mod->duration_seconds }}s)</td>
                                <td style="text-align: right;">
                                    {{ $mp ? round($mp->watched_seconds / 60, 1) . 'm (' . $mp->watched_seconds . 's)' : '0m' }}
                                </td>
                                <td style="text-align: center;">
                                    @if($mp && $mp->completion_percent >= 100)
                                        <span class="badge-ok">100%</span>
                                    @elseif($mp && $mp->completion_percent > 0)
                                        <span class="badge-wait">{{ $mp->completion_percent }}%</span>
                                    @else
                                        <span style="color:#94a3b8;">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Log di Connessione Telemetrica (WatchLogs) -->
                <div style="font-weight: bold; font-size: 7.5pt; color: #334155; margin-bottom: 3px; text-transform: uppercase;">
                    &bull; Log Certificato Sessioni di Visione (WatchLogs):
                </div>
                <table class="data-table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Inizio Sessione</th>
                            <th style="width: 20%;">Ultimo Heartbeat</th>
                            <th style="width: 20%;">Modulo</th>
                            <th style="width: 16%;">Intervallo Video</th>
                            <th style="width: 10%; text-align: right;">Secondi</th>
                            <th style="width: 14%; text-align: center;">Indirizzo IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userWatchLogs as $wl)
                            <tr>
                                <td>{{ $wl->started_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $wl->last_heartbeat_at?->format('d/m/Y H:i:s') }}</td>
                                <td>M{{ $wl->module?->sort_order }} - {{ Str::limit($wl->module?->title, 20) }}</td>
                                <td>{{ gmdate('H:i:s', $wl->start_position_seconds) }} &rarr; {{ gmdate('H:i:s', $wl->end_position_seconds) }}</td>
                                <td style="text-align: right;"><strong>{{ $wl->seconds_counted }}s</strong></td>
                                <td style="text-align: center; font-family: 'DejaVu Sans Mono', monospace; font-size: 7.2pt;">{{ $wl->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; font-style: italic; padding: 4px;">
                                    Nessuna sessione telemetrica registrata per questo corsista.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <!-- SEZIONE 5: Certificazione di Conformità e Firma Legale -->
    <div class="signature-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 65%; vertical-align: top; border: none; padding-right: 14px;">
                    <strong style="color: {{ $secondaryColor }};">ATTESTAZIONE DI CONFORMITÀ DEI LOG TELEMATICI (Art. 6 D.M. 88/2024)</strong><br>
                    <span style="font-size: 7.5pt; color: #475569; display: block; margin-top: 4px;">
                        Si certifica sotto la propria responsabilità che il presente Fascicolo Probante Generale contiene i tracciati telemetrici originali e non alterati, acquisiti mediante il sistema informatico conforme alle Linee Guida FAD e dotato di controlli anti-skip e memorizzazione degli indirizzi IP di fruizione.
                    </span>
                    <div style="margin-top: 10px; font-size: 7.5pt; color: #334155;">
                        Documento generato telematicamente in data <strong>{{ now()->format('d/m/Y') }}</strong> alle ore <strong>{{ now()->format('H:i:s') }}</strong>.
                    </div>
                </td>
                <td style="width: 35%; vertical-align: top; text-align: center; border: none;">
                    <div style="font-size: 7.5pt; color: #475569;">
                        Il Rappresentante Legale<br>
                        <strong>{{ $legalRep }}</strong>
                    </div>
                    <div class="signature-line"></div>
                    <div style="font-size: 6.8pt; color: #94a3b8; margin-top: 3px;">
                        (Timbro dell'Ente e Firma Digitale / Autografa)
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
