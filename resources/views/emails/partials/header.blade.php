@php
    $settings = !empty($settings) ? $settings : \App\Models\PlatformSetting::getAllSettings();
    $logoUrl = !empty($settings['logo_url'])
        ? (str_starts_with($settings['logo_url'], 'http') ? $settings['logo_url'] : url($settings['logo_url']))
        : null;
    $primaryColor = $settings['primary_color'] ?? '#a30000';
    $platformName = $settings['platform_name'] ?? 'FormaCamera';
    $providerName = $settings['provider_name'] ?? 'Ente di Formazione Accreditato';
    $hideTextWithLogo = !empty($settings['hide_brand_text_with_logo']) && $settings['hide_brand_text_with_logo'] == '1';
@endphp

<!-- Header con Logo della Piattaforma -->
<div style="border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 18px; margin-bottom: 24px;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width: 100%;">
        <tr>
            <td style="vertical-align: middle;">
                @if($logoUrl)
                    <div style="margin-bottom: {{ $hideTextWithLogo ? '0' : '8px' }};">
                        <img src="{{ $logoUrl }}" 
                             alt="{{ $platformName }}" 
                             style="max-height: 54px; max-width: 220px; height: auto; width: auto; display: block; border: 0; outline: none; text-decoration: none;">
                    </div>
                @endif
                @if(!$logoUrl || !$hideTextWithLogo)
                    <h2 style="margin: 0; color: #0f172a; font-size: 20px; font-weight: 800; line-height: 1.2;">
                        {{ $platformName }}
                    </h2>
                @endif
                <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500;">
                    {{ $providerName }}
                </div>
            </td>
        </tr>
    </table>
</div>
