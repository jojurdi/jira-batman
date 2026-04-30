<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\AuthSession;
use App\OAuthClient;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
AuthSession::start();

try {
    $oauth = OAuthClient::fromEnv();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit;
}

$state = AuthSession::generateState();
$url = $oauth->getAuthorizationUrl($state);

header('Location: ' . $url, true, 302);
exit;
