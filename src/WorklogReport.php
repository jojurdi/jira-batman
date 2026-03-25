<?php

namespace App;

class WorklogReport
{
    private JiraClient $client;
    private string $timezone;
    private float $hoursPerDay;
    private string $accountId;

    public function __construct(JiraClient $client, string $timezone, float $hoursPerDay = 8.0)
    {
        $this->client = $client;
        $this->timezone = $timezone;
        $this->hoursPerDay = $hoursPerDay;

        $me = $this->client->getMyself();
        $this->accountId = $me['accountId'];
    }

    public function getAccountDisplayName(): string
    {
        $me = $this->client->getMyself();
        return $me['displayName'] ?? 'Desconocido';
    }

    /**
     * Genera el reporte de worklogs agrupados por día.
     * @return array ['days' => [...], 'summary' => [...]]
     */
    public function generate(string $startDate, string $endDate): array
    {
        $tz = new \DateTimeZone($this->timezone);

        $jql = sprintf(
            'worklogAuthor = currentUser() AND worklogDate >= "%s" AND worklogDate <= "%s" ORDER BY updated DESC',
            $startDate,
            $endDate
        );

        $searchResult = $this->client->searchIssues($jql, 100, 'summary,worklog,status,project,parent,customfield_10014,issuetype');
        $issues = $searchResult['issues'] ?? [];

        $start = new \DateTime($startDate, $tz);
        $end = new \DateTime($endDate, $tz);
        $end->setTime(23, 59, 59);

        $dayMap = [];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), (clone $end)->modify('+1 day'));
        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            $dayOfWeek = (int) $day->format('N');
            $dayMap[$key] = [
                'date' => $key,
                'dayName' => $this->spanishDayName($dayOfWeek),
                'isWeekend' => $dayOfWeek >= 6,
                'worklogs' => [],
                'totalSeconds' => 0,
            ];
        }

        // Recopilar claves de épicas para fetch en lote
        $issueEpicKeys = [];
        $epicKeysNeeded = [];
        foreach ($issues as $issue) {
            $k = $issue['key'];
            $parentKey  = $issue['fields']['parent']['key'] ?? null;
            $parentType = strtolower($issue['fields']['parent']['fields']['issuetype']['name'] ?? '');
            $epicLink   = $issue['fields']['customfield_10014'] ?? null;
            if ($parentType === 'epic' && $parentKey) {
                $issueEpicKeys[$k] = $parentKey;
                $epicKeysNeeded[$parentKey] = true;
            } elseif ($epicLink) {
                $issueEpicKeys[$k] = $epicLink;
                $epicKeysNeeded[$epicLink] = true;
            }
        }

        // Fetch de épicas en una sola llamada
        $epicMeta = [];
        if (!empty($epicKeysNeeded)) {
            $epicJql    = 'issueKey in (' . implode(',', array_keys($epicKeysNeeded)) . ')';
            $epicsResult = $this->client->searchIssues($epicJql, 200, 'summary,parent,issuetype');
            foreach ($epicsResult['issues'] ?? [] as $epic) {
                $epicMeta[$epic['key']] = [
                    'summary'           => $epic['fields']['summary'] ?? $epic['key'],
                    'initiativeKey'     => $epic['fields']['parent']['key'] ?? null,
                    'initiativeSummary' => $epic['fields']['parent']['fields']['summary'] ?? null,
                ];
            }
        }

        foreach ($issues as $issue) {
            $issueKey = $issue['key'];
            $summary = $issue['fields']['summary'] ?? '';
            $status = $issue['fields']['status']['name'] ?? '';
            $project = $issue['fields']['project']['name'] ?? '';

            $worklogData = $this->client->getIssueWorklogs($issueKey);
            $worklogs = $worklogData['worklogs'] ?? [];

            foreach ($worklogs as $wl) {
                if (($wl['author']['accountId'] ?? '') !== $this->accountId) {
                    continue;
                }

                $started = new \DateTime($wl['started']);
                $started->setTimezone($tz);
                $dayKey = $started->format('Y-m-d');

                if (!isset($dayMap[$dayKey])) {
                    continue;
                }

                $seconds = (int) ($wl['timeSpentSeconds'] ?? 0);
                $dayMap[$dayKey]['worklogs'][] = [
                    'id' => $wl['id'] ?? '',
                    'issueKey' => $issueKey,
                    'summary' => $summary,
                    'status' => $status,
                    'project' => $project,
                    'timeSpent' => $wl['timeSpent'] ?? '',
                    'timeSpentSeconds' => $seconds,
                    'started' => $started->format('H:i'),
                ];
                $dayMap[$dayKey]['totalSeconds'] += $seconds;
            }
        }

        $totalLoggedSeconds = 0;
        $totalExpectedSeconds = 0;
        $workDays = 0;

        foreach ($dayMap as &$day) {
            $day['totalHours'] = round($day['totalSeconds'] / 3600, 2);
            $day['expectedHours'] = $day['isWeekend'] ? 0 : $this->hoursPerDay;
            $day['remainingHours'] = max(0, $day['expectedHours'] - $day['totalHours']);
            $day['overtime'] = max(0, $day['totalHours'] - $day['expectedHours']);

            if (!$day['isWeekend']) {
                $totalLoggedSeconds += $day['totalSeconds'];
                $totalExpectedSeconds += $this->hoursPerDay * 3600;
                $workDays++;
            }

            usort($day['worklogs'], fn($a, $b) => strcmp($a['started'], $b['started']));
        }
        unset($day);

        $totalLogged = round($totalLoggedSeconds / 3600, 2);
        $totalExpected = round($totalExpectedSeconds / 3600, 2);

        // Agrupar por tarea con info de jerarquía
        $issueMap = [];
        foreach ($dayMap as $day) {
            foreach ($day['worklogs'] as $wl) {
                $k = $wl['issueKey'];
                if (!isset($issueMap[$k])) {
                    $epicKey  = $issueEpicKeys[$k] ?? null;
                    $issueMap[$k] = [
                        'issueKey'          => $k,
                        'summary'           => $wl['summary'],
                        'project'           => $wl['project'],
                        'status'            => $wl['status'],
                        'totalSeconds'      => 0,
                        'epicKey'           => $epicKey,
                        'epicSummary'       => $epicKey ? ($epicMeta[$epicKey]['summary'] ?? $epicKey) : null,
                        'initiativeKey'     => $epicKey ? ($epicMeta[$epicKey]['initiativeKey'] ?? null) : null,
                        'initiativeSummary' => $epicKey ? ($epicMeta[$epicKey]['initiativeSummary'] ?? null) : null,
                    ];
                }
                $issueMap[$k]['totalSeconds'] += $wl['timeSpentSeconds'];
            }
        }
        foreach ($issueMap as &$iss) {
            $iss['totalHours'] = round($iss['totalSeconds'] / 3600, 2);
        }
        unset($iss);

        // Construir jerarquía: iniciativa → épica → historia
        $hierarchy = [];
        foreach ($issueMap as $iss) {
            $initKey   = $iss['initiativeKey']     ?? '_none';
            $initLabel = $iss['initiativeSummary'] ?? 'Sin iniciativa';
            $epicKey   = $iss['epicKey']           ?? '_none';
            $epicLabel = $iss['epicSummary']       ?? 'Sin épica';

            if (!isset($hierarchy[$initKey])) {
                $hierarchy[$initKey] = [
                    'key'          => $initKey === '_none' ? null : $initKey,
                    'summary'      => $initLabel,
                    'totalSeconds' => 0,
                    'epics'        => [],
                ];
            }
            if (!isset($hierarchy[$initKey]['epics'][$epicKey])) {
                $hierarchy[$initKey]['epics'][$epicKey] = [
                    'key'          => $epicKey === '_none' ? null : $epicKey,
                    'summary'      => $epicLabel,
                    'totalSeconds' => 0,
                    'issues'       => [],
                ];
            }
            $hierarchy[$initKey]['epics'][$epicKey]['issues'][]      = $iss;
            $hierarchy[$initKey]['epics'][$epicKey]['totalSeconds']  += $iss['totalSeconds'];
            $hierarchy[$initKey]['totalSeconds']                     += $iss['totalSeconds'];
        }

        usort($hierarchy, fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
        foreach ($hierarchy as &$init) {
            $init['totalHours'] = round($init['totalSeconds'] / 3600, 2);
            usort($init['epics'], fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
            foreach ($init['epics'] as &$epic) {
                $epic['totalHours'] = round($epic['totalSeconds'] / 3600, 2);
                usort($epic['issues'], fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
                $epic['epics'] = array_values($epic['epics'] ?? []);
            }
            unset($epic);
            $init['epics'] = array_values($init['epics']);
        }
        unset($init);

        return [
            'days'        => array_values($dayMap),
            'byHierarchy' => array_values($hierarchy),
            'summary' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'workDays' => $workDays,
                'totalLogged' => $totalLogged,
                'totalExpected' => $totalExpected,
                'totalRemaining' => max(0, round($totalExpected - $totalLogged, 2)),
                'totalOvertime' => max(0, round($totalLogged - $totalExpected, 2)),
                'completionPercent' => $totalExpected > 0
                    ? round(($totalLogged / $totalExpected) * 100, 1)
                    : 0,
            ],
        ];
    }

    private function spanishDayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => '',
        };
    }
}
