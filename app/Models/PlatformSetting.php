<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getAllSettings(): array
    {
        $defaults = [
            'platform_name' => 'FormaFAD LMS',
            'platform_subtitle' => 'Piattaforma Telematica Certificata per la Formazione Professionale e Continua',
            'provider_name' => 'Ente di Formazione Accreditato',
            'provider_legal_rep' => 'Dott. Mario Rossi',
            'provider_address' => 'Via dell\'Umiltà 48, 00187 Roma (RM)',
            'ateco_code' => '79.90',
            'cp_istat_code' => '3.4.1.5.2',
            'support_email' => 'segreteria@enteformazione.it',
            'allow_public_registration' => '1',
            'auto_approve_registration' => '1',
            'logo_url' => '',
            'logo_bg' => 'white',                  // 'white' | 'dark' | 'transparent'
            'logo_size' => 'md',                   // 'sm' | 'md' | 'lg'
            'hide_brand_text_with_logo' => '0',    // '1' = mostra solo il logo grafico senza scritta accanto
            'primary_color' => '#4f46e5',
            'secondary_color' => '#1e1b4b',
            'accent_color' => '#10b981',
        ];

        try {
            $stored = static::query()->pluck('value', 'key')->toArray();
            $merged = array_merge($defaults, $stored);
        } catch (\Throwable $e) {
            $merged = $defaults;
        }

        // Normalize brand colors
        $primary = self::normalizeHex($merged['primary_color'] ?? '#4f46e5', '#4f46e5');
        $secondary = self::normalizeHex($merged['secondary_color'] ?? '#1e1b4b', '#1e1b4b');
        $accent = self::normalizeHex($merged['accent_color'] ?? '#10b981', '#10b981');

        // If secondary_color is still the old unbranded #0f172a and primary_color was customized,
        // automatically harmonize secondary_color to a deep shade of primary_color
        if ($secondary === '#0f172a' && $primary !== '#4f46e5') {
            $secondary = self::getDeepHarmonizedShade($primary, 0.24);
        }

        $merged['primary_color'] = $primary;
        $merged['secondary_color'] = $secondary;
        $merged['accent_color'] = $accent;

        // Tonal WCAG Contrast Font Colors (never generic #0f172a: uses deep shade of the brand color on light backgrounds)
        $merged['primary_contrast'] = self::getContrastText($primary);
        $merged['secondary_contrast'] = self::getContrastText($secondary);
        $merged['accent_contrast'] = self::getContrastText($accent);

        // Readable brand text on light cards
        $merged['primary_readable_on_light'] = self::getReadableColorOnWhite($primary);
        $merged['accent_readable_on_light'] = self::getReadableColorOnWhite($accent);

        // Soft 10% tints for badges & info boxes
        $merged['primary_soft_bg'] = self::hexToRgba($primary, 0.10);
        $merged['primary_soft_border'] = self::hexToRgba($primary, 0.26);
        $merged['accent_soft_bg'] = self::hexToRgba($accent, 0.12);
        $merged['accent_soft_border'] = self::hexToRgba($accent, 0.28);

        // Harmonized Secondary surface & font tokens for Banners, Cards & Buttons (.bg-slate-900 / .bg-slate-800)
        $secIsLight = self::getYiq($secondary) >= 155;
        $merged['secondary_muted'] = $secIsLight
            ? self::mixHex($secondary, self::getDeepHarmonizedShade($secondary, 0.16), 0.78)
            : self::mixHex($secondary, '#ffffff', 0.78);
        $merged['secondary_surface'] = $secIsLight
            ? self::hexToRgba(self::getDeepHarmonizedShade($secondary, 0.20), 0.08)
            : 'rgba(255, 255, 255, 0.11)';
        $merged['secondary_border'] = $secIsLight
            ? self::hexToRgba(self::getDeepHarmonizedShade($secondary, 0.20), 0.18)
            : 'rgba(255, 255, 255, 0.20)';
        $merged['secondary_highlight'] = $secIsLight
            ? self::getReadableColorOnWhite($primary)
            : self::mixHex($primary, '#ffffff', 0.62);

        // Harmonized deep heading & body colors on white cards (replaces cold #0f172a)
        $merged['brand_heading'] = self::getDeepHarmonizedShade($secondary, 0.22);
        $merged['brand_body'] = self::getDeepHarmonizedShade($secondary, 0.32);

        return $merged;
    }

    public static function setValues(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Cache::forget('platform_settings');
    }

    public static function normalizeHex(string $hex, string $fallback = '#4f46e5'): string
    {
        $hex = trim($hex);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return strtolower($hex);
        }
        return $fallback;
    }

    public static function getYiq(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    }

    /**
     * Returns harmonized light text ('#ffffff') on dark/medium backgrounds,
     * or a deep tonally-coordinated shade of the brand color on bright backgrounds.
     */
    public static function getContrastText(string $hex): string
    {
        if (self::getYiq($hex) >= 155) {
            return self::getDeepHarmonizedShade($hex, 0.18);
        }
        return '#ffffff';
    }

    /**
     * Generates a deep, high-contrast shade harmonized with the given brand color
     * so dark fonts/headings/surfaces match the chosen brand hue instead of fixed #0f172a.
     */
    public static function getDeepHarmonizedShade(string $hex, float $factor = 0.20): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        // Preserve the hue saturation while bringing brightness down for strong contrast
        $dr = max(6, min(55, (int) round($r * $factor)));
        $dg = max(8, min(55, (int) round($g * $factor)));
        $db = max(12, min(65, (int) round($b * $factor)));
        return sprintf('#%02x%02x%02x', $dr, $dg, $db);
    }

    /**
     * Mixes two hex colors by $weight (0.0 = 100% $hex1, 1.0 = 100% $hex2).
     */
    public static function mixHex(string $hex1, string $hex2, float $weight = 0.5): string
    {
        [$r1, $g1, $b1] = self::hexToRgb($hex1);
        [$r2, $g2, $b2] = self::hexToRgb($hex2);
        $r = (int) round($r1 * (1 - $weight) + $r2 * $weight);
        $g = (int) round($g1 * (1 - $weight) + $g2 * $weight);
        $b = (int) round($b1 * (1 - $weight) + $b2 * $weight);
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    /**
     * If a brand color is too bright to read as text on a white background,
     * darkens it automatically while keeping its hue.
     */
    public static function getReadableColorOnWhite(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        if ($yiq <= 160) {
            return $hex;
        }
        $dr = max(0, (int) round($r * 0.48));
        $dg = max(0, (int) round($g * 0.48));
        $db = max(0, (int) round($b * 0.48));
        return sprintf('#%02x%02x%02x', $dr, $dg, $db);
    }

    public static function hexToRgba(string $hex, float $alpha = 0.10): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex), '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
