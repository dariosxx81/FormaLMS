<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Attestato Conseguito — {{ $settings['platform_name'] ?? 'FormaLMS' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; padding: 24px; margin: 0;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        @include('emails.partials.header')

        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 44px; line-height: 1;">🏆</div>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 10px 0 4px 0;">
                Congratulazioni, {{ $user->first_name }}!
            </h3>
            <p style="font-size: 14px; color: #16a34a; font-weight: bold; margin: 0;">
                Hai completato con successo il percorso formativo e raggiunto la frequenza richiesta.
            </p>
        </div>

        <p style="font-size: 14px; line-height: 1.6; color: #334155;">
            @if(!empty($customBody))
                Gentile <strong>{{ $user->full_name }}</strong>,<br><br>
                {!! nl2br(e($customBody)) !!}
            @else
                Gentile <strong>{{ $user->full_name }}</strong>, ti comunichiamo che è stato generato e rilasciato il tuo <strong>Attestato Ufficiale di Frequenza</strong> conforme alle disposizioni vigenti.
            @endif
        </p>

        <!-- Certificate Details Box -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 5px solid #16a34a; border-radius: 12px; padding: 20px; margin: 24px 0;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #15803d; letter-spacing: 0.5px;">
                Dettagli Attestato di Frequenza
            </div>
            <div style="font-size: 16px; font-weight: 800; color: #14532d; margin-top: 6px;">
                [{{ $course->code }}] {{ $course->title }}
            </div>
            <div style="font-size: 13px; color: #166534; margin-top: 8px; line-height: 1.5;">
                <strong>Numero Attestato:</strong> <span style="font-family: monospace; background: #dcfce7; padding: 2px 6px; border-radius: 4px; font-weight: bold;">{{ $enrollment->certificate_number }}</span><br>
                <strong>Monte Ore Maturato:</strong> {{ number_format($enrollment->total_hours_completed, 2, ',', '') }}h su {{ $course->total_hours }}h ({{ $enrollment->attendance_percent }}%)<br>
                <strong>Data di Conseguimento:</strong> {{ $enrollment->certificate_issued_at ? $enrollment->certificate_issued_at->format('d/m/Y') : now()->format('d/m/Y') }}
            </div>
        </div>

        <!-- Download Button -->
        <p style="margin: 28px 0 24px 0; text-align: center;">
            <a href="{{ route('student.enrollments.certificate', $enrollment) }}"
               style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; font-weight: bold; padding: 14px 32px; border-radius: 12px; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                🎓 Scarica Attestato Ufficiale (PDF)
            </a>
        </p>

        <p style="font-size: 12px; color: #64748b; line-height: 1.5; text-align: center;">
            Il certificato è scaricabile in qualsiasi momento anche accedendo alla tua <a href="{{ route('student.dashboard') }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">Dashboard Personale</a>.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        
        <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0;">
            {{ $settings['provider_name'] ?? 'Ente di Formazione Accreditato' }} • Sede: {{ $settings['provider_address'] ?? '' }}<br>
            Per qualsiasi richiesta o informazione scrivi a: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}</a>
        </p>
    </div>
</body>
</html>
