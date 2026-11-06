<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Account Approvato — {{ $settings['platform_name'] ?? 'FormaLMS' }}</title>
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
                siamo lieti di comunicarti che la segreteria didattica ha revisionato e <strong>approvato con successo la tua registrazione</strong>. Il tuo account corsista è ora pienamente attivo e abilitato all'accesso!
            @endif
        </p>

        <!-- Profile Details Box -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 5px solid #16a34a; border-radius: 12px; padding: 18px; margin: 24px 0;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: bold; color: #15803d; letter-spacing: 0.5px;">
                Stato Profilo Aggiornato
            </div>
            <div style="font-size: 14px; font-weight: bold; color: #14532d; margin-top: 4px;">
                ✓ Account Verificato & Abilitato
            </div>
            <div style="font-size: 13px; color: #166534; margin-top: 8px; line-height: 1.5;">
                <strong>Email di Login:</strong> {{ $user->email }}<br>
                <strong>Codice Fiscale:</strong> {{ $user->fiscal_code }}<br>
                <strong>Data Approvazione:</strong> {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>

        <!-- Call to Action -->
        <p style="margin: 28px 0 24px 0; text-align: center;">
            <a href="{{ route('login') }}"
               style="display: inline-block; background-color: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: #ffffff; text-decoration: none; font-weight: bold; padding: 14px 30px; border-radius: 12px; font-size: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                Accedi alla Piattaforma &rarr;
            </a>
        </p>

        <p style="font-size: 12px; color: #64748b; line-height: 1.5; text-align: center;">
            Una volta effettuato l'accesso con la tua email e password, troverai nella tua dashboard i corsi a cui sei iscritto o potrai sbloccarne di nuovi con il codice fornito dalla segreteria.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        
        <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0;">
            {{ $settings['provider_name'] ?? 'Ente di Formazione' }} • Sede: {{ $settings['provider_address'] ?? '' }}<br>
            Per qualsiasi necessità scrivi a: <a href="mailto:{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}" style="color: {{ $settings['primary_color'] ?? '#4f46e5' }}; font-weight: bold;">{{ $settings['support_email'] ?? 'segreteria@formacamera.it' }}</a>
        </p>
    </div>
</body>
</html>
