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
            'logo_bg' => 'white',      // 'white' | 'dark' | 'transparent'
            'logo_size' => 'md',       // 'sm' | 'md' | 'lg'
            'primary_color' => '#4f46e5',
            'secondary_color' => '#0f172a',
            'accent_color' => '#10b981',
        ];

        try {
            $stored = static::query()->pluck('value', 'key')->toArray();
            $merged = array_merge($defaults, $stored);
        } catch (\Throwable $e) {
            $merged = $defaults;
        }

        // Compute derived WCAG contrast & tint tokens
        $primary = self::normalizeHex($merged['primary_color'] ?? '#4f46e5', '#4f46e5');
        $secondary = self::normalizeHex($merged['secondary_color'] ?? '#0f172a', '#0f172a');
        $accent = self::normalizeHex($merged['accent_color'] ?? '#10b981', '#10b981');

        $merged['primary_color'] = $primary;
        $merged['secondary_color'] = $secondary;
        $merged['accent_color'] = $accent;

        $merged['primary_contrast'] = self::getContrastText($primary);
        $merged['secondary_contrast'] = self::getContrastText($secondary);
        $merged['accent_contrast'] = self::getContrastText($accent);

        $merged['primary_readable_on_light'] = self::getReadableColorOnWhite($primary);
        $merged['accent_readable_on_light'] = self::getReadableColorOnWhite($accent);

        $merged['primary_soft_bg'] = self::hexToRgba($primary, 0.10);
        $merged['primary_soft_border'] = self::hexToRgba($primary, 0.26);
        $merged['accent_soft_bg'] = self::hexToRgba($accent, 0.12);
        $merged['accent_soft_border'] = self::hexToRgba($accent, 0.28);

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

    /**
     * Returns '#ffffff' or '#0f172a' based on WCAG relative luminance of background hex.
     */
    public static function getContrastText(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        // Perceived brightness (YIQ formula)
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return ($yiq >= 155) ? '#0f172a' : '#ffffff';
    }

    /**
     * If a brand color is too bright to read as text on a white background,
     * darkens it automatically so text links/badges are always legible.
     */
    public static function getReadableColorOnWhite(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        if ($yiq <= 165) {
            return $hex;
        }
        // Darken channels by 45% to guarantee readability on white/light backgrounds
        $dr = max(0, (int) round($r * 0.50));
        $dg = max(0, (int) round($g * 0.50));
        $db = max(0, (int) round($b * 0.50));
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
