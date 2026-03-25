<?php

namespace App;

class JiraClient
{
    private string $baseUrl;
    private string $authHeader;

    public function __construct(string $baseUrl, string $email, string $apiToken)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->authHeader = 'Basic ' . base64_encode($email . ':' . $apiToken);
    }

    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}");
        }

        return json_decode($response, true) ?? [];
    }

    public function getMyself(): array
    {
        return $this->get('/rest/api/3/myself');
    }

    public function searchIssues(string $jql, int $maxResults = 100, string $fields = 'summary,worklog,status,project'): array
    {
        return $this->get('/rest/api/3/search/jql', [
            'jql'        => $jql,
            'maxResults' => $maxResults,
            'fields'     => $fields,
        ]);
    }

    public function getIssueWorklogs(string $issueKey): array
    {
        return $this->get("/rest/api/3/issue/{$issueKey}/worklog");
    }

    public function getIssue(string $issueKey): array
    {
        return $this->get("/rest/api/3/issue/{$issueKey}", [
            'fields' => 'summary,status,project',
        ]);
    }

    public function post(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}");
        }

        return json_decode($response, true) ?? [];
    }

    public function addWorklog(string $issueKey, string $started, int $timeSpentSeconds): array
    {
        return $this->post("/rest/api/3/issue/{$issueKey}/worklog", [
            'started' => $started,
            'timeSpentSeconds' => $timeSpentSeconds,
        ]);
    }

    public function put(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}");
        }

        return json_decode($response, true) ?? [];
    }

    public function updateWorklog(string $issueKey, string $worklogId, string $started, int $timeSpentSeconds): array
    {
        return $this->put("/rest/api/3/issue/{$issueKey}/worklog/{$worklogId}", [
            'started' => $started,
            'timeSpentSeconds' => $timeSpentSeconds,
        ]);
    }

    public function deleteWorklog(string $issueKey, string $worklogId): void
    {
        $url = $this->baseUrl . "/rest/api/3/issue/{$issueKey}/worklog/{$worklogId}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $this->authHeader,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }
        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg  = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}");
        }
    }
}
