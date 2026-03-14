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

        $searchResult = $this->client->searchIssues($jql, 100);
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

        return [
            'days' => array_values($dayMap),
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
