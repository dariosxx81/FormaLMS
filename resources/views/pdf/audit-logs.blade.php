<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Registro Audit & Log Telemetrici</title>
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
            size: a4 landscape;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid {{ $primaryColor }};
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-logo {
            max-height: 44px;
            max-width: 170px;
        }
        .header-text-right {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.3;
        }

        /* Title Box */
        .doc-title-box {
            background-color: #f8fafc;
            border-left: 4px solid {{ $primaryColor }};
            padding: 6px 12px;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            color: {{ $secondaryColor }};
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .doc-subtitle {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 2px;
        }

        /* Filter pill box */
        .filter-summary {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 5px 10px;
            margin-bottom: 12px;
            font-size: 7.5pt;
        }

        /* Section Headings */
        .section-header {
            font-size: 8.8pt;
            font-weight: bold;
            color: #0f172a;
            background-color: #f1f5f9;
            border-left: 3px solid {{ $primaryColor }};
            padding: 4px 8px;
            margin-top: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 12px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 7.2pt;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 6.8pt;
        }

        .badge-time {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: bold;
            padding: 1.5px 4px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-event {
            background-color: #e2e8f0;
            color: #1e293b;
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
        }

        /* Footer / Signature */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 8px 12px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo Ente" class="header-logo">
                @else
                    <div style="font-size: 12pt; font-weight: bold; color: {{ $primaryColor }};">
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

    <!-- Title Box -->
    <div class="doc-title-box">
        <div class="doc-title">Registro Ufficiale Audit & Tracciamento Telemetrico</div>
        <div class="doc-subtitle">
            Estratto probatorio certificato di sessione telemetrica e log eventi di sistema (Art. 3 e Art. 6 D.M. 26 giugno 2024, n. 88 - Linee Guida FAD)
        </div>
    </div>

    <!-- Active Filters Summary -->
    <div class="filter-summary">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33%; border: none; padding: 2px;">
                    Corso Filtrato: <strong>{{ $selectedCourse ? '[' . $selectedCourse->code . '] ' . $selectedCourse->title : 'TUTTI I CORSI' }}</strong>
                </td>
                <td style="width: 33%; border: none; padding: 2px;">
                    Utente / Corsista: <strong>{{ $selectedUser ? $selectedUser->full_name . ' (' . $selectedUser->fiscal_code . ')' : 'TUTTI I CORSISTI' }}</strong>
                </td>
                <td style="width: 34%; text-align: right; border: none; padding: 2px;">
                    Data Estrazione: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- 1. Sessioni di Fruizione Video Certificata (WatchLogs) -->
    <div class="section-header">1. Sessioni di Fruizione Video Certificata (Tracciamento Heartbeat Player Anti-skip) [{{ $watchLogs->count() }} Record]</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 14%;">Inizio & Ultimo Heartbeat</th>
                <th style="width: 18%;">Corsista & Codice Fiscale</th>
                <th style="width: 22%;">Corso & Modulo Didattico</th>
                <th style="width: 13%;">Intervallo Posizione</th>
                <th style="width: 10%; text-align: right;">Tempo Effettivo</th>
                <th style="width: 23%;">Indirizzo IP & Dispositivo / Browser</th>
            </tr>
        </thead>
        <tbody>
            @forelse($watchLogs as $wl)
                <tr>
                    <td style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.8pt;">
                        <strong>{{ $wl->started_at?->format('d/m/Y H:i:s') }}</strong><br>
                        <span style="color: #64748b;">&rarr; {{ $wl->last_heartbeat_at?->format('H:i:s') }}</span>
                    </td>
                    <td>
                        <strong>{{ $wl->user?->full_name ?? 'Utente Eliminato' }}</strong><br>
                        <span style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.8pt; color: #475569;">{{ $wl->user?->fiscal_code }}</span>
                    </td>
                    <td>
                        <strong style="color: {{ $primaryColor }};">[{{ $wl->course?->code }}]</strong>
                        <span>M{{ $wl->module?->sort_order }}: {{ Str::limit($wl->module?->title, 35) }}</span>
                    </td>
                    <td style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.8pt;">
                        {{ gmdate('H:i:s', $wl->start_position_seconds) }} &rarr; {{ gmdate('H:i:s', $wl->end_position_seconds) }}
                    </td>
                    <td style="text-align: right;">
                        <span class="badge-time">
                            {{ round($wl->seconds_counted / 60, 1) }}m ({{ $wl->seconds_counted }}s)
                        </span>
                    </td>
                    <td style="font-size: 6.6pt; color: #475569;">
                        <span style="font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; color: #1e293b;">{{ $wl->ip_address }}</span><br>
                        <span style="color: #64748b;">{{ Str::limit($wl->user_agent, 45) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; font-style: italic; padding: 6px;">
                        Nessuna sessione di fruizione video registrata con i criteri selezionati.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 2. Registro Globale Eventi & Attività Piattaforma (ActivityLogs) -->
    <div class="section-header">2. Registro Globale Eventi & Attività Piattaforma (Audit Log) [{{ $activityLogs->count() }} Record]</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 14%;">Data / Ora (Timestamp)</th>
                <th style="width: 16%;">Tipo Evento</th>
                <th style="width: 20%;">Utente Esecutore</th>
                <th style="width: 38%;">Descrizione Attività Tracciata</th>
                <th style="width: 12%;">Indirizzo IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activityLogs as $act)
                <tr>
                    <td style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.8pt;">
                        {{ $act->created_at?->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                        <span class="badge-event">{{ strtoupper($act->action_type) }}</span>
                    </td>
                    <td>
                        <strong>{{ $act->user?->full_name ?? 'Sistema Automatico' }}</strong>
                        @if($act->user?->fiscal_code)
                            <div style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.5pt; color: #64748b;">{{ $act->user->fiscal_code }}</div>
                        @endif
                    </td>
                    <td>{{ $act->description }}</td>
                    <td style="font-family: 'DejaVu Sans Mono', monospace; font-size: 6.8pt;">{{ $act->ip_address ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; font-style: italic; padding: 6px;">
                        Nessun evento registrato con i criteri selezionati.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Clausola e Sottoscrizione -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%; vertical-align: top; border: none;">
                <strong style="color: {{ $secondaryColor }};">CERTIFICAZIONE DI CONFORMITÀ DEI TRACCIATI TELEMETRICI (Art. 6 D.M. 88/2024)</strong><br>
                <span style="font-size: 7.2pt; color: #475569; display: block; margin-top: 3px;">
                    Si attesta sotto la propria responsabilità che i log e le sessioni di tracciamento sopra elencati sono stati estratti dal registro elettronico non alterabile della piattaforma {{ $platformName }}, conforme alle normative vigenti in materia di tracciamento FAD, memorizzazione degli indirizzi IP e controlli anti-skip.
                </span>
            </td>
            <td style="width: 30%; vertical-align: top; text-align: center; border: none;">
                <div style="font-size: 7.2pt; color: #475569;">
                    Il Rappresentante Legale<br>
                    <strong>{{ $legalRep }}</strong>
                </div>
                <div style="border-bottom: 1px solid #475569; width: 180px; margin: 18px auto 3px auto;"></div>
                <div style="font-size: 6.5pt; color: #94a3b8;">
                    (Timbro e Firma)
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
