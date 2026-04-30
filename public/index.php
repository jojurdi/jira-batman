<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\AuthSession;
use App\JiraApiException;
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

// Fallback: cookies del navegador (por usuario). El uso de credenciales del .env
// como fallback compartido está deshabilitado por defecto para evitar que cualquier
// visitante use la cuenta del servidor. Activar con ALLOW_ENV_FALLBACK=true solo
// en entornos de un solo usuario (desarrollo local).
$cookieEmail = isset($_COOKIE['jira_email']) && $_COOKIE['jira_email'] !== '' ? $_COOKIE['jira_email'] : null;
$cookieToken = isset($_COOKIE['jira_token']) && $_COOKIE['jira_token'] !== '' ? $_COOKIE['jira_token'] : null;
$allowEnvFallback = filter_var($_ENV['ALLOW_ENV_FALLBACK'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$envEmail = $allowEnvFallback ? ($_ENV['JIRA_EMAIL'] ?? '') : '';
$envToken = $allowEnvFallback ? ($_ENV['JIRA_API_TOKEN'] ?? '') : '';
$jiraEmail = $cookieEmail ?? $envEmail;
$jiraToken = $cookieToken ?? $envToken;
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
        if ($action === 'apply_transition') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            $transitionId = trim($body['transitionId'] ?? '');
            $extraFields  = is_array($body['extraFields'] ?? null) ? $body['extraFields'] : [];
            $comment      = trim((string) ($body['comment'] ?? ''));
            if (!$issueKey || !$transitionId) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan issueKey o transitionId']);
                exit;
            }
            try {
                $client->applyTransitionById($issueKey, $transitionId, $extraFields, $comment ?: null);
                $issue = $client->getIssue($issueKey);
                AuthSession::clearReportCache();
                echo json_encode([
                    'ok'     => true,
                    'status' => $issue['fields']['status']['name'] ?? '',
                ]);
            } catch (JiraApiException $e) {
                http_response_code($e->getCode() ?: 500);
                echo json_encode([
                    'error'        => $e->getMessage(),
                    'fieldErrors'  => $e->fieldErrors,
                    'issueKey'     => $issueKey,
                    'transitionId' => $transitionId,
                ]);
            } catch (\Throwable $e) {
                $code = (int) $e->getCode();
                http_response_code(($code >= 400 && $code < 600) ? $code : 500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'issue_fields_metadata') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            $keys     = is_array($body['fieldKeys'] ?? null) ? $body['fieldKeys'] : [];
            if (!$issueKey || empty($keys)) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan issueKey o fieldKeys']);
                exit;
            }
            try {
                $fields = $client->getIssueFieldsMetadata($issueKey, $keys);
                echo json_encode(['ok' => true, 'fields' => $fields]);
            } catch (\Throwable $e) {
                $code = (int) $e->getCode();
                http_response_code(($code >= 400 && $code < 600) ? $code : 500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'transition_required_fields') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            $transitionId = trim($body['transitionId'] ?? '');
            if (!$issueKey || !$transitionId) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan issueKey o transitionId']);
                exit;
            }
            try {
                $info = $client->getTransitionRequiredFields($issueKey, $transitionId);
                echo json_encode([
                    'ok'              => true,
                    'commentRequired' => $info['commentRequired'],
                    'fields'          => $info['fields'],
                ]);
            } catch (\Throwable $e) {
                $code = (int) $e->getCode();
                http_response_code(($code >= 400 && $code < 600) ? $code : 500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'get_transitions') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            if (!$issueKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta issueKey']);
                exit;
            }
            try {
                $transitions = $client->getIssueTransitions($issueKey);
                $simple = [];
                foreach ($transitions as $t) {
                    $simple[] = [
                        'id'     => $t['id'] ?? '',
                        'name'   => $t['name'] ?? '',
                        'toName' => $t['to']['name'] ?? '',
                        'toCategory' => $t['to']['statusCategory']['key'] ?? '',
                    ];
                }
                echo json_encode(['ok' => true, 'transitions' => $simple]);
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
                $desc = trim(JiraClient::adfToText($iss['fields']['description'] ?? null));
                $subs = [];
                foreach ($iss['fields']['subtasks'] ?? [] as $st) {
                    $subs[] = [
                        'key'       => $st['key'] ?? '',
                        'summary'   => $st['fields']['summary'] ?? '',
                        'status'    => $st['fields']['status']['name'] ?? '',
                        'issuetype' => $st['fields']['issuetype']['name'] ?? 'Sub-task',
                    ];
                }
                $out[] = [
                    'key'         => $iss['key'],
                    'summary'     => $iss['fields']['summary']        ?? '',
                    'status'      => $iss['fields']['status']['name'] ?? '',
                    'project'     => $iss['fields']['project']['name'] ?? '',
                    'issuetype'   => $iss['fields']['issuetype']['name'] ?? '',
                    'priority'    => $iss['fields']['priority']['name'] ?? '',
                    'description' => mb_strlen($desc) > 1000 ? mb_substr($desc, 0, 1000) . '…' : $desc,
                    'subtasks'    => $subs,
                ];
            }
            echo json_encode(['ok' => true, 'issues' => $out]);
            exit;
        }
        if ($action === 'list_worklogs') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            if (!$issueKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta issueKey']);
                exit;
            }
            try {
                $data = $client->getIssueWorklogs($issueKey);
                $cachedMe = AuthSession::cachedMyself($client);
                $myAccountId = $cachedMe['accountId'] ?? '';
                $tz = new DateTimeZone($timezone);
                $worklogs = [];
                foreach ($data['worklogs'] ?? [] as $wl) {
                    if (($wl['author']['accountId'] ?? '') !== $myAccountId) continue;
                    $started = new DateTime($wl['started']);
                    $started->setTimezone($tz);
                    $worklogs[] = [
                        'id'        => $wl['id'] ?? '',
                        'date'      => $started->format('Y-m-d'),
                        'time'      => $started->format('H:i'),
                        'timeSpent' => $wl['timeSpent'] ?? '',
                        'timeSpentSeconds' => (int) ($wl['timeSpentSeconds'] ?? 0),
                        'comment'   => JiraClient::adfToText($wl['comment'] ?? null),
                    ];
                }
                usort($worklogs, fn($a, $b) => strcmp($b['date'] . $b['time'], $a['date'] . $a['time']));
                echo json_encode(['ok' => true, 'worklogs' => $worklogs]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'get_issue_full') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            if (!$issueKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta issueKey']);
                exit;
            }
            try {
                $issue = $client->getIssue($issueKey, 'summary,status,description,issuetype,project');
                echo json_encode([
                    'ok'          => true,
                    'key'         => $issueKey,
                    'summary'     => $issue['fields']['summary']        ?? '',
                    'status'      => $issue['fields']['status']['name'] ?? '',
                    'description' => JiraClient::adfToText($issue['fields']['description'] ?? null),
                ]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'update_description') {
            $issueKey = strtoupper(trim($body['issueKey'] ?? ''));
            $description = (string) ($body['description'] ?? '');
            if (!$issueKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta issueKey']);
                exit;
            }
            try {
                $client->updateIssueDescription($issueKey, $description);
                echo json_encode(['ok' => true]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'subtask_types') {
            $parentKey = strtoupper(trim($body['parentKey'] ?? ''));
            if (!$parentKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta parentKey']);
                exit;
            }
            try {
                $parent = $client->getIssue($parentKey);
                $projectId = $parent['fields']['project']['id'] ?? '';
                $types = $client->listSubtaskIssueTypes($projectId);
                echo json_encode(['ok' => true, 'types' => $types]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'subtask_probe') {
            $parentKey = strtoupper(trim($body['parentKey'] ?? ''));
            $issuetypeName = trim($body['issuetypeName'] ?? '');
            if (!$parentKey || !$issuetypeName) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan parentKey o issuetypeName']);
                exit;
            }
            try {
                $info = $client->probeCreateSubtask($parentKey, $issuetypeName);
                echo json_encode([
                    'ok'            => true,
                    'fieldErrors'   => $info['fieldErrors'],
                    'errorMessages' => $info['errorMessages'] ?? [],
                ]);
            } catch (\Throwable $e) {
                $code = (int) $e->getCode();
                http_response_code(($code >= 400 && $code < 600) ? $code : 500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'subtask_required_fields') {
            $parentKey = strtoupper(trim($body['parentKey'] ?? ''));
            $issuetypeId = trim($body['issuetypeId'] ?? '');
            $issuetypeName = trim($body['issuetypeName'] ?? '');
            $includeOptional = !empty($body['includeOptional']);
            if (!$parentKey || (!$issuetypeId && !$issuetypeName)) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan parentKey o issuetypeId/Name']);
                exit;
            }
            try {
                $parent = $client->getIssue($parentKey);
                $projectId = $parent['fields']['project']['id'] ?? '';
                if (!$issuetypeId && $issuetypeName) {
                    foreach ($client->listSubtaskIssueTypes($projectId) as $t) {
                        if (mb_strtolower($t['name']) === mb_strtolower($issuetypeName)) {
                            $issuetypeId = $t['id'];
                            break;
                        }
                    }
                }
                $fields = $issuetypeId
                    ? $client->getCreateMetaRequiredFields($projectId, $issuetypeId, $includeOptional)
                    : [];
                echo json_encode(['ok' => true, 'fields' => $fields]);
            } catch (\Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        if ($action === 'create_subtask') {
            $parentKey   = strtoupper(trim($body['parentKey'] ?? ''));
            $summary     = trim($body['summary'] ?? '');
            $description = trim($body['description'] ?? '');
            $issuetypeName = trim($body['issuetypeName'] ?? '');
            $extraFields   = is_array($body['extraFields'] ?? null) ? $body['extraFields'] : [];
            $worklogDuration = trim($body['worklogDuration'] ?? '');
            $worklogComment  = trim($body['worklogComment'] ?? '');
            $worklogDate     = trim($body['worklogDate'] ?? '');
            $worklogTime     = trim($body['worklogTime'] ?? '09:00');
            if (!$parentKey || !$summary) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan parentKey o summary']);
                exit;
            }
            $cachedMe = AuthSession::cachedMyself($client);
            $accountId = $cachedMe['accountId'] ?? null;
            $result = $client->createSubtaskAndTransition(
                $parentKey,
                $summary,
                $description ?: null,
                $accountId,
                '/progres|curso|doing|trabaj|en uso/iu',
                $issuetypeName ?: null,
                $extraFields
            );
            $newKey = $result['key'] ?? '';

            // Worklog opcional en la subtarea recién creada
            $worklogResult = null;
            if ($newKey && $worklogDuration !== '') {
                $seconds = 0;
                if (preg_match('/(\d+(?:\.\d+)?)\s*h/i', $worklogDuration, $m)) {
                    $seconds += (int) round((float) $m[1] * 3600);
                }
                if (preg_match('/(\d+)\s*m/i', $worklogDuration, $m)) {
                    $seconds += (int) $m[1] * 60;
                }
                if ($seconds > 0) {
                    try {
                        if ($worklogDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $worklogDate)) {
                            $timeStr = preg_match('/^\d{2}:\d{2}$/', $worklogTime) ? $worklogTime : '09:00';
                            $dt = new DateTime($worklogDate . ' ' . $timeStr, new DateTimeZone($timezone));
                        } else {
                            $dt = new DateTime('now', new DateTimeZone($timezone));
                        }
                        $started = $dt->format('Y-m-d\TH:i:s.000O');
                        $client->addWorklog($newKey, $started, $seconds, $worklogComment ?: null);
                        $worklogResult = ['ok' => true, 'duration' => $worklogDuration];
                        AuthSession::clearReportCache();
                    } catch (\Throwable $e) {
                        $worklogResult = ['ok' => false, 'error' => $e->getMessage()];
                    }
                } else {
                    $worklogResult = ['ok' => false, 'error' => 'Duración inválida (usa formato 1h, 30m, 2h 30m)'];
                }
            }

            // Obtener info completa del issue para que el frontend lo agregue a la lista
            $issueData = null;
            if ($newKey) {
                try {
                    $issue = $client->getIssue($newKey);
                    $issueData = [
                        'key'       => $newKey,
                        'summary'   => $issue['fields']['summary'] ?? $summary,
                        'project'   => $issue['fields']['project']['name'] ?? '',
                        'status'    => $issue['fields']['status']['name'] ?? '',
                        'issuetype' => $issue['fields']['issuetype']['name'] ?? 'Sub-task',
                        'priority'  => $issue['fields']['priority']['name'] ?? '',
                    ];
                } catch (\Throwable $e) {
                    // ignorar - igual el client puede mostrar el básico
                }
            }

            echo json_encode([
                'ok'               => true,
                'key'              => $newKey,
                'issue'            => $issueData,
                'transitionName'   => $result['transition']['name'] ?? null,
                'transitionFailed' => isset($result['transition']['error']),
                'worklog'          => $worklogResult,
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
            $comment  = trim($body['comment'] ?? '');
            $transitionId = trim($body['transitionId'] ?? '');

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

            $wlResult = $client->addWorklog($issueKey, $started, $seconds, $comment ?: null);

            $transitionStatus = null;
            if ($transitionId) {
                try {
                    $client->applyTransitionById($issueKey, $transitionId);
                    $transitionStatus = ['ok' => true];
                } catch (\Throwable $e) {
                    $transitionStatus = ['ok' => false, 'error' => $e->getMessage()];
                }
            }

            $issue = $client->getIssue($issueKey);
            AuthSession::clearReportCache();
            echo json_encode([
                'ok'        => true,
                'worklogId' => $wlResult['id'] ?? '',
                'summary'   => $issue['fields']['summary'] ?? '',
                'project'   => $issue['fields']['project']['name'] ?? '',
                'status'    => $issue['fields']['status']['name'] ?? '',
                'transition' => $transitionStatus,
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

            $comment = trim($body['comment'] ?? '');
            $transitionId = trim($body['transitionId'] ?? '');

            $client->updateWorklog($issueKey, $worklogId, $started, $seconds, $comment ?: null);

            $transitionStatus = null;
            if ($transitionId) {
                try {
                    $client->applyTransitionById($issueKey, $transitionId);
                    $transitionStatus = ['ok' => true];
                } catch (\Throwable $e) {
                    $transitionStatus = ['ok' => false, 'error' => $e->getMessage()];
                }
            }

            AuthSession::clearReportCache();
            echo json_encode(['ok' => true, 'transition' => $transitionStatus]);
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
        $code = (int) $e->getCode();
        http_response_code(($code >= 400 && $code < 600) ? $code : 500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
require __DIR__ . '/../templates/report.php';
