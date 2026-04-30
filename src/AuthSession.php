<?php

namespace App;

class AuthSession
{
    private const SESSION_NAME = 'jira_batman_sess';

    /**
     * Inicia la sesión con cookies seguras. Idempotente: si ya está activa, no hace nada.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (PHP_VERSION_ID >= 70300) {
            ini_set('session.use_strict_mode', '1');
        }

        session_start();
    }

    /**
     * Prefijo de URL de la app. Default '/'. Si la app está en subdirectorio
     * (ej: /jira-batman/), se configura con APP_BASE_URL en .env.
     * Siempre termina con '/'.
     */
    public static function appBaseUrl(): string
    {
        $url = $_ENV['APP_BASE_URL'] ?? '/';
        if ($url === '') {
            return '/';
        }
        return str_ends_with($url, '/') ? $url : ($url . '/');
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return false;
    }

    public static function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    public static function consumeState(string $received): bool
    {
        $expected = $_SESSION['oauth_state'] ?? null;
        unset($_SESSION['oauth_state']);
        return is_string($expected) && hash_equals($expected, $received);
    }

    /**
     * Persiste los tokens y el sitio Jira asociado tras un intercambio o refresh.
     * @param array $tokens respuesta cruda del endpoint /oauth/token
     */
    public static function saveOAuthTokens(array $tokens, string $cloudId, string $cloudUrl, string $cloudName = ''): void
    {
        $_SESSION['oauth'] = [
            'access_token'  => $tokens['access_token'] ?? '',
            'refresh_token' => $tokens['refresh_token'] ?? ($_SESSION['oauth']['refresh_token'] ?? ''),
            'expires_at'    => time() + (int) ($tokens['expires_in'] ?? 3600),
            'cloud_id'      => $cloudId,
            'cloud_url'     => rtrim($cloudUrl, '/'),
            'cloud_name'    => $cloudName,
        ];
    }

    public static function getOAuth(): ?array
    {
        return $_SESSION['oauth'] ?? null;
    }

    public static function clearOAuth(): void
    {
        unset($_SESSION['oauth']);
    }

    /**
     * Asegura que el access_token está vigente. Refresca si está próximo a expirar.
     * Devuelve true si el token quedó listo, false si no había sesión o el refresh falló.
     */
    public static function ensureFreshAccessToken(OAuthClient $oauth): bool
    {
        $tokens = $_SESSION['oauth'] ?? null;
        if (!$tokens || empty($tokens['access_token'])) {
            return false;
        }

        // Margen de 60s para evitar usar un token que expira en mitad de la petición.
        if (($tokens['expires_at'] ?? 0) > time() + 60) {
            return true;
        }

        if (empty($tokens['refresh_token'])) {
            self::clearOAuth();
            return false;
        }

        try {
            $new = $oauth->refreshAccessToken($tokens['refresh_token']);
            $_SESSION['oauth']['access_token'] = $new['access_token'] ?? '';
            if (!empty($new['refresh_token'])) {
                $_SESSION['oauth']['refresh_token'] = $new['refresh_token'];
            }
            $_SESSION['oauth']['expires_at'] = time() + (int) ($new['expires_in'] ?? 3600);
            return !empty($_SESSION['oauth']['access_token']);
        } catch (\Throwable $e) {
            self::clearOAuth();
            return false;
        }
    }

    /**
     * Cache en sesión del /myself con TTL. Evita una request por cada carga.
     */
    public static function cachedMyself(JiraClient $client, int $ttlSeconds = 3600): array
    {
        $cached = $_SESSION['cached_me'] ?? null;
        if (is_array($cached) && (time() - ($cached['ts'] ?? 0)) < $ttlSeconds) {
            return $cached['data'];
        }
        $me = $client->getMyself();
        $_SESSION['cached_me'] = ['ts' => time(), 'data' => $me];
        return $me;
    }

    public static function clearMyselfCache(): void
    {
        unset($_SESSION['cached_me']);
    }

    /**
     * Cache de reporte por (startDate, endDate). Devuelve la copia cacheada
     * si está fresca, si no llama a $generator y la guarda.
     */
    public static function cachedReport(string $cacheKey, int $ttlSeconds, callable $generator): array
    {
        $cached = $_SESSION['report_cache'][$cacheKey] ?? null;
        if (is_array($cached) && (time() - ($cached['ts'] ?? 0)) < $ttlSeconds) {
            return $cached['data'];
        }
        $data = $generator();
        $_SESSION['report_cache'][$cacheKey] = ['ts' => time(), 'data' => $data];
        // Mantener solo los últimos 5 reportes en sesión.
        if (isset($_SESSION['report_cache']) && count($_SESSION['report_cache']) > 5) {
            uasort($_SESSION['report_cache'], fn($a, $b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
            $_SESSION['report_cache'] = array_slice($_SESSION['report_cache'], 0, 5, true);
        }
        return $data;
    }

    public static function clearReportCache(): void
    {
        unset($_SESSION['report_cache']);
    }

    /**
     * Destruye la sesión completa (logout).
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
        session_destroy();
    }
}
