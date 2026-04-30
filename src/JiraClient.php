<?php

namespace App;

class JiraClient
{
    private string $baseUrl;
    private string $authHeader;
    private string $noProxyHost;

    public function __construct(string $baseUrl, string $authHeader)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->authHeader = $authHeader;
        $this->noProxyHost = parse_url($this->baseUrl, PHP_URL_HOST) ?: '';
    }

    public static function fromBasic(string $jiraUrl, string $email, string $apiToken): self
    {
        return new self($jiraUrl, 'Basic ' . base64_encode($email . ':' . $apiToken));
    }

    public static function fromOAuth(string $cloudId, string $accessToken): self
    {
        return new self("https://api.atlassian.com/ex/jira/{$cloudId}", 'Bearer ' . $accessToken);
    }

    /**
     * Ejecuta una petición cURL y reintenta alternando proxy/no-proxy
     * según el tipo de error de red detectado.
     */
    private function executeWithProxyFallback(array $options): array
    {
        $attemptModes = ['no_proxy', 'default'];
        $last = ['response' => false, 'httpCode' => 0, 'error' => ''];

        foreach ($attemptModes as $mode) {
            $attemptOptions = $options;

            if ($mode === 'no_proxy' && $this->noProxyHost !== '') {
                $attemptOptions[CURLOPT_NOPROXY] = $this->noProxyHost;
            } else {
                unset($attemptOptions[CURLOPT_NOPROXY]);
            }

            $ch = curl_init();
            curl_setopt_array($ch, $attemptOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $last = ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];

            if ($error === '') {
                return $last;
            }

            $isConnectTunnelError = stripos($error, 'CONNECT tunnel failed') !== false;
            $isHostResolveError = stripos($error, 'Could not resolve host') !== false;

            // Si sin proxy no resuelve DNS, probamos con proxy (siguiente intento).
            if ($mode === 'no_proxy' && $isHostResolveError) {
                continue;
            }

            // Si con proxy falla túnel CONNECT, ya probamos sin proxy primero.
            if ($mode === 'default' && $isConnectTunnelError) {
                continue;
            }

            break;
        }

        return $last;
    }

    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $result = $this->executeWithProxyFallback([
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
        $response = $result['response'];
        $httpCode = $result['httpCode'];
        $error = $result['error'];

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}", $httpCode);
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

        $result = $this->executeWithProxyFallback([
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
        $response = $result['response'];
        $httpCode = $result['httpCode'];
        $error = $result['error'];

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}", $httpCode);
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

        $result = $this->executeWithProxyFallback([
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
        $response = $result['response'];
        $httpCode = $result['httpCode'];
        $error = $result['error'];

        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }

        if ($httpCode >= 400) {
            $body = json_decode($response, true);
            $msg = $body['errorMessages'][0] ?? $body['message'] ?? "HTTP {$httpCode}";
            throw new \RuntimeException("Error de Jira API ({$httpCode}): {$msg}", $httpCode);
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

        $result = $this->executeWithProxyFallback([
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
        $response = $result['response'];
        $httpCode = $result['httpCode'];
        $error    = $result['error'];

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
