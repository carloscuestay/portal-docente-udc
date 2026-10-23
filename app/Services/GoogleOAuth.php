<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo de Google OAuth 2.0 / OpenID Connect (sin dependencias externas).
 */
class GoogleOAuth
{
    public function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function authUrl(string $state): string
    {
        $params = [
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ];
        $domain = config('portal.institutional_domain');
        if ($domain && ! config('portal.allow_external_teacher_login')) {
            $params['hd'] = $domain; // sugiere a Google mostrar solo cuentas del dominio
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    /** Intercambia el código por tokens y devuelve el perfil verificado del usuario. */
    public function user(string $code): array
    {
        $token = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (! $token->ok() || ! $token->json('access_token')) {
            throw new RuntimeException('No fue posible validar el inicio de sesión con Google.');
        }

        $profile = Http::withToken($token->json('access_token'))->timeout(15)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');
        if (! $profile->ok() || ! $profile->json('email') || ! $profile->json('email_verified')) {
            throw new RuntimeException('Google no devolvió un correo verificado.');
        }

        return [
            'id' => (string) $profile->json('sub'),
            'email' => strtolower($profile->json('email')),
            'name' => $profile->json('name') ?: $profile->json('email'),
            'avatar' => $profile->json('picture'),
            'hd' => $profile->json('hd'),
        ];
    }

    public function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }
}
