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
        $periodEnd = (new \DateTime($endDate, $tz))->modify('+1 day');
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $periodEnd);
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
            $k         = $issue['key'];
            $parentKey = $issue['fields']['parent']['key'] ?? null;
            $epicLink  = $issue['fields']['customfield_10014'] ?? null;
            if ($parentKey) {
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
                    $epicKey = $issueEpicKeys[$k] ?? null;
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
        foreach ($issueMap as $mk => $iss) {
            $issueMap[$mk]['totalHours'] = round($iss['totalSeconds'] / 3600, 2);
        }

        // Construir jerarquía usando copias (sin referencias)
        $rawHier = [];
        foreach ($issueMap as $iss) {
            $initKey   = $iss['initiativeKey']     ?? '_none';
            $initLabel = $iss['initiativeSummary'] ?? 'Sin iniciativa';
            $epicKey   = $iss['epicKey']           ?? '_none';
            $epicLabel = $iss['epicSummary']       ?? 'Sin épica';

            if (!isset($rawHier[$initKey])) {
                $rawHier[$initKey] = [
                    'key'          => $initKey === '_none' ? null : $initKey,
                    'summary'      => $initLabel,
                    'totalSeconds' => 0,
                    'epics'        => [],
                ];
            }
            if (!isset($rawHier[$initKey]['epics'][$epicKey])) {
                $rawHier[$initKey]['epics'][$epicKey] = [
                    'key'          => $epicKey === '_none' ? null : $epicKey,
                    'summary'      => $epicLabel,
                    'totalSeconds' => 0,
                    'issues'       => [],
                ];
            }
            $rawHier[$initKey]['epics'][$epicKey]['issues'][]     = $iss;
            $rawHier[$initKey]['epics'][$epicKey]['totalSeconds'] += $iss['totalSeconds'];
            $rawHier[$initKey]['totalSeconds']                    += $iss['totalSeconds'];
        }

        // Ordenar y aplanar sin referencias
        $byHierarchy = [];
        foreach ($rawHier as $init) {
            $epics = [];
            foreach ($init['epics'] as $epic) {
                $issues = $epic['issues'];
                usort($issues, fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
                $epics[] = [
                    'key'          => $epic['key'],
                    'summary'      => $epic['summary'],
                    'totalSeconds' => $epic['totalSeconds'],
                    'totalHours'   => round($epic['totalSeconds'] / 3600, 2),
                    'issues'       => $issues,
                ];
            }
            usort($epics, fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
            $byHierarchy[] = [
                'key'          => $init['key'],
                'summary'      => $init['summary'],
                'totalSeconds' => $init['totalSeconds'],
                'totalHours'   => round($init['totalSeconds'] / 3600, 2),
                'epics'        => $epics,
            ];
        }
        usort($byHierarchy, fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);

        // Construir matriz: tarea × día
        $matrixDates = [];
        foreach ($dayMap as $date => $day) {
            $matrixDates[] = [
                'date'      => $date,
                'isWeekend' => $day['isWeekend'],
                'label'     => mb_substr($day['dayName'], 0, 2) . ' ' . date('d', strtotime($date)),
            ];
        }

        $matrixRows   = [];
        $matrixTotals = array_fill_keys(array_keys($dayMap), 0.0);

        foreach ($dayMap as $date => $day) {
            foreach ($day['worklogs'] as $wl) {
                $k = $wl['issueKey'];
                if (!isset($matrixRows[$k])) {
                    $matrixRows[$k] = [
                        'issueKey'     => $k,
                        'summary'      => $wl['summary'],
                        'project'      => $wl['project'],
                        'totalSeconds' => 0,
                        'cells'        => array_fill_keys(array_keys($dayMap), 0.0),
                    ];
                }
                $h = round($wl['timeSpentSeconds'] / 3600, 2);
                $matrixRows[$k]['cells'][$date]  = round(($matrixRows[$k]['cells'][$date] ?? 0) + $h, 2);
                $matrixRows[$k]['totalSeconds'] += $wl['timeSpentSeconds'];
                $matrixTotals[$date]             = round(($matrixTotals[$date] ?? 0) + $h, 2);
            }
        }

        usort($matrixRows, fn($a, $b) => $b['totalSeconds'] - $a['totalSeconds']);
        $matrixFinal = [];
        foreach ($matrixRows as $row) {
            $row['totalHours'] = round($row['totalSeconds'] / 3600, 2);
            $matrixFinal[]     = $row;
        }

        // Amortizable: tareas cuya épica tiene iniciativa padre.
        // No amortizable: el resto (sin épica, o épica sin iniciativa).
        $amortizableSeconds = 0;
        $nonAmortizableSeconds = 0;
        $amortizableInitiatives = [];
        foreach ($byHierarchy as $init) {
            if (!empty($init['key'])) {
                $amortizableSeconds += $init['totalSeconds'];
                $amortizableInitiatives[] = [
                    'key'          => $init['key'],
                    'summary'      => $init['summary'],
                    'totalSeconds' => $init['totalSeconds'],
                    'totalHours'   => $init['totalHours'],
                ];
            } else {
                $nonAmortizableSeconds += $init['totalSeconds'];
            }
        }
        $amortTotalSeconds = $amortizableSeconds + $nonAmortizableSeconds;
        $amortization = [
            'amortizableHours'    => round($amortizableSeconds / 3600, 2),
            'nonAmortizableHours' => round($nonAmortizableSeconds / 3600, 2),
            'totalHours'          => round($amortTotalSeconds / 3600, 2),
            'amortizablePercent'  => $amortTotalSeconds > 0
                ? round($amortizableSeconds / $amortTotalSeconds * 100, 1)
                : 0,
            'nonAmortizablePercent' => $amortTotalSeconds > 0
                ? round($nonAmortizableSeconds / $amortTotalSeconds * 100, 1)
                : 0,
            'initiatives' => $amortizableInitiatives,
        ];

        return [
            'days'         => array_values($dayMap),
            'byHierarchy'  => $byHierarchy,
            'amortization' => $amortization,
            'matrix'      => [
                'dates'      => $matrixDates,
                'rows'       => $matrixFinal,
                'totals'     => $matrixTotals,
                'grandTotal' => round(array_sum($matrixTotals), 2),
            ],
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
