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
            self::throwJiraError($httpCode, $response);
        }

        return json_decode($response, true) ?? [];
    }

    public function getMyself(): array
    {
        return $this->get('/rest/api/3/myself');
    }

    /**
     * Convierte la respuesta cruda de un error de Jira en un mensaje legible.
     * Lanza JiraApiException preservando errorMessages[] y errors{} para que
     * el endpoint pueda reaccionar (ej. abrir un modal con los campos faltantes).
     */
    private static function throwJiraError(int $httpCode, ?string $response): void
    {
        $body = is_string($response) ? json_decode($response, true) : null;
        $errorMessages = [];
        $fieldErrors = [];
        $parts = [];
        if (is_array($body)) {
            foreach (($body['errorMessages'] ?? []) as $m) {
                if ($m !== '') {
                    $errorMessages[] = (string) $m;
                    $parts[] = (string) $m;
                }
            }
            foreach (($body['errors'] ?? []) as $field => $m) {
                if ($m !== '') {
                    $fieldErrors[(string) $field] = (string) $m;
                    $parts[] = $field . ': ' . (string) $m;
                }
            }
            if (empty($parts) && !empty($body['message'])) {
                $parts[] = (string) $body['message'];
            }
        }
        $msg = $parts ? implode(' | ', $parts) : "HTTP {$httpCode}";
        throw new JiraApiException("Error de Jira API ({$httpCode}): {$msg}", $httpCode, $errorMessages, $fieldErrors);
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
            self::throwJiraError($httpCode, $response);
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
            self::throwJiraError($httpCode, $response);
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

    public function applyTransitionById(
        string $issueKey,
        string $transitionId,
        array $extraFields = [],
        ?string $comment = null
    ): void {
        $payload = ['transition' => ['id' => $transitionId]];
        if (!empty($extraFields)) {
            $payload['fields'] = $extraFields;
        }
        if ($comment !== null && $comment !== '') {
            $payload['update'] = $payload['update'] ?? [];
            $payload['update']['comment'] = [[
                'add' => [
                    'body' => [
                        'type'    => 'doc',
                        'version' => 1,
                        'content' => [[
                            'type'    => 'paragraph',
                            'content' => [['type' => 'text', 'text' => $comment]],
                        ]],
                    ],
                ],
            ]];
        }
        $this->post("/rest/api/3/issue/{$issueKey}/transitions", $payload);
    }

    /**
     * Devuelve los campos requeridos por la transición indicada para el issue.
     * Usa el expand 'transitions.fields' del endpoint de transitions.
     *
     * @return array{
     *   commentRequired: bool,
     *   fields: array<int, array{
     *     key: string, name: string, type: string, items: ?string,
     *     allowedValues: array, multiple: bool, hasDefaultValue: bool
     *   }>
     * }
     */
    public function getTransitionRequiredFields(string $issueKey, string $transitionId): array
    {
        $skip = ['summary', 'project', 'issuetype', 'parent', 'description',
                 'reporter', 'attachment', 'issuelinks'];
        $data = $this->get("/rest/api/3/issue/{$issueKey}/transitions", [
            'expand' => 'transitions.fields',
        ]);
        $transitions = $data['transitions'] ?? [];
        $target = null;
        foreach ($transitions as $t) {
            if ((string) ($t['id'] ?? '') === $transitionId) {
                $target = $t;
                break;
            }
        }
        if (!$target) {
            return ['commentRequired' => false, 'fields' => []];
        }
        $fieldsMap = $target['fields'] ?? [];

        $commentRequired = false;
        $out = [];
        foreach ($fieldsMap as $key => $f) {
            if (empty($f['required'])) continue;
            if ($key === 'comment') {
                $commentRequired = true;
                continue;
            }
            if (in_array($key, $skip, true)) continue;
            if (!empty($f['hasDefaultValue'])) continue;

            $schema = $f['schema'] ?? [];
            $type = (string) ($schema['type'] ?? 'string');
            $items = isset($schema['items']) ? (string) $schema['items'] : null;
            $allowed = [];
            foreach (($f['allowedValues'] ?? []) as $av) {
                $allowed[] = [
                    'id'    => (string) ($av['id'] ?? ''),
                    'value' => (string) ($av['value'] ?? $av['name'] ?? ''),
                ];
            }
            $out[] = [
                'key'             => (string) $key,
                'name'            => (string) ($f['name'] ?? $key),
                'type'            => $type,
                'items'           => $items,
                'allowedValues'   => $allowed,
                'multiple'        => $type === 'array',
                'hasDefaultValue' => !empty($f['hasDefaultValue']),
            ];
        }
        return ['commentRequired' => $commentRequired, 'fields' => $out];
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
        string $transitionMatch = '/progres|curso|doing|trabaj|en uso/iu',
        ?string $issuetypeName = null,
        array $extraFields = []
    ): array {
        $parent = $this->getIssue($parentKey);
        $projectKey = $parent['fields']['project']['key'] ?? '';
        $projectId  = $parent['fields']['project']['id']  ?? '';
        if (!$projectKey) {
            throw new \RuntimeException("No se pudo determinar el proyecto del parent {$parentKey}");
        }

        $subtaskTypes = $this->listSubtaskIssueTypes($projectId);
        if (empty($subtaskTypes)) {
            throw new \RuntimeException("El proyecto {$projectKey} no tiene un tipo de subtarea configurado.");
        }

        $subtaskTypeName = null;
        if ($issuetypeName !== null && $issuetypeName !== '') {
            // Validar que el nombre solicitado sea un subtask type del proyecto.
            foreach ($subtaskTypes as $t) {
                if (mb_strtolower($t['name']) === mb_strtolower($issuetypeName)) {
                    $subtaskTypeName = $t['name'];
                    break;
                }
            }
            if (!$subtaskTypeName) {
                throw new \RuntimeException("El tipo '{$issuetypeName}' no es un tipo de subtarea válido en el proyecto {$projectKey}.");
            }
        } else {
            $subtaskTypeName = $this->preferredSubtaskName($subtaskTypes);
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
        foreach ($extraFields as $k => $v) {
            $fields[$k] = $v;
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

    /**
     * Devuelve los issue types con subtask=true del proyecto, cada uno como
     * ['name' => ..., 'iconUrl' => ..., 'recommended' => bool]. El "recomendado"
     * es el que parece ser una subtarea genérica por nombre (Subtarea, Subtask,
     * Sub-task, Tarea secundaria...). Si ninguno matchea, marca el primero.
     *
     * @return array<int, array{name: string, iconUrl: string, recommended: bool}>
     */
    public function listSubtaskIssueTypes(string $projectIdOrKey): array
    {
        if (!$projectIdOrKey) return [];
        try {
            $types = $this->get('/rest/api/3/issuetype/project', ['projectId' => $projectIdOrKey]);
        } catch (\Throwable $e) {
            try {
                $types = $this->get('/rest/api/3/issuetype');
            } catch (\Throwable $e2) {
                return [];
            }
        }

        $out = [];
        foreach ($types as $t) {
            if (empty($t['subtask']) || empty($t['name'])) continue;
            $out[] = [
                'id'      => (string) ($t['id'] ?? ''),
                'name'    => (string) $t['name'],
                'iconUrl' => (string) ($t['iconUrl'] ?? ''),
            ];
        }

        $recommendedName = $this->preferredSubtaskName($out);
        foreach ($out as &$row) {
            $row['recommended'] = ($row['name'] === $recommendedName);
        }
        unset($row);

        // Recomendado primero, resto por nombre.
        usort($out, function ($a, $b) {
            if ($a['recommended'] !== $b['recommended']) {
                return $a['recommended'] ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $out;
    }

    /**
     * Devuelve los campos requeridos para crear un issue del tipo dado en el proyecto.
     * Excluye los que la app maneja por su cuenta (summary, project, issuetype, parent,
     * description, reporter, attachment, issuelinks).
     *
     * @return array<int, array{
     *   key: string, name: string, type: string, items: ?string,
     *   allowedValues: array, multiple: bool, hasDefaultValue: bool
     * }>
     */
    public function getCreateMetaRequiredFields(
        string $projectIdOrKey,
        string $issuetypeId,
        bool $includeOptional = false
    ): array {
        if (!$projectIdOrKey || !$issuetypeId) return [];
        $skip = ['summary', 'project', 'issuetype', 'parent', 'description',
                 'reporter', 'attachment', 'issuelinks'];

        try {
            $resp = $this->get(
                "/rest/api/3/issue/createmeta/{$projectIdOrKey}/issuetypes/{$issuetypeId}",
                ['maxResults' => 100]
            );
            $rawFields = $resp['fields'] ?? $resp['values'] ?? [];
        } catch (\Throwable $e) {
            // Fallback al endpoint antiguo
            try {
                $resp = $this->get('/rest/api/3/issue/createmeta', [
                    'projectIds'    => $projectIdOrKey,
                    'issuetypeIds'  => $issuetypeId,
                    'expand'        => 'projects.issuetypes.fields',
                ]);
                $rawFields = $resp['projects'][0]['issuetypes'][0]['fields'] ?? [];
                $tmp = [];
                foreach ($rawFields as $key => $f) {
                    $f['fieldId'] = $f['fieldId'] ?? $key;
                    $tmp[] = $f;
                }
                $rawFields = $tmp;
            } catch (\Throwable $e2) {
                return [];
            }
        }

        $out = [];
        foreach ($rawFields as $f) {
            $key = $f['fieldId'] ?? $f['key'] ?? '';
            if (!$key || in_array($key, $skip, true)) continue;
            $isRequired = !empty($f['required']);
            $isCustom   = strpos($key, 'customfield_') === 0;
            // Siempre incluir required (sin default). Opcionalmente, también customfields no-required.
            if (!$isRequired && !($includeOptional && $isCustom)) continue;
            if ($isRequired && !empty($f['hasDefaultValue'])) continue;

            $schema = $f['schema'] ?? [];
            $type = (string) ($schema['type'] ?? 'string');
            $items = isset($schema['items']) ? (string) $schema['items'] : null;
            $allowed = [];
            foreach (($f['allowedValues'] ?? []) as $av) {
                $allowed[] = [
                    'id'    => (string) ($av['id'] ?? ''),
                    'value' => (string) ($av['value'] ?? $av['name'] ?? ''),
                ];
            }
            $out[] = [
                'key'             => $key,
                'name'            => (string) ($f['name'] ?? $key),
                'type'            => $type,
                'items'           => $items,
                'allowedValues'   => $allowed,
                'multiple'        => $type === 'array',
                'required'        => $isRequired,
                'hasDefaultValue' => !empty($f['hasDefaultValue']),
            ];
        }
        return $out;
    }

    /**
     * Devuelve metadata (name, type, allowedValues) de los campos indicados para un issue.
     * Usa /editmeta del issue, que es la fuente de verdad para campos editables.
     *
     * @param string[] $fieldKeys lista de field keys (customfield_xxx, etc.)
     * @return array<int, array{
     *   key: string, name: string, type: string, items: ?string,
     *   allowedValues: array, multiple: bool, required: bool
     * }>
     */
    public function getIssueFieldsMetadata(string $issueKey, array $fieldKeys): array
    {
        if (!$issueKey || empty($fieldKeys)) return [];
        try {
            $resp = $this->get("/rest/api/3/issue/{$issueKey}/editmeta");
        } catch (\Throwable $e) {
            return [];
        }
        $fieldsMap = $resp['fields'] ?? [];

        // Index para fallback por nombre (cuando el caller pasa "Roadmap Activity Type"
        // en vez de la key customfield_xxx).
        $nameToKey = [];
        foreach ($fieldsMap as $k => $f) {
            if (!empty($f['name'])) {
                $nameToKey[mb_strtolower((string) $f['name'])] = $k;
            }
        }

        $out = [];
        $seen = [];
        foreach ($fieldKeys as $input) {
            $input = (string) $input;
            $key = null;
            if (isset($fieldsMap[$input])) {
                $key = $input;
            } else {
                $lower = mb_strtolower($input);
                if (isset($nameToKey[$lower])) {
                    $key = $nameToKey[$lower];
                }
            }
            if (!$key || isset($seen[$key])) continue;
            $seen[$key] = true;

            $f = $fieldsMap[$key];
            $schema = $f['schema'] ?? [];
            $type = (string) ($schema['type'] ?? 'string');
            $items = isset($schema['items']) ? (string) $schema['items'] : null;
            $allowed = [];
            foreach (($f['allowedValues'] ?? []) as $av) {
                $allowed[] = [
                    'id'    => (string) ($av['id'] ?? ''),
                    'value' => (string) ($av['value'] ?? $av['name'] ?? ''),
                ];
            }
            $out[] = [
                'key'           => $key,
                'name'          => (string) ($f['name'] ?? $key),
                'type'          => $type,
                'items'         => $items,
                'allowedValues' => $allowed,
                'multiple'      => $type === 'array',
                'required'      => true,
            ];
        }
        return $out;
    }

    /**
     * Elige el nombre que mejor representa "la" subtarea del proyecto.
     * Prefiere matches exactos a "subtarea/subtask/sub-task/tarea secundaria",
     * luego cualquier nombre que contenga "sub". Si nada matchea, devuelve el primero.
     */
    private function preferredSubtaskName(array $subtaskTypes): ?string
    {
        if (empty($subtaskTypes)) return null;
        $exact = ['subtarea', 'subtask', 'sub-task', 'sub task', 'tarea secundaria'];
        foreach ($subtaskTypes as $t) {
            if (in_array(mb_strtolower($t['name']), $exact, true)) {
                return $t['name'];
            }
        }
        foreach ($subtaskTypes as $t) {
            if (mb_stripos($t['name'], 'sub') !== false) {
                return $t['name'];
            }
        }
        return $subtaskTypes[0]['name'];
    }

    /**
     * Probe: intenta crear una subtarea con summary vacío para forzar que Jira
     * devuelva todos los fieldErrors (incluyendo validators ocultos no expuestos
     * en createmeta). Como summary es siempre required, no se crea el issue.
     * Si por alguna razón se crea, lo borra para no dejar basura.
     *
     * @return array{fieldErrors: array<string,string>}
     */
    public function probeCreateSubtask(string $parentKey, string $issuetypeName): array
    {
        $parent = $this->getIssue($parentKey);
        $projectKey = $parent['fields']['project']['key'] ?? '';
        $projectId  = $parent['fields']['project']['id']  ?? '';
        if (!$projectKey) {
            return ['fieldErrors' => []];
        }
        $subtaskTypes = $this->listSubtaskIssueTypes($projectId);
        $resolvedType = null;
        foreach ($subtaskTypes as $t) {
            if (mb_strtolower($t['name']) === mb_strtolower($issuetypeName)) {
                $resolvedType = $t['name'];
                break;
            }
        }
        if (!$resolvedType) {
            $resolvedType = $this->preferredSubtaskName($subtaskTypes);
        }
        if (!$resolvedType) {
            return ['fieldErrors' => []];
        }

        $fields = [
            'project'   => ['key' => $projectKey],
            'parent'    => ['key' => $parentKey],
            'summary'   => '',
            'issuetype' => ['name' => $resolvedType],
        ];
        try {
            $created = $this->post('/rest/api/3/issue', ['fields' => $fields]);
            if (!empty($created['key'])) {
                try { $this->deleteIssue($created['key']); } catch (\Throwable $e2) {}
            }
            return ['fieldErrors' => [], 'errorMessages' => []];
        } catch (JiraApiException $e) {
            $errors = $e->fieldErrors;
            unset($errors['summary']);
            // Filtrar mensajes irrelevantes (los que solo hablan de summary).
            $messages = array_values(array_filter($e->errorMessages, function ($m) {
                return stripos($m, 'summary') === false
                    && stripos($m, 'resumen') === false;
            }));
            return ['fieldErrors' => $errors, 'errorMessages' => $messages];
        }
    }

    public function deleteIssue(string $issueKey): void
    {
        $url = $this->baseUrl . "/rest/api/3/issue/{$issueKey}";
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
        $httpCode = $result['httpCode'];
        $error    = $result['error'];
        $response = $result['response'];
        if ($error) {
            throw new \RuntimeException("Error de cURL: {$error}");
        }
        if ($httpCode >= 400) {
            self::throwJiraError($httpCode, $response);
        }
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
            self::throwJiraError($httpCode, $response);
        }
    }
}
