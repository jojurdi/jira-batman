<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\JiraClient;
use App\WorklogReport;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$timezone = $_ENV['TIMEZONE'] ?? 'America/Mexico_City';
$hoursPerDay = (float) ($_ENV['HOURS_PER_DAY'] ?? 8);
date_default_timezone_set($timezone);

$jiraBaseUrl = $_COOKIE['jira_base_url'] ?? $_ENV['JIRA_BASE_URL'] ?? '';
$jiraEmail = $_COOKIE['jira_email'] ?? $_ENV['JIRA_EMAIL'] ?? '';
$jiraToken = $_COOKIE['jira_token'] ?? $_ENV['JIRA_API_TOKEN'] ?? '';
$needsSetup = empty($jiraBaseUrl) || empty($jiraEmail) || empty($jiraToken);

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
        $startDate = $today->format('Y-m-01');
        $endDate = $today->format('Y-m-d');
        break;
}

$error = null;
$report = null;
$displayName = '';

if (!$needsSetup) {
    try {
        $client = new JiraClient($jiraBaseUrl, $jiraEmail, $jiraToken);
        $worklogReport = new WorklogReport($client, $timezone, $hoursPerDay);
        $displayName = $worklogReport->getAccountDisplayName();
        $report = $worklogReport->generate($startDate, $endDate);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

require __DIR__ . '/../templates/report.php';
