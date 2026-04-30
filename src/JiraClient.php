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

    /**
     * Obtiene los worklogs de varios issues en paralelo via curl_multi.
     * @param string[] $issueKeys
     * @return array<string, array> map issueKey => respuesta cruda (con 'worklogs' dentro)
     */
    public function getMultipleIssueWorklogs(array $issueKeys): array
    {
        if (empty($issueKeys)) {
            return [];
        }

        // Probamos primero sin proxy y, si falla DNS, retry con proxy.
        foreach (['no_proxy', 'default'] as $mode) {
            $multi = curl_multi_init();
            $handles = [];

            foreach ($issueKeys as $key) {
                $ch = curl_init();
                $opts = [
                    CURLOPT_URL            => $this->baseUrl . "/rest/api/3/issue/{$key}/worklog",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: ' . $this->authHeader,
                        'Accept: application/json',
                    ],
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_SSL_VERIFYPEER => true,
                ];
                if ($mode === 'no_proxy' && $this->noProxyHost !== '') {
                    $opts[CURLOPT_NOPROXY] = $this->noProxyHost;
                }
                curl_setopt_array($ch, $opts);
                curl_multi_add_handle($multi, $ch);
                $handles[$key] = $ch;
            }

            do {
                $status = curl_multi_exec($multi, $running);
                if ($running > 0) {
                    curl_multi_select($multi, 1.0);
                }
            } while ($running > 0 && $status === CURLM_OK);

            $results = [];
            $sawDnsError = false;
            $sawTunnelError = false;
            foreach ($handles as $key => $ch) {
                $response = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error    = curl_error($ch);
                if ($error !== '') {
                    if (stripos($error, 'Could not resolve host') !== false) $sawDnsError = true;
                    if (stripos($error, 'CONNECT tunnel failed') !== false) $sawTunnelError = true;
                }
                if ($httpCode >= 200 && $httpCode < 400 && $error === '') {
                    $results[$key] = json_decode($response, true) ?? [];
                }
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
            }
            curl_multi_close($multi);

            // Si hubo error de DNS sin proxy, reintentar con proxy.
            if ($mode === 'no_proxy' && $sawDnsError && empty($results)) {
                continue;
            }
            // Si hubo error de tunnel con proxy, reintentar sin proxy.
            if ($mode === 'default' && $sawTunnelError && empty($results)) {
                continue;
            }

            return $results;
        }

        return [];
    }

    public function getIssue(string $issueKey, string $fields = 'summary,status,project'): array
    {
        return $this->get("/rest/api/3/issue/{$issueKey}", [
            'fields' => $fields,
        ]);
    }

    /**
     * Actualiza la descripción de un issue. Si $description es '' la borra.
     */
    public function updateIssueDescription(string $issueKey, string $description): void
    {
        $payload = ['fields' => [
            'description' => $description !== '' ? $this->makeAdfText($description) : null,
        ]];
        $this->put("/rest/api/3/issue/{$issueKey}", $payload);
    }

    /**
     * Sugerencias rápidas de issues para el typeahead.
     * Devuelve la sección "Current Search" + "History" del picker de Jira.
     */
    /**
     * Búsqueda rápida de issues para typeahead. Prueba varias JQL en cascada
     * hasta encontrar resultados, porque Jira es exigente con la sintaxis del
     * operador `~` (mín 3 caracteres en algunos sites, requiere wildcard, etc.).
     *
     * Devuelve [issues, debug] donde debug es info para troubleshooting.
     */
    public function pickIssues(string $query, int $limit = 8): array
    {
        [$issues] = $this->pickIssuesWithDebug($query, $limit);
        return $issues;
    }

    /**
     * @return array{0: array, 1: array} [issues, debug]
     */
    public function pickIssuesWithDebug(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') {
            return [[], ['reason' => 'empty_query']];
        }

        $clean = str_replace(['\\', '"'], '', $query);
        $upper = strtoupper($clean);
        $debug = ['query' => $query, 'attempts' => []];

        $candidates = [];
        // 1. Clave exacta tipo ABC-123
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]*-\d+$/', $clean)) {
            $candidates[] = sprintf('issuekey = "%s"', $upper);
        }
        // 2. Project key exacto (ABC, ABC2)
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]{1,9}$/', $clean)) {
            $candidates[] = sprintf('project = "%s"', $upper);
        }
        // 3. summary con wildcard prefix
        $candidates[] = sprintf('summary ~ "%s*"', $clean);
        // 4. summary sin wildcard (algunos Jira no aceptan wildcard puro)
        $candidates[] = sprintf('summary ~ "%s"', $clean);
        // 5. text (busca en summary + description + comments)
        $candidates[] = sprintf('text ~ "%s*"', $clean);
        $candidates[] = sprintf('text ~ "%s"', $clean);

        foreach ($candidates as $jqlBase) {
            $jql = $jqlBase . ' ORDER BY updated DESC';
            $attempt = ['jql' => $jql];
            try {
                $result = $this->searchIssues($jql, $limit, 'summary,project,issuetype,status');
                $issues = $result['issues'] ?? [];
                $attempt['count'] = count($issues);
                $debug['attempts'][] = $attempt;
                if (!empty($issues)) {
                    return [$this->normalizePickerResults($issues), $debug];
                }
            } catch (\Throwable $e) {
                $attempt['error'] = $e->getMessage();
                $debug['attempts'][] = $attempt;
                // probar el siguiente
            }
        }

        return [[], $debug];
    }

    private function normalizePickerResults(array $issues): array
    {
        $out = [];
        foreach ($issues as $iss) {
            if (empty($iss['key'])) {
                continue;
            }
            $out[] = [
                'key'     => $iss['key'],
                'summary' => $iss['fields']['summary'] ?? '',
                'project' => $iss['fields']['project']['name'] ?? '',
                'img'     => '',
            ];
        }
        return $out;
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

    public function addWorklog(string $issueKey, string $started, int $timeSpentSeconds, ?string $comment = null): array
    {
        $payload = [
            'started' => $started,
            'timeSpentSeconds' => $timeSpentSeconds,
        ];
        if ($comment !== null && $comment !== '') {
            $payload['comment'] = $this->makeAdfText($comment);
        }
        return $this->post("/rest/api/3/issue/{$issueKey}/worklog", $payload);
    }

    /**
     * Extrae texto plano de un nodo ADF (Atlassian Document Format).
     * Útil para mostrar descriptions y comments que vienen como JSON anidado.
     */
    public static function adfToText($adf): string
    {
        if ($adf === null || $adf === '') return '';
        if (is_string($adf)) return $adf;
        if (!is_array($adf)) return '';
        return rtrim(self::extractAdfNode($adf));
    }

    private static function extractAdfNode($node): string
    {
        if (!is_array($node)) return '';
        $out = '';
        if (isset($node['text']) && is_string($node['text'])) {
            $out .= $node['text'];
        }
        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                $out .= self::extractAdfNode($child);
            }
        }
        $type = $node['type'] ?? '';
        // Saltos entre paragraphs / list items / heading
        if (in_array($type, ['paragraph', 'heading', 'listItem', 'blockquote', 'codeBlock'], true)) {
            $out .= "\n";
        } elseif ($type === 'hardBreak') {
            $out .= "\n";
        }
        return $out;
    }

    /**
     * Convierte texto plano a Atlassian Document Format (ADF), formato requerido
     * por Jira Cloud para comments y descriptions.
     */
    private function makeAdfText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $content = [];
        foreach ($lines as $line) {
            $para = ['type' => 'paragraph', 'content' => []];
            if ($line !== '') {
                $para['content'][] = ['type' => 'text', 'text' => $line];
            }
            $content[] = $para;
        }
        if (empty($content)) {
            $content[] = ['type' => 'paragraph', 'content' => []];
        }
        return ['type' => 'doc', 'version' => 1, 'content' => $content];
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

    public function updateWorklog(string $issueKey, string $worklogId, string $started, int $timeSpentSeconds, ?string $comment = null): array
    {
        $payload = [
            'started' => $started,
            'timeSpentSeconds' => $timeSpentSeconds,
        ];
        if ($comment !== null && $comment !== '') {
            $payload['comment'] = $this->makeAdfText($comment);
        }
        return $this->put("/rest/api/3/issue/{$issueKey}/worklog/{$worklogId}", $payload);
    }

    public function getIssueTransitions(string $issueKey): array
    {
        $data = $this->get("/rest/api/3/issue/{$issueKey}/transitions");
        return $data['transitions'] ?? [];
    }

    public function applyTransitionById(string $issueKey, string $transitionId): void
    {
        $this->post("/rest/api/3/issue/{$issueKey}/transitions", [
            'transition' => ['id' => $transitionId],
        ]);
    }

    /**
     * Lista issues asignadas al usuario actual que no estén "Done".
     */
    public function getMyAssignedIssues(int $limit = 30): array
    {
        $jql = 'assignee = currentUser() AND statusCategory != Done ORDER BY priority DESC, updated DESC';
        $result = $this->searchIssues($jql, $limit, 'summary,status,priority,project,issuetype,description,subtasks');
        return $result['issues'] ?? [];
    }

    /**
     * Crea una subtarea bajo $parentKey, asigna al accountId si se da, y la mueve
     * a la transición que matchee $transitionMatch (regex case-insensitive).
     *
     * @return array ['key' => 'PROJ-456', 'self' => '...', ...] de la subtarea creada
     */
    public function createSubtaskAndTransition(
        string $parentKey,
        string $summary,
        ?string $description = null,
        ?string $assigneeAccountId = null,
        string $transitionMatch = '/progres|curso|doing|trabaj|en uso/iu'
    ): array {
        $parent = $this->getIssue($parentKey);
        $projectKey = $parent['fields']['project']['key'] ?? '';
        $projectId  = $parent['fields']['project']['id']  ?? '';
        if (!$projectKey) {
            throw new \RuntimeException("No se pudo determinar el proyecto del parent {$parentKey}");
        }

        // Detectar el issuetype subtarea del proyecto.
        $subtaskTypeName = $this->findSubtaskIssueType($projectId);
        if (!$subtaskTypeName) {
            throw new \RuntimeException("El proyecto {$projectKey} no tiene un tipo de subtarea configurado.");
        }

        $fields = [
            'project'   => ['key' => $projectKey],
            'parent'    => ['key' => $parentKey],
            'summary'   => $summary,
            'issuetype' => ['name' => $subtaskTypeName],
        ];
        if ($assigneeAccountId) {
            $fields['assignee'] = ['accountId' => $assigneeAccountId];
        }
        if ($description !== null && $description !== '') {
            // Jira Cloud usa ADF (Atlassian Document Format) para descripción.
            $fields['description'] = [
                'type'    => 'doc',
                'version' => 1,
                'content' => [[
                    'type'    => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $description]],
                ]],
            ];
        }

        $created = $this->post('/rest/api/3/issue', ['fields' => $fields]);
        $newKey = $created['key'] ?? '';

        // Aplicar transition (best-effort, no falla la creación si no hay transition).
        $transitionApplied = null;
        if ($newKey) {
            try {
                $transitionApplied = $this->applyTransition($newKey, $transitionMatch);
            } catch (\Throwable $e) {
                $transitionApplied = ['error' => $e->getMessage()];
            }
        }

        return [
            'key'        => $newKey,
            'self'       => $created['self'] ?? '',
            'transition' => $transitionApplied,
        ];
    }

    private function findSubtaskIssueType(string $projectIdOrKey): ?string
    {
        if (!$projectIdOrKey) return null;
        try {
            $types = $this->get('/rest/api/3/issuetype/project', ['projectId' => $projectIdOrKey]);
        } catch (\Throwable $e) {
            // Fallback: tipos globales
            try {
                $types = $this->get('/rest/api/3/issuetype');
            } catch (\Throwable $e2) {
                return null;
            }
        }
        foreach ($types as $t) {
            if (!empty($t['subtask']) && !empty($t['name'])) {
                return $t['name'];
            }
        }
        return null;
    }

    /**
     * Aplica la transición cuyo nombre matchee la regex.
     * @return array|null información de la transición aplicada, o null si no se encontró
     */
    public function applyTransition(string $issueKey, string $matchPattern): ?array
    {
        $data = $this->get("/rest/api/3/issue/{$issueKey}/transitions");
        $transitions = $data['transitions'] ?? [];
        $matched = null;
        foreach ($transitions as $t) {
            if (preg_match($matchPattern, $t['name'] ?? '')) {
                $matched = $t;
                break;
            }
        }
        if (!$matched) {
            return null;
        }
        $this->post("/rest/api/3/issue/{$issueKey}/transitions", [
            'transition' => ['id' => $matched['id']],
        ]);
        return ['id' => $matched['id'], 'name' => $matched['name']];
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
