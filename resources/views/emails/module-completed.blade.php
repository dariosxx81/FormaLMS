<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modulo Completato — {{ $settings['platform_name'] ?? 'FormaLMS' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; padding: 24px; margin: 0;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        @include('emails.partials.header')

        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 40px; line-height: 1;">✅</div>
            <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 10px 0 4px 0;">
                Modulo Didattico Completato!
            </h3>
            <p style="font-size: 13px; color: #475569; margin: 0;">
                Il tuo avanzamento è stato registrato e certificato nel registro presenze.
            </p>
        </div>

        <p style="font-size: 14px; line-height: 1.6; color: #334155;">
            @if(!empty($customBody))
                Gentile <strong>{{ $user->full_name }}</strong>,<br><br>
                {!! nl2br(e($customBody)) !!}
            @else
                Gentile <strong>{{ $user->full_name }}</strong>, ti confermiamo che hai completato al <strong>100%</strong> il seguente modulo didattico:
            @endif
        </p>

        <!-- Module & Progress Details Box -->
        <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-left: 5px solid {{ $settings['primary_color'] ?? '#4f46e5' }}; border-radius: 12px; padding: 20px; margin: 22px 0;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #64748b; letter-spacing: 0.5px;">
                Corso: [{{ $course->code }}] {{ $course->title }}
            </div>
            <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 6px;">
                Modulo #{{ $module->sort_order }}: {{ $module->title }}
            </div>
            <div style="font-size: 13px; color: #334155; margin-top: 10px; line-height: 1.6;">
                <strong>Tipologia Modulo:</strong> {{ $module->is_zoom ? 'Aula Virtuale Sincrona (Zoom Live)' : 'Video Lezione FAD Asincrona' }}<br>
                <strong>Durata Certificata Modulo:</strong> {{ $module->formatted_duration }}<br>
                @if($module->instructor_name)
                    <strong>Docente / Relatore:</strong> {{ $module->instructor_name }}<br>
                @endif
                <strong>Data e Ora Completamento:</strong> {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>

        <!-- Course Overall Progress Summary -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; margin: 20px 0;">
            <div style="font-size: 12px; font-weight: bold; color: #166534;">
                📊 Riepilogo Avanzamento Complessivo sul Corso:
            </div>
            <div style="font-size: 14px; font-weight: 800; color: #14532d; margin-top: 4px;">
                Frequenza maturata: {{ $enrollment->attendance_percent }}% ({{ number_format($enrollment->total_hours_completed, 2, ',', '') }}h su {{ $course->total_hours }}h)
            </div>
            <div style="font-size: 12px; color: #15803d; margin-top: 4px;">
                @if($enrollment->is_eligible_for_certificate)
                    🏆 Hai già raggiunto la soglia minima del {{ $course->min_attendance_percent }}%! Il tuo Attestato è sbloccato e scaricabile.
                @else
                    Soglia richiesta per il rilascio dell'Attestato: {{ $course->min_attendance_percent }}% ({{ $course->minimum_required_hours }}h).
                @endif
            </div>
        </div>

        <!-- CTA Button -->
        <p style="margin: 26px 0 22px 0; text-align: center;">
            <a href="{{ route('student.courses.show', $course) }}"
               style="display: inline-block; background-color: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: #ffffff; text-decoration: none; font-weight: bold; padding: 13px 28px; border-radius: 12px; font-size: 14px;">
                ▶ Prosegui il Corso in Piattaforma
            </a>
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        
        <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0;">
            {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }} • Sede: {{ $settings['provider_address'] ?? '' }}<br>
            Per qualsiasi richiesta o assistenza: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}</a>
        </p>
    </div>
</body>
</html>
