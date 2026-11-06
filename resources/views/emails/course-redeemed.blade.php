<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Corso Sbloccato — {{ $settings['platform_name'] ?? 'FormaLMS' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; padding: 24px; margin: 0;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        @include('emails.partials.header')

        <p style="font-size: 15px; line-height: 1.6; color: #334155;">
            Gentile <strong>{{ $user->full_name }}</strong>,
        </p>

        <p style="font-size: 14px; line-height: 1.6; color: #334155;">
            @if(!empty($customBody))
                {!! nl2br(e($customBody)) !!}
            @else
                ti confermiamo che il riscatto del corso formativo è avvenuto con successo tramite il tuo Codice di Accesso. Il percorso è ora attivo e pronto per la fruizione sulla tua dashboard!
            @endif
        </p>

        <!-- Course Summary Box -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 5px solid {{ $settings['primary_color'] ?? '#4f46e5' }}; border-radius: 12px; padding: 20px; margin: 24px 0;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: {{ $settings['primary_color'] ?? '#4f46e5' }}; letter-spacing: 0.5px;">
                Dettagli del Corso Sbloccato
            </div>
            <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 6px;">
                [{{ $course->code }}] {{ $course->title }}
            </div>
            <div style="font-size: 13px; color: #475569; margin-top: 6px; line-height: 1.5;">
                <strong>Tipologia:</strong> {{ $course->has_sync_hours ? 'Ibrido (Zoom + FAD Asincrona)' : '100% Piattaforma FAD Asincrona' }}<br>
                <strong>Monte Ore Complessivo:</strong> {{ $course->total_hours }} ore<br>
                <strong>Soglia Minima per Attestato:</strong> {{ $course->min_attendance_percent }}% (pari a {{ $course->minimum_required_hours }}h)<br>
                @if($accessCode)
                    <strong>Codice Utilizzato:</strong> <span style="font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">{{ $accessCode }}</span>
                @endif
            </div>
        </div>

        <!-- Call to Action -->
        <p style="margin: 28px 0 24px 0; text-align: center;">
            <a href="{{ route('student.courses.show', $course) }}"
               style="display: inline-block; background-color: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: #ffffff; text-decoration: none; font-weight: bold; padding: 14px 30px; border-radius: 12px; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                &#9654; Accedi al Corso & Inizia le Lezioni
            </a>
        </p>

        <p style="font-size: 12px; color: #64748b; line-height: 1.5; text-align: center;">
            Ricorda che per maturare validamente le ore, il tracciamento telemetrico anti-skip della piattaforma monitorerà i tempi effettivi di visualizzazione dei moduli video.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        
        <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0;">
            {{ $settings['provider_name'] ?? 'Ente di Formazione' }} • Sede: {{ $settings['provider_address'] ?? '' }}<br>
            Per qualsiasi dubbio o informazione contatta la segreteria didattica: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}</a>
        </p>
    </div>
</body>
</html>
