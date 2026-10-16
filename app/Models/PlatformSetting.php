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
        ];

        try {
            $stored = static::query()->pluck('value', 'key')->toArray();
            return array_merge($defaults, $stored);
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    public static function setValues(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Cache::forget('platform_settings');
    }
}
