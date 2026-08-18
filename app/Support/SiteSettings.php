<?php

namespace App\Support;

use App\Models\Setting;

class SiteSettings
{
    protected static ?array $settings = null;

    protected static function load(): void
    {
        if (self::$settings === null) {
            self::$settings = Setting::whereIn('setting_key', [
                'general.site_name',
                'general.tagline',
            ])->pluck('value', 'setting_key')->toArray();
        }
    }

    public static function siteName(): string
    {
        self::load();
        $value = self::$settings['general.site_name'] ?? null;

        return filled($value) ? (string) $value : 'TINTAPENA';
    }

    public static function tagline(): string
    {
        self::load();
        $value = self::$settings['general.tagline'] ?? null;

        return filled($value) ? (string) $value : 'Menulis Berdasarkan Fakta';
    }
}
