<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Registrazione Corsista — {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</title>
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
                ti confermiamo che il tuo profilo anagrafico è stato registrato con successo sulla nostra piattaforma di Formazione a Distanza di Formacamera.
            @endif
        </p>

        <div style="background-color: #f1f5f9; border-radius: 12px; padding: 16px; margin: 20px 0;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #64748b; margin-bottom: 8px;">
                Riepilogo Credenziali di Accesso
            </div>
            <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;">
                <strong>Indirizzo Email:</strong> {{ $user->email }}
            </div>
            <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;">
                <strong>Codice Fiscale:</strong> {{ $user->fiscal_code }}
            </div>
            <div style="font-size: 13px; color: #1e293b;">
                <strong>Stato Profilo:</strong> {{ $user->status === 'active' ? 'Attivo & Abilitato' : 'In attesa di approvazione della segreteria' }}
            </div>
        </div>

        @if($course)
            <div style="border-left: 4px solid {{ $settings['primary_color'] ?? '#4f46e5' }}; padding-left: 14px; margin: 20px 0;">
                <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: {{ $settings['primary_color'] ?? '#4f46e5' }};">
                    Corso Assegnato Contestualmente
                </div>
                <div style="font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                    {{ $course->title }} ({{ $course->code }})
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Monte ore totale: {{ $course->total_hours }}h • Soglia attestato: {{ $course->min_attendance_percent }}%
                </div>
            </div>
        @endif

        <p style="margin: 28px 0 24px 0; text-align: center;">
            <a href="{{ route('login') }}"
               style="display: inline-block; background-color: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: #ffffff; text-decoration: none; font-weight: bold; padding: 13px 28px; border-radius: 12px; font-size: 14px;">
                Accedi alla tua Area Riservata &rarr;
            </a>
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        
        <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0;">
            {{ $settings['provider_name'] ?? 'Ente di Formazione' }} • {{ $settings['provider_address'] ?? '' }}<br>
            Per assistenza tecnica o didattica contatta: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}</a>
        </p>
    </div>
</body>
</html>
