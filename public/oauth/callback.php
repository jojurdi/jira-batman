<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\AuthSession;
use App\OAuthClient;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
AuthSession::start();

function abort(string $msg, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    $safe = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Error de autenticación</title>";
    echo "<style>body{font-family:-apple-system,sans-serif;max-width:480px;margin:3rem auto;padding:0 1.5rem;color:#333;}";
    echo "h1{font-size:1.1rem;}p{color:#666;font-size:0.9rem;}a{color:#0052cc;}</style></head><body>";
    echo "<h1>No se pudo iniciar sesión</h1><p>{$safe}</p><p><a href='/'>← Volver</a></p>";
    echo "</body></html>";
    exit;
}

if (isset($_GET['error'])) {
    $desc = $_GET['error_description'] ?? $_GET['error'];
    abort('Atlassian rechazó la autorización: ' . $desc);
}

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !$state) {
    abort('Faltan parámetros code o state en la respuesta.');
}

if (!AuthSession::consumeState($state)) {
    abort('El parámetro state no coincide. Reintenta el inicio de sesión.', 403);
}

try {
    $oauth = OAuthClient::fromEnv();
    $tokens = $oauth->exchangeCodeForTokens($code);
} catch (\Throwable $e) {
    abort('Error al intercambiar el código: ' . $e->getMessage(), 500);
}

$accessToken = $tokens['access_token'] ?? '';
if (!$accessToken) {
    abort('Atlassian no devolvió un access_token.');
}

try {
    $resources = $oauth->getAccessibleResources($accessToken);
} catch (\Throwable $e) {
    abort('Error al consultar los sitios accesibles: ' . $e->getMessage(), 500);
}

if (empty($resources)) {
    abort('Tu cuenta no tiene acceso a ningún sitio de Jira con esta app.');
}

// Elegir el site que coincida con JIRA_BASE_URL del .env. Si no hay match, error claro.
$expectedBaseUrl = rtrim($_ENV['JIRA_BASE_URL'] ?? '', '/');
$chosen = null;
if ($expectedBaseUrl !== '') {
    foreach ($resources as $r) {
        if (rtrim($r['url'] ?? '', '/') === $expectedBaseUrl) {
            $chosen = $r;
            break;
        }
    }
} else {
    $chosen = $resources[0];
}

if (!$chosen) {
    $available = array_map(fn($r) => $r['url'] ?? '', $resources);
    abort(
        'Tu cuenta no tiene acceso a ' . htmlspecialchars($expectedBaseUrl) . '. '
        . 'Sitios disponibles: ' . implode(', ', $available)
    );
}

AuthSession::saveOAuthTokens(
    $tokens,
    (string) ($chosen['id'] ?? ''),
    (string) ($chosen['url'] ?? ''),
    (string) ($chosen['name'] ?? '')
);

header('Location: ' . AuthSession::appBaseUrl(), true, 302);
exit;
