<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registro Presenze - {{ $course->code }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1e293b; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 14pt; font-weight: bold; color: #0f172a; }
        .sub { font-size: 9pt; color: #475569; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 8.5pt; }
        th { background-color: #f1f5f9; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .badge-ok { color: #065f46; font-weight: bold; }
        .badge-wait { color: #92400e; font-weight: bold; }
        .footer { margin-top: 22px; font-size: 8.5pt; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REGISTRO UFFICIALE RILEVAMENTO PRESENZE (SINCRONO ZOOM + ASINCRONO FAD)</div>
        <div class="sub">
            Ente Erogatore: <strong>{{ $settings['provider_name'] }}</strong> (ATECO {{ $settings['ateco_code'] }} - CP ISTAT {{ $settings['cp_istat_code'] }})<br>
            Corso: <strong>[{{ $course->code }}] {{ $course->title }}</strong> • Autorizzazione: <strong>{{ $course->authorization_details }}</strong><br>
            Monte Ore Corso: <strong>{{ $course->total_hours }}h</strong> ({{ $course->sync_required_hours }}h Sincrone + {{ $course->async_required_hours }}h Asincrone FAD) • Soglia Minima: <strong>{{ $course->min_attendance_percent }}% ({{ $course->minimum_required_hours }}h)</strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Corsista (Cognome e Nome)</th>
                <th>Codice Fiscale</th>
                <th>Data e Luogo Nascita</th>
                <th>Ore Sincrone (Zoom)</th>
                <th>Dettaglio Moduli Video FAD</th>
                <th>Ore FAD</th>
                <th>Totale Ore</th>
                <th>% Freq.</th>
                <th>Esito / N. Attestato</th>
            </tr>
        </thead>
        <tbody>
            @foreach($course->enrollments as $idx => $enr)
                @php
                    $u = $enr->user;
                    $mods = ($progressMatrix[$u->id] ?? collect())->keyBy('course_module_id');
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $u->full_name }}</strong><br><span style="color:#64748b">{{ $u->email }}</span></td>
                    <td style="font-family: DejaVu Sans Mono, monospace;">{{ $u->fiscal_code }}</td>
                    <td>{{ $u->birth_date ? $u->birth_date->format('d/m/Y') : '' }}<br>{{ $u->birth_city }} ({{ $u->birth_province }})</td>
                    <td><strong>{{ number_format($enr->sync_hours_completed, 1, ',', '') }}h</strong> / {{ $course->sync_required_hours }}h</td>
                    <td>
                        @foreach($course->modules as $m)
                            @php $mp = $mods[$m->id] ?? null; @endphp
                            M{{ $m->sort_order }}: {{ $mp ? $mp->completion_percent . '%' : '0%' }}@if(!$loop->last) | @endif
                        @endforeach
                    </td>
                    <td><strong>{{ number_format($enr->async_hours_completed, 2, ',', '') }}h</strong> / {{ $course->async_required_hours }}h</td>
                    <td><strong>{{ number_format($enr->total_hours_completed, 2, ',', '') }}h</strong> / {{ $course->total_hours }}h</td>
                    <td><strong>{{ $enr->attendance_percent }}%</strong></td>
                    <td>
                        @if($enr->is_eligible_for_certificate)
                            <span class="badge-ok">IDONEO ({{ $enr->certificate_number }})</span>
                        @else
                            <span class="badge-wait">IN CORSO</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento generato in data {{ now()->format('d/m/Y H:i:s') }} dal sistema di tracciamento certificato <strong>{{ $settings['platform_name'] }}</strong>.
        Firma del Coordinatore / Rappresentante Legale ({{ $settings['provider_legal_rep'] }}): ______________________________________
    </div>
</body>
</html>
