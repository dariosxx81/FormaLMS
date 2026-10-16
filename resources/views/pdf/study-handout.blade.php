<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>{{ $attachment->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; line-height: 1.6; font-size: 11pt; margin: 36px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 24px; }
        h1 { font-size: 16pt; color: #0f172a; margin: 8px 0 4px 0; }
        .box { background: #f8fafc; border: 1px solid #cbd5e1; padding: 16px; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 9pt; font-weight: bold; color: #4f46e5; text-transform: uppercase;">
            {{ $settings['provider_name'] }} — Dispensa Didattica Ufficiale
        </div>
        <h1>{{ $attachment->title }}</h1>
        <div style="font-size: 9.5pt; color: #475569;">
            Corso: <strong>[{{ $course->code }}] {{ $course->title }}</strong><br>
            Modulo {{ $module->sort_order }}: <strong>{{ $module->title }}</strong> • Docente: {{ $module->instructor_name }}
        </div>
    </div>

    <p>
        Sintesi didattica e riferimenti normativi relativi al <strong>Modulo {{ $module->sort_order }} ({{ $module->title }})</strong>,
        erogato nell'ambito del percorso formativo autorizzato dal Ministero del Turismo ai sensi del D.M. 26 giugno 2024, n. 88
        e della Legge 13 dicembre 2023, n. 190.
    </p>

    <div class="box">
        <strong>Argomenti trattati nella videolezione:</strong>
        <p>{{ $module->description }}</p>
    </div>

    <p style="margin-top: 32px; font-size: 9pt; color: #64748b;">
        Copia personale scaricata e tracciata per il corsista: <strong>{{ $user->full_name }} (CF: {{ $user->fiscal_code }})</strong> in data {{ now()->format('d/m/Y H:i:s') }}.
    </p>
</body>
</html>
