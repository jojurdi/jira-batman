<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\AuthSession;
use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
AuthSession::start();
AuthSession::destroy();

header('Location: ' . AuthSession::appBaseUrl(), true, 302);
exit;
