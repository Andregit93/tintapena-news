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
                'social.instagram',
                'social.facebook',
                'seo.default_title',
                'seo.default_description',
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

    public static function instagram(): ?string
    {
        self::load();

        return self::getValidUrl('social.instagram');
    }

    public static function facebook(): ?string
    {
        self::load();

        return self::getValidUrl('social.facebook');
    }

    public static function defaultSeoTitle(): string
    {
        self::load();
        $value = self::$settings['seo.default_title'] ?? null;

        return filled($value) ? (string) $value : self::siteName().' - '.self::tagline();
    }

    public static function defaultSeoDescription(): string
    {
        self::load();
        $value = self::$settings['seo.default_description'] ?? null;

        return filled($value) ? (string) $value : self::siteName().' adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.';
    }

    protected static function getValidUrl(string $key): ?string
    {
        $value = self::$settings[$key] ?? null;

        if (blank($value)) {
            return null;
        }

        $value = (string) $value;

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (! in_array(strtolower($scheme ?? ''), ['http', 'https'], true)) {
            return null;
        }

        return $value;
    }
}
