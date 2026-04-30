<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\AuthSession;
use App\JiraClient;
use App\OAuthClient;
use App\WorklogReport;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$timezone = $_ENV['TIMEZONE'] ?? 'America/Mexico_City';
$hoursPerDay = (float) ($_ENV['HOURS_PER_DAY'] ?? 8);
date_default_timezone_set($timezone);

$jiraBaseUrlConfig = rtrim($_ENV['JIRA_BASE_URL'] ?? 'https://sibamex.atlassian.net', '/');
$oauthAvailable = OAuthClient::isConfigured();

AuthSession::start();

// Resolver método de autenticación: OAuth tiene prioridad si hay sesión.
$authMethod = null;
$client = null;
$jiraBaseUrl = $jiraBaseUrlConfig;
$oauthSession = null;

if ($oauthAvailable && AuthSession::getOAuth()) {
    try {
        $oauth = OAuthClient::fromEnv();
        if (AuthSession::ensureFreshAccessToken($oauth)) {
            $oauthSession = AuthSession::getOAuth();
            $client = JiraClient::fromOAuth($oauthSession['cloud_id'], $oauthSession['access_token']);
            $jiraBaseUrl = $oauthSession['cloud_url'] ?: $jiraBaseUrlConfig;
            $authMethod = 'oauth';
        }
    } catch (\Throwable $e) {
        $authMethod = null;
    }
}

// Fallback: cookies/env con email + API token
$cookieEmail = isset($_COOKIE['jira_email']) && $_COOKIE['jira_email'] !== '' ? $_COOKIE['jira_email'] : null;
$cookieToken = isset($_COOKIE['jira_token']) && $_COOKIE['jira_token'] !== '' ? $_COOKIE['jira_token'] : null;
$jiraEmail = $cookieEmail ?? ($_ENV['JIRA_EMAIL'] ?? '');
$jiraToken = $cookieToken ?? ($_ENV['JIRA_API_TOKEN'] ?? '');
$hasToken = !empty($jiraToken);

if (!$client && !empty($jiraEmail) && !empty($jiraToken)) {
    $client = JiraClient::fromBasic($jiraBaseUrlConfig, $jiraEmail, $jiraToken);
    $jiraBaseUrl = $jiraBaseUrlConfig;
    $authMethod = 'basic';
}

$needsSetup = $client === null;

$rangeType = $_GET['range'] ?? 'month';
$customStart = $_GET['start'] ?? null;
$customEnd = $_GET['end'] ?? null;

$today = new DateTime('now', new DateTimeZone($timezone));

switch ($rangeType) {
    case 'today':
        $startDate = $today->format('Y-m-d');
        $endDate = $startDate;
        break;
    case 'week':
        $dayOfWeek = (int) $today->format('N');
        $monday = (clone $today)->modify('-' . ($dayOfWeek - 1) . ' days');
        $friday = (clone $monday)->modify('+4 days');
        $startDate = $monday->format('Y-m-d');
        $endDate = min($friday->format('Y-m-d'), $today->format('Y-m-d'));
        break;
    case 'custom':
        $startDate = $customStart ?? $today->format('Y-m-d');
        $endDate = $customEnd ?? $today->format('Y-m-d');
        break;
    case 'month':
    default:
        $rangeType = 'month';
        $startDate = (clone $today)->modify('-1 month')->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        break;
}

$error = null;
$authError = false;
$authErrorMethod = null;
$report = null;
$displayName = '';

if (!$needsSetup) {
    try {
        $cachedMe = AuthSession::cachedMyself($client);
        $worklogReport = new WorklogReport($client, $timezone, $hoursPerDay, $cachedMe);
        $displayName = $worklogReport->getAccountDisplayName();

        $cacheKey = "{$startDate}__{$endDate}__" . ($cachedMe['accountId'] ?? '');
        $cacheTtl = (int) ($_GET['nocache'] ?? 0) === 1 ? 0 : 90;
        $report = AuthSession::cachedReport($cacheKey, $cacheTtl, function() use ($worklogReport, $startDate, $endDate) {
            return $worklogReport->generate($startDate, $endDate);
        });
    } catch (\Throwable $e) {
        if (in_array($e->getCode(), [401, 403], true)) {
            $authError = true;
            $authErrorMethod = $authMethod;
            AuthSession::clearMyselfCache();
            // Si fue OAuth, el token está revocado o expiró sin refresh válido: limpiar.
            if ($authMethod === 'oauth') {
                AuthSession::clearOAuth();
                $authMethod = null;
                $oauthSession = null;
            }
            $needsSetup = true;
        } else {
            $error = $e->getMessage();
        }
    }
}

// CSV export
if (!$needsSetup && ($_GET['export'] ?? '') === 'csv' && $report) {
    $filename = 'worklog_' . $startDate . '_' . $endDate . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
    fputcsv($out, ['Fecha', 'Día', 'Clave', 'Proyecto', 'Tarea', 'Estado', 'Inicio', 'Horas']);
    foreach ($report['days'] as $day) {
        foreach ($day['worklogs'] as $wl) {
            fputcsv($out, [
                $day['date'],
                $day['dayName'],
                $wl['issueKey'],
                $wl['project'],
                $wl['summary'],
                $wl['status'],
                $wl['started'],
                round($wl['timeSpentSeconds'] / 3600, 2),
            ]);
        }
    }
    fclose($out);
    exit;
}

// Manejar peticiones AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if ($needsSetup) {
        http_response_code(401);
        echo json_encode(['error' => 'Sin credenciales configuradas']);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';
    try {
        if ($action === 'compare') {
            $cmpStart = $body['startDate'] ?? '';
            $cmpEnd   = $body['endDate']   ?? '';
            $cmpCurrent = (float) ($body['currentTotal'] ?? 0);
            if (!$cmpStart || !$cmpEnd) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan startDate/endDate']);
                exit;
            }
            try {
                $rangeDays = (new DateTime($cmpEnd))->diff(new DateTime($cmpStart))->days + 1;
                $prevEnd   = (new DateTime($cmpStart, new DateTimeZone($timezone)))->modify('-1 day');
                $prevStart = (clone $prevEnd)->modify('-' . ($rangeDays - 1) . ' days');
                $cachedMe = AuthSession::cachedMyself($client);
                $report = new WorklogReport($client, $timezone, $hoursPerDay, $cachedMe);
                $prevTotal = $report->getPeriodTotalHours(
                    $prevStart->format('Y-m-d'),
                    $prevEnd->format('Y-m-d')
                );
                echo json_encode([
                    'ok' => true,
                    'previousLogged' => $prevTotal,
                    'delta' => round($cmpCurrent - $prevTotal, 2),
                ]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'list_assigned') {
            $issues = $client->getMyAssignedIssues(30);
            $out = [];
            foreach ($issues as $iss) {
                $out[] = [
                    'key'       => $iss['key'],
                    'summary'   => $iss['fields']['summary']        ?? '',
                    'status'    => $iss['fields']['status']['name'] ?? '',
                    'project'   => $iss['fields']['project']['name'] ?? '',
                    'issuetype' => $iss['fields']['issuetype']['name'] ?? '',
                    'priority'  => $iss['fields']['priority']['name'] ?? '',
                ];
            }
            echo json_encode(['ok' => true, 'issues' => $out]);
            exit;
        }
        if ($action === 'create_subtask') {
            $parentKey   = strtoupper(trim($body['parentKey'] ?? ''));
            $summary     = trim($body['summary'] ?? '');
            $description = trim($body['description'] ?? '');
            if (!$parentKey || !$summary) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan parentKey o summary']);
                exit;
            }
            $cachedMe = AuthSession::cachedMyself($client);
            $accountId = $cachedMe['accountId'] ?? null;
            $result = $client->createSubtaskAndTransition($parentKey, $summary, $description ?: null, $accountId);
            echo json_encode([
                'ok'             => true,
                'key'            => $result['key'],
                'transitionName' => $result['transition']['name'] ?? null,
                'transitionFailed' => isset($result['transition']['error']),
            ]);
            exit;
        }
        if ($action === 'pick_issue') {
            $query = trim($body['query'] ?? '');
            if ($query === '') {
                echo json_encode(['ok' => true, 'issues' => []]);
                exit;
            }
            [$issues, $debug] = $client->pickIssuesWithDebug($query, 8);
            echo json_encode(['ok' => true, 'issues' => $issues, '_debug' => $debug]);
            exit;
        }
        if ($action === 'add_worklog') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            $date     = $body['date'] ?? '';
            $time     = $body['time'] ?? '09:00';
            $duration = trim($body['duration'] ?? '');

            if (!$issueKey || !$date || !$duration) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos requeridos']);
                exit;
            }

            $seconds = 0;
            if (preg_match('/(\d+(?:\.\d+)?)\s*h/i', $duration, $m)) {
                $seconds += (int) round((float) $m[1] * 3600);
            }
            if (preg_match('/(\d+)\s*m/i', $duration, $m)) {
                $seconds += (int) $m[1] * 60;
            }
            if ($seconds <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Duración inválida. Usa formato como "2h 30m", "1h" o "45m"']);
                exit;
            }

            $dt      = new DateTime($date . ' ' . $time, new DateTimeZone($timezone));
            $started = $dt->format('Y-m-d\TH:i:s.000O');

            $wlResult = $client->addWorklog($issueKey, $started, $seconds);
            $issue    = $client->getIssue($issueKey);
            AuthSession::clearReportCache();
            echo json_encode([
                'ok'        => true,
                'worklogId' => $wlResult['id'] ?? '',
                'summary'   => $issue['fields']['summary'] ?? '',
                'project'   => $issue['fields']['project']['name'] ?? '',
                'status'    => $issue['fields']['status']['name'] ?? '',
            ]);
            exit;
        }
        if ($action === 'update_worklog') {
            $issueKey  = strtoupper(trim($body['issueKey'] ?? ''));
            $worklogId = trim($body['worklogId'] ?? '');
            $date      = $body['date'] ?? '';
            $time      = $body['time'] ?? '09:00';
            $duration  = trim($body['duration'] ?? '');

            if (!$issueKey || !$worklogId || !$date || !$duration) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos requeridos']);
                exit;
            }

            $seconds = 0;
            if (preg_match('/(\d+(?:\.\d+)?)\s*h/i', $duration, $m)) {
                $seconds += (int) round((float) $m[1] * 3600);
            }
            if (preg_match('/(\d+)\s*m/i', $duration, $m)) {
                $seconds += (int) $m[1] * 60;
            }
            if ($seconds <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Duración inválida. Usa formato como "2h 30m", "1h" o "45m"']);
                exit;
            }

            $dt      = new DateTime($date . ' ' . $time, new DateTimeZone($timezone));
            $started = $dt->format('Y-m-d\TH:i:s.000O');

            $client->updateWorklog($issueKey, $worklogId, $started, $seconds);
            AuthSession::clearReportCache();
            echo json_encode(['ok' => true]);
            exit;
        }
        if ($action === 'delete_worklog') {
            $issueKey  = strtoupper(trim($body['issueKey'] ?? ''));
            $worklogId = trim($body['worklogId'] ?? '');

            if (!$issueKey || !$worklogId) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos requeridos']);
                exit;
            }

            $client->deleteWorklog($issueKey, $worklogId);
            AuthSession::clearReportCache();
            echo json_encode(['ok' => true]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['error' => 'Acción desconocida']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/../templates/report.php';
