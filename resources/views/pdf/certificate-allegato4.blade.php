<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Attestato Ministeriale - {{ $student->full_name }}</title>
    <style>
        @page { margin: 38px 46px; }
        body {
            font-family: "Times New Roman", DejaVu Serif, serif;
            color: #111827;
            line-height: 1.55;
            font-size: 13pt;
        }
        .border-frame {
            border: 2px solid #1e293b;
            padding: 28px 34px;
            min-height: 920px;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 18px;
        }
        .ministry-title {
            font-size: 17pt;
            font-style: italic;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .ministry-sub {
            font-size: 9.5pt;
            font-style: italic;
            color: #374151;
        }
        .allegato-badge {
            text-align: right;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 8px;
        }
        .doc-title {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 13pt;
            font-weight: bold;
            margin-top: 14px;
            text-transform: uppercase;
        }
        .doc-subtitle {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            margin-top: 8px;
        }
        .codes-row {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            font-weight: bold;
            margin: 16px 0;
        }
        .release-reason {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 22px;
        }
        .section-label {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            margin: 18px 0 10px 0;
        }
        .paragraph-box {
            text-align: justify;
            font-size: 12pt;
            line-height: 1.75;
        }
        .filled-value {
            font-weight: bold;
            border-bottom: 1px solid #374151;
            padding: 0 4px;
        }
        .national-validity {
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            margin: 34px 0 28px 0;
            letter-spacing: 0.4px;
        }
        .footer-area {
            margin-top: 40px;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
        }
        .signature-line {
            margin-top: 24px;
            width: 320px;
            border-bottom: 1px solid #111827;
            padding-bottom: 4px;
            font-weight: bold;
        }
        .meta-stamp {
            margin-top: 36px;
            padding-top: 10px;
            border-top: 1px dashed #9ca3af;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #4b5563;
        }
    </style>
</head>
<body>
<div class="border-frame">
    <div class="header">
        <div class="ministry-title">Ministero del Turismo</div>
        <div class="ministry-sub">Direzione Generale Controllo, Regolamentazione, Acquisti, Formazione e Professioni Turistiche</div>
    </div>

    <div class="allegato-badge">
        {{ $course->course_type === 'specializzazione' ? 'Allegato 3' : 'Allegato 4' }}
    </div>

    @if($course->course_type === 'specializzazione')
        <div class="doc-title">ATTESTATO DI COMPLETAMENTO DEL CORSO DI SPECIALIZZAZIONE PER GUIDA TURISTICA</div>
        <div class="doc-subtitle">
            Ai sensi dell'articolo 22 comma 6 del Decreto Ministeriale 26 giugno 2024, n. 88, recante disposizione<br>
            applicative della legge 13 dicembre 2023, n. 190
        </div>
    @else
        <div class="doc-title">ATTESTATO DI FREQUENZA DEL CORSO DI AGGIORNAMENTO PER GUIDA TURISTICA</div>
        <div class="doc-subtitle">
            Ai sensi dell'articolo 23 comma 3 del Decreto Ministeriale 26 giugno 2024, n. 88, recante disposizione<br>
            applicative della legge 13 dicembre 2023, n. 190
        </div>
    @endif

    <div class="codes-row">
        Codice ATECO: {{ $settings['ateco_code'] ?? '79.90' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; CP ISTAT: {{ $settings['cp_istat_code'] ?? '3.4.1.5.2' }}
    </div>

    <div class="release-reason">
        @if($course->course_type === 'specializzazione')
            Rilasciato in esito al superamento della prova finale
        @else
            Rilasciato in esito alla partecipazione con accertamento di presenza di almeno l'{{ $course->min_attendance_percent }}% delle ore di corso di aggiornamento
        @endif
    </div>

    <div class="section-label">DA</div>

    <div class="paragraph-box">
        <span class="filled-value">{{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }}</span>
        autorizzato all'erogazione del corso dal Ministero del turismo con
        <span class="filled-value">{{ $course->authorization_details ?: 'Provvedimento Ministeriale D.G. Formazione Turistica' }}</span>
        istituito presso
        <span class="filled-value">{{ $course->training_center_name ?: ($settings['provider_name'] ?? 'Polo Formativo Accreditato') }}</span>
        di <span class="filled-value">{{ $course->training_center_city ?: 'Roma (RM)' }}</span>
        afferente all'ambito tematico
        <span class="filled-value">{{ $course->thematic_area ?: $course->title }}</span>
        (Corso: <em>"{{ $course->title }}"</em> - Cod. {{ $course->code }}).
    </div>

    <div class="section-label">CONFERITO A</div>

    <div class="paragraph-box">
        Il Sig./Sig.ra <span class="filled-value">{{ $student->full_name }}</span>
        (C.F. <span class="filled-value">{{ $student->fiscal_code ?: 'N/D' }}</span>)
        nato/a il <span class="filled-value">{{ $student->birth_date ? $student->birth_date->format('d/m/Y') : '__/__/____' }}</span>
        a <span class="filled-value">{{ $student->birth_city ?: '________' }}</span>,
        Provincia (<span class="filled-value">{{ $student->birth_province ?: '__' }}</span>)
        in <span class="filled-value">{{ $student->birth_country ?: 'Italia' }}</span>,
        con una frequenza pari a <span class="filled-value">{{ number_format($enrollment->total_hours_completed, 1, ',', '') }}</span> ore
        (su {{ number_format($course->total_hours, 0) }} ore complessive previste, di cui {{ number_format($enrollment->sync_hours_completed, 1, ',', '') }}h in modalità sincrona e {{ number_format($enrollment->async_hours_completed, 1, ',', '') }}h in modalità FAD asincrona tracciata).
    </div>

    <div class="national-validity">
        IL PRESENTE ATTESTATO È VALIDO SU TUTTO IL TERRITORIO NAZIONALE
    </div>

    <div class="footer-area">
        <div>Firma</div>
        <div class="signature-line">
            {{ $settings['provider_legal_rep'] ?? 'Il Rappresentante Legale' }}
        </div>
        <div style="font-style: italic; font-size: 9.5pt; margin-top: 4px;">
            (Rappresentante legale dell'Ente di formazione competente al rilascio dell'attestato)
        </div>

        <div style="margin-top: 26px;">
            <span class="filled-value">{{ $course->training_center_city ?: 'Roma' }}</span>,
            <span class="filled-value">{{ ($enrollment->certificate_issued_at ?? now())->format('d / m / Y') }}</span>
        </div>
        <div style="font-style: italic; font-size: 9.5pt; margin-top: 4px;">
            (Luogo e Data di rilascio)
        </div>
    </div>

    <div class="meta-stamp">
        Protocollo Attestato N. <strong>{{ $enrollment->certificate_number ?: 'CERT-' . $enrollment->id }}</strong>
        • Generato e certificato tramite piattaforma telematica <strong>{{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</strong>
        • Verifica telemetrica conforme all'Art. 6 del D.M. 26 giugno 2024, n. 88.
    </div>
</div>
</body>
</html>
