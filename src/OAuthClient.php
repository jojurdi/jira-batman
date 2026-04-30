<?php

namespace App;

class OAuthClient
{
    private const AUTHORIZE_URL = 'https://auth.atlassian.com/authorize';
    private const TOKEN_URL = 'https://auth.atlassian.com/oauth/token';
    private const RESOURCES_URL = 'https://api.atlassian.com/oauth/token/accessible-resources';

    private const SCOPES = [
        'read:me',
        'read:jira-user',
        'read:jira-work',
        'write:jira-work',
        'offline_access',
    ];

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri,
    ) {}

    /**
     * Construye un OAuthClient leyendo las variables de entorno.
     * Lanza si falta cualquier valor.
     */
    public static function fromEnv(): self
    {
        $id     = $_ENV['JIRA_OAUTH_CLIENT_ID']     ?? '';
        $secret = $_ENV['JIRA_OAUTH_CLIENT_SECRET'] ?? '';
        $uri    = $_ENV['JIRA_OAUTH_REDIRECT_URI']  ?? '';

        if (!$id || !$secret || !$uri) {
            throw new \RuntimeException(
                'OAuth no configurado. Añade JIRA_OAUTH_CLIENT_ID, JIRA_OAUTH_CLIENT_SECRET '
                . 'y JIRA_OAUTH_REDIRECT_URI en .env'
            );
        }

        return new self($id, $secret, $uri);
    }

    public static function isConfigured(): bool
    {
        return !empty($_ENV['JIRA_OAUTH_CLIENT_ID'])
            && !empty($_ENV['JIRA_OAUTH_CLIENT_SECRET'])
            && !empty($_ENV['JIRA_OAUTH_REDIRECT_URI']);
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'audience'      => 'api.atlassian.com',
            'client_id'     => $this->clientId,
            'scope'         => implode(' ', self::SCOPES),
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'response_type' => 'code',
            'prompt'        => 'consent',
        ];

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    /**
     * Intercambia el authorization code por access_token + refresh_token.
     * Devuelve: ['access_token', 'refresh_token', 'expires_in', 'scope', 'token_type'].
     */
    public function exchangeCodeForTokens(string $code): array
    {
        return $this->postJson(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
        ]);
    }

    /**
     * Atlassian rota el refresh_token: la respuesta incluye un nuevo refresh_token
     * que reemplaza al anterior.
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->postJson(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Lista los Jira Cloud sites a los que el usuario autorizó esta app.
     * Cada item: ['id' => cloudId, 'url' => 'https://xxx.atlassian.net', 'name' => '...', 'scopes' => [...]].
     */
    public function getAccessibleResources(string $accessToken): array
    {
        $result = $this->executeWithProxyFallback(self::RESOURCES_URL, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
        ]);

        if ($result['error']) {
            throw new \RuntimeException("Error de cURL: {$result['error']}");
        }
        if ($result['httpCode'] >= 400) {
            throw new \RuntimeException("No se pudieron listar recursos accesibles (HTTP {$result['httpCode']})");
        }

        $data = json_decode($result['response'], true);
        return is_array($data) ? $data : [];
    }

    private function postJson(string $url, array $payload): array
    {
        $result = $this->executeWithProxyFallback($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        if ($result['error']) {
            throw new \RuntimeException("Error de cURL: {$result['error']}");
        }

        $data = json_decode($result['response'], true);

        if ($result['httpCode'] >= 400) {
            $msg = $data['error_description'] ?? $data['error'] ?? "HTTP {$result['httpCode']}";
            throw new \RuntimeException("Error de OAuth: {$msg}");
        }

        return is_array($data) ? $data : [];
    }

    private function executeWithProxyFallback(string $url, array $extraOptions): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $base = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        $options = $extraOptions + $base;

        $attemptModes = ['no_proxy', 'default'];
        $last = ['response' => false, 'httpCode' => 0, 'error' => ''];

        foreach ($attemptModes as $mode) {
            $attemptOptions = $options;

            if ($mode === 'no_proxy' && $host !== '') {
                $attemptOptions[CURLOPT_NOPROXY] = $host;
            } else {
                unset($attemptOptions[CURLOPT_NOPROXY]);
            }

            $ch = curl_init();
            curl_setopt_array($ch, $attemptOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $last = ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];

            if ($error === '') {
                return $last;
            }

            $isConnectTunnelError = stripos($error, 'CONNECT tunnel failed') !== false;
            $isHostResolveError   = stripos($error, 'Could not resolve host') !== false;

            if ($mode === 'no_proxy' && $isHostResolveError) {
                continue;
            }
            if ($mode === 'default' && $isConnectTunnelError) {
                continue;
            }

            break;
        }

        return $last;
    }
}
