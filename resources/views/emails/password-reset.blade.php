<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recupero Password — {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; padding: 24px; margin: 0;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 20px;">
            {{ $settings['platform_name'] ?? 'FormaFAD LMS' }}
        </h2>

        <p style="font-size: 14px; line-height: 1.6; color: #334155;">
            Gentile <strong>{{ $user->full_name }}</strong>,
        </p>

        @if($mode === 'link' && $resetUrl)
            <p style="font-size: 14px; line-height: 1.6; color: #334155;">
                Abbiamo ricevuto una richiesta di reimpostazione della password per il tuo account corsista associato all'indirizzo <strong>{{ $user->email }}</strong>.
            </p>
            <p style="margin: 24px 0;">
                <a href="{{ $resetUrl }}"
                   style="display: inline-block; background-color: {{ $settings['primary_color'] ?? '#4f46e5' }}; color: {{ $settings['primary_contrast'] ?? '#ffffff' }}; text-decoration: none; font-weight: bold; padding: 12px 24px; border-radius: 10px; font-size: 14px;">
                    Reimposta la tua Password &rarr;
                </a>
            </p>
            <p style="font-size: 12px; color: #64748b; word-break: break-all;">
                Se il pulsante non dovesse funzionare, copia e incolla questo link nel browser:<br>
                <a href="{{ $resetUrl }}" style="color: #4f46e5;">{{ $resetUrl }}</a>
            </p>
        @else
            <p style="font-size: 14px; line-height: 1.6; color: #334155;">
                Ti confermiamo che la password del tuo account (<strong>{{ $user->email }}</strong>) è stata aggiornata con successo.
            </p>
            <p style="font-size: 13px; color: #64748b;">
                Ora puoi effettuare il login in piattaforma utilizzando la tua nuova password.
            </p>
        @endif

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        <p style="font-size: 11px; color: #94a3b8; margin: 0;">
            {{ $settings['platform_name'] ?? 'FormaFAD LMS' }} • {{ $settings['legal_entity'] ?? '' }}
        </p>
    </div>
</body>
</html>
