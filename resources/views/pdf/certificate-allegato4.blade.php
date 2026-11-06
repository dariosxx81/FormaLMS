<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Attestato di Frequenza - {{ $student->full_name }}</title>
    @php
        $primaryColor = $settings['primary_color'] ?? '#a30000';
        $secondaryColor = $settings['secondary_color'] ?? '#11102e';
        $providerName = $settings['provider_name'] ?? 'Ente di Formazione Accreditato';
        $legalRep = $settings['provider_legal_rep'] ?? 'Dott.ssa Daniela De Vita';
        $platformName = $settings['platform_name'] ?? 'Formacamera';

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
            margin: 6mm 8mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            font-family: "DejaVu Serif", "Times New Roman", serif;
            color: #1e293b;
        }

        /* Landscape Outer Prestige Border covering the exact A4 sheet height */
        .cert-outer-frame {
            border: 3px double {{ $primaryColor }};
            border-radius: 4px;
            padding: 0;
            background-color: #ffffff;
            height: 196mm;
            position: relative;
        }

        /* Top Brand Color Stripe */
        .top-brand-stripe {
            height: 4px;
            background-color: {{ $primaryColor }};
            width: 100%;
        }

        /* 1. Header Box */
        .header-box {
            position: absolute;
            top: 7mm;
            left: 18mm;
            right: 18mm;
            height: 20mm;
        }

        /* 2. Titolo Staccato in Alto */
        .title-box {
            position: absolute;
            top: 34mm;
            left: 18mm;
            right: 18mm;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 0;
        }

        /* 3. Si Attesta Che Staccato */
        .student-box {
            position: absolute;
            top: 59mm;
            left: 18mm;
            right: 18mm;
            text-align: center;
        }

        /* 4. Corso & Descrizione */
        .course-box {
            position: absolute;
            top: 92mm;
            left: 18mm;
            right: 18mm;
            text-align: center;
            font-size: 11pt;
            line-height: 1.7;
        }

        /* 5. Nastro Validita Nazionale */
        .validity-box {
            position: absolute;
            top: 128mm;
            left: 18mm;
            right: 18mm;
            text-align: center;
        }

        /* 6. Luogo, Data & Firme Spostate in Basso */
        .signatures-box {
            position: absolute;
            top: 148mm;
            left: 18mm;
            right: 18mm;
            height: 28mm;
        }

        /* 7. Piè di Pagina Protocollo a filo inferiore */
        .footer-box {
            position: absolute;
            bottom: 5mm;
            left: 18mm;
            right: 18mm;
            border-top: 1px dashed #cbd5e1;
            padding-top: 3mm;
        }
    </style>
</head>
<body>
<div class="cert-outer-frame">
    <div class="top-brand-stripe"></div>

    <!-- 1. Header Ministero + Logo Ente -->
    <div class="header-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 14.5pt; font-style: italic; font-weight: bold; color: #0f172a; letter-spacing: 0.5px;">Ministero del Turismo</div>
                    <div style="font-size: 8pt; font-style: italic; color: #64748b; margin-top: 2px;">Direzione Generale Controllo, Regolamentazione, Acquisti, Formazione e Professioni Turistiche</div>
                </td>
                <td style="text-align: right; vertical-align: middle; width: 220px;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo Ente" style="max-height: 48px; max-width: 200px; object-fit: contain;">
                    @else
                        <div style="font-family: 'DejaVu Sans'; font-weight: 800; font-size: 12pt; color: {{ $primaryColor }}; text-transform: uppercase;">{{ $providerName }}</div>
                    @endif
                    <div style="font-family: 'DejaVu Sans'; font-size: 8pt; font-weight: 800; color: {{ $primaryColor }}; letter-spacing: 0.6px; margin-top: 2px;">
                        {{ $course->course_type === 'specializzazione' ? 'ALLEGATO 3 • D.M. 26/06/2024 N. 88' : 'ALLEGATO 4 • D.M. 26/06/2024 N. 88' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 2. Titolo Principale Ben Distanziato -->
    <div class="title-box">
        @if($course->course_type === 'specializzazione')
            <div style="font-family: 'DejaVu Sans'; font-size: 16pt; font-weight: 900; letter-spacing: 2px; color: {{ $primaryColor }}; text-transform: uppercase;">ATTESTATO DI COMPLETAMENTO</div>
            <div style="font-family: 'DejaVu Sans'; font-size: 8.5pt; color: #64748b; margin-top: 2px;">Corso di Specializzazione Professionale per Guida Turistica</div>
        @else
            <div style="font-family: 'DejaVu Sans'; font-size: 16pt; font-weight: 900; letter-spacing: 2px; color: {{ $primaryColor }}; text-transform: uppercase;">ATTESTATO DI FREQUENZA</div>
            <div style="font-family: 'DejaVu Sans'; font-size: 8.5pt; color: #64748b; margin-top: 2px;">Corso di Aggiornamento Professionale per Guida Turistica</div>
        @endif
    </div>

    <!-- 3. SI ATTESTA CHE & Nome Corsista Staccati -->
    <div class="student-box">
        <div style="font-family: 'DejaVu Sans'; font-size: 9.5pt; font-weight: 900; letter-spacing: 3px; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">SI ATTESTA CHE</div>
        <div style="font-family: 'DejaVu Sans'; font-size: 24pt; font-weight: 900; color: {{ $secondaryColor }}; border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 4px; display: inline-block;">
            {{ strtoupper($student->full_name) }}
        </div>
        <div style="font-size: 9.5pt; color: #475569; margin-top: 6px;">
            Codice Fiscale: <strong style="color: #0f172a;">{{ $student->fiscal_code ?: 'N/D' }}</strong>
            &nbsp;&bull;&nbsp;
            Nato/a il <strong>{{ $student->birth_date ? $student->birth_date->format('d/m/Y') : '__/__/____' }}</strong>
            a <strong>{{ $student->birth_city ?: 'Roma' }} ({{ $student->birth_province ?: 'RM' }})</strong>
        </div>
    </div>

    <!-- 4. Narrazione Corso e Ore Certificate -->
    <div class="course-box">
        ha frequentato e superato con esito positivo il percorso formativo accreditato:<br>
        <strong style="font-size: 13pt; color: #0f172a;">"{{ $course->title }}"</strong>
        <span style="font-size: 10pt; color: #64748b;">(Cod. <strong>{{ $course->code }}</strong>)</span><br>
        erogato da <strong>{{ $providerName }}</strong> con un monte ore certificato di
        <strong style="color: {{ $primaryColor }}; font-size: 12pt;">{{ number_format($enrollment->total_hours_completed, 2, ',', '') }} ORE</strong>
        in modalit&agrave; telematica FAD interattiva con tracciamento AgID.
    </div>

    <!-- 5. Nastro Validità Nazionale -->
    <div class="validity-box">
        <div style="display: inline-block; font-family: 'DejaVu Sans'; font-size: 9pt; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $primaryColor }}; background-color: #fff7ed; border: 1px solid #fed7aa; padding: 6px 26px; border-radius: 9999px;">
            &bull; IL PRESENTE ATTESTATO HA VALIDIT&Agrave; SU TUTTO IL TERRITORIO NAZIONALE &bull;
        </div>
    </div>

    <!-- 6. Luogo, Data & Firma Posizionati in Basso -->
    <div class="signatures-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: bottom; width: 50%; font-family: 'DejaVu Sans'; font-size: 9pt; color: #475569; line-height: 1.5;">
                    Luogo e Data di Rilascio:<br>
                    <strong style="color: #0f172a; font-size: 10.5pt;">{{ $course->training_center_city ?: 'Roma' }}, {{ ($enrollment->certificate_issued_at ?? now())->format('d/m/Y') }}</strong><br>
                    <span style="font-size: 8pt;">Ente Erogatore: {{ $providerName }}</span>
                </td>
                <td style="vertical-align: bottom; width: 50%; text-align: right;">
                    <div style="display: inline-block; text-align: center; min-width: 270px; font-family: 'DejaVu Sans';">
                        <div style="font-size: 8.5pt; color: #475569;">Firma del Legale Rappresentante</div>
                        <div style="border-bottom: 1px solid #0f172a; padding-bottom: 4px; font-weight: bold; font-size: 11pt; color: #0f172a; margin-top: 8px;">
                            {{ $legalRep }}
                        </div>
                        <div style="font-size: 7.5pt; color: #64748b; font-style: italic; margin-top: 3px;">
                            (Rappresentante legale dell'Ente di formazione autorizzato)
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 7. Piè di Pagina Protocollo a Bordo Inferiore -->
    <div class="footer-box">
        <table style="width: 100%; border-collapse: collapse; font-family: 'DejaVu Sans'; font-size: 7.2pt; color: #64748b;">
            <tr>
                <td style="vertical-align: middle;">
                    Protocollo Univoco: <strong style="font-family: 'Courier New'; font-size: 8.5pt; color: #0f172a;">{{ $enrollment->certificate_number ?: 'ATT-' . $enrollment->id . '-' . date('Y') }}</strong>
                    &bull; ATECO: <strong>{{ $settings['ateco_code'] ?? '79.90' }}</strong> &bull; CP ISTAT: <strong>{{ $settings['cp_istat_code'] ?? '3.4.1.5.2' }}</strong><br>
                    Rilasciato tramite piattaforma <strong>{{ $platformName }}</strong> in conformit&agrave; alla Legge 13 dicembre 2023 n. 190 e al D.M. 26 giugno 2024 n. 88.
                </td>
                <td style="text-align: right; vertical-align: middle; width: 140px;">
                    <span style="border: 1px solid #cbd5e1; padding: 3px 6px; border-radius: 4px; font-weight: bold; background: #f8fafc; color: {{ $primaryColor }}; font-size: 7pt;">
                        DOCUMENTO UFFICIALE
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
