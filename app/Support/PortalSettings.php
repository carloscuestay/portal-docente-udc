<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Ajustes del portal que el docente cambia desde la interfaz (sin editar el .env).
 * Se guardan en storage/app/portal-settings.json y se aplican sobre config('portal.*') al arrancar.
 */
class PortalSettings
{
    public static function path(): string
    {
        return storage_path('app/portal-settings.json');
    }

    /** @return array{open_registration: bool, institutional_domain: string} */
    public static function all(): array
    {
        $saved = File::exists(self::path()) ? (json_decode((string) File::get(self::path()), true) ?: []) : [];
        $envDomain = (string) config('portal.env_institutional_domain', '');

        return [
            'open_registration' => (bool) ($saved['open_registration'] ?? $envDomain === ''),
            'institutional_domain' => strtolower(trim((string) ($saved['institutional_domain'] ?? $envDomain))),
        ];
    }

    public static function save(bool $openRegistration, string $domain): void
    {
        File::ensureDirectoryExists(dirname(self::path()));
        File::put(self::path(), json_encode([
            'open_registration' => $openRegistration,
            'institutional_domain' => strtolower(trim($domain)),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        self::apply();
    }

    /** Registro abierto = cualquier correo (dominio vacío); si no, solo el dominio institucional. */
    public static function apply(): void
    {
        if (! config()->has('portal.env_institutional_domain')) {
            config(['portal.env_institutional_domain' => (string) config('portal.institutional_domain', '')]);
        }
        if (! File::exists(self::path())) {
            return;
        }
        $s = self::all();
        config(['portal.institutional_domain' => $s['open_registration'] ? '' : $s['institutional_domain']]);
    }
}
