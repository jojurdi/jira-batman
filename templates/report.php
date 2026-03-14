<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batman Worklog Tracker - Jira</title>
    <style>
        :root {
            --bg: #0d1117;
            --surface: #161b22;
            --border: #30363d;
            --text: #e6edf3;
            --text-muted: #8b949e;
            --accent: #f0b429;
            --success: #2ea043;
            --danger: #f85149;
            --warning: #d29922;
            --info: #58a6ff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding: 2rem;
        }

        .container { max-width: 1100px; margin: 0 auto; }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo svg { width: 40px; height: 40px; fill: var(--accent); }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info { color: var(--text-muted); font-size: 0.9rem; }

        .btn-settings {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-settings:hover { border-color: var(--accent); color: var(--accent); }

        .filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            align-items: center;
        }

        .filters a, .filters button {
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            color: var(--text);
            background: var(--surface);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.15s;
        }

        .filters a:hover, .filters button:hover { border-color: var(--accent); }
        .filters a.active { background: var(--accent); color: #000; font-weight: 600; border-color: var(--accent); }

        .filters input[type="date"] {
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-size: 0.85rem;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.25rem;
        }

        .card-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .card-value.success { color: var(--success); }
        .card-value.danger { color: var(--danger); }
        .card-value.warning { color: var(--warning); }
        .card-value.info { color: var(--info); }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .day-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
        }

        .day-header:hover { background: rgba(240, 180, 41, 0.04); }

        .day-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .day-badge {
            font-size: 0.75rem;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-complete { background: rgba(46, 160, 67, 0.2); color: var(--success); }
        .badge-partial { background: rgba(210, 153, 34, 0.2); color: var(--warning); }
        .badge-empty { background: rgba(248, 81, 73, 0.2); color: var(--danger); }
        .badge-weekend { background: rgba(139, 148, 158, 0.2); color: var(--text-muted); }

        .day-hours { font-size: 0.95rem; color: var(--text-muted); }
        .day-hours strong { color: var(--text); }

        .day-body { padding: 0 1.25rem 1rem; }

        .worklog-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .worklog-table th {
            text-align: left;
            padding: 0.6rem 0.5rem;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--border);
        }

        .worklog-table td {
            padding: 0.6rem 0.5rem;
            border-bottom: 1px solid rgba(48, 54, 61, 0.5);
        }

        .worklog-table tr:last-child td { border-bottom: none; }

        .issue-key {
            color: var(--info);
            font-weight: 600;
            text-decoration: none;
        }

        .issue-key:hover { text-decoration: underline; }

        .status-badge {
            font-size: 0.72rem;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            background: rgba(88, 166, 255, 0.15);
            color: var(--info);
        }

        .time-cell { font-family: 'SF Mono', 'Fira Code', monospace; font-weight: 600; }

        .empty-day {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-style: italic;
        }

        .error-box {
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid var(--danger);
            border-radius: 10px;
            padding: 1.5rem;
            color: var(--danger);
            margin-bottom: 1.5rem;
        }

        .error-box strong { display: block; margin-bottom: 0.3rem; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal h2 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
            color: var(--accent);
        }

        .modal p.subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-group input {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 0.9rem;
            transition: border-color 0.15s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .form-group .hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid var(--border);
        }

        .btn-primary {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
        }

        .btn-primary:hover { filter: brightness(1.1); }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
        }

        .btn-ghost:hover { color: var(--text); border-color: var(--text-muted); }

        .btn-danger-sm {
            background: transparent;
            color: var(--danger);
            border: 1px solid transparent;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.78rem;
            cursor: pointer;
        }

        .btn-danger-sm:hover { border-color: var(--danger); }

        .setup-prompt {
            text-align: center;
            padding: 4rem 2rem;
        }

        .setup-prompt svg { width: 64px; height: 64px; fill: var(--accent); margin-bottom: 1.5rem; }
        .setup-prompt h2 { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .setup-prompt p { color: var(--text-muted); margin-bottom: 1.5rem; }

        .connected-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            margin-right: 0.3rem;
        }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .summary-cards { grid-template-columns: repeat(2, 1fr); }
            .modal { margin: 1rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="logo">
            <svg viewBox="0 0 100 60" xmlns="http://www.w3.org/2000/svg">
                <path d="M50 5 C35 5 20 20 5 40 C15 35 25 32 35 33 C38 28 44 22 50 18 C56 22 62 28 65 33 C75 32 85 35 95 40 C80 20 65 5 50 5Z"/>
            </svg>
            <h1>Batman Worklog Tracker</h1>
        </div>
        <div class="header-right">
            <?php if ($displayName): ?>
                <div class="user-info">
                    <span class="connected-dot"></span>
                    <?= htmlspecialchars($displayName) ?> &middot; <?= htmlspecialchars($timezone) ?>
                </div>
            <?php endif; ?>
            <button class="btn-settings" onclick="openSettings()" title="Configuraci&oacute;n">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 4.754a3.246 3.246 0 100 6.492 3.246 3.246 0 000-6.492zM5.754 8a2.246 2.246 0 114.492 0 2.246 2.246 0 01-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 01-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 01-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 01.52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 011.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 011.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 01.52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 01-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 01-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 002.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 001.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 00-1.115 2.693l.16.291c.415.764-.421 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 00-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 00-2.692-1.115l-.292.16c-.764.415-1.6-.421-1.184-1.185l.159-.291A1.873 1.873 0 001.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 003.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 002.692-1.115l.094-.319z"/></svg>
            </button>
        </div>
    </header>

    <?php if ($needsSetup): ?>
        <div class="setup-prompt">
            <svg viewBox="0 0 100 60" xmlns="http://www.w3.org/2000/svg">
                <path d="M50 5 C35 5 20 20 5 40 C15 35 25 32 35 33 C38 28 44 22 50 18 C56 22 62 28 65 33 C75 32 85 35 95 40 C80 20 65 5 50 5Z"/>
            </svg>
            <h2>Configura tu conexi&oacute;n a Jira</h2>
            <p>Ingresa tu URL de Jira, email y API token para comenzar a ver tu reporte de horas.</p>
            <button class="btn btn-primary" onclick="openSettings()">Configurar ahora</button>
        </div>
    <?php else: ?>
        <div class="filters">
            <a href="?range=today" class="<?= $rangeType === 'today' ? 'active' : '' ?>">Hoy</a>
            <a href="?range=week" class="<?= $rangeType === 'week' ? 'active' : '' ?>">Semana</a>
            <a href="?range=month" class="<?= $rangeType === 'month' ? 'active' : '' ?>">Mes</a>
            <span style="color: var(--text-muted); margin: 0 0.3rem;">|</span>
            <form method="get" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>">
                <span style="color: var(--text-muted);">a</span>
                <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <strong>Error al conectar con Jira</strong>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($report): ?>
            <?php $s = $report['summary']; ?>
            <div class="summary-cards">
                <div class="card">
                    <div class="card-label">Horas registradas</div>
                    <div class="card-value info"><?= $s['totalLogged'] ?>h</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, $s['completionPercent']) ?>%; background: <?= $s['completionPercent'] >= 100 ? 'var(--success)' : ($s['completionPercent'] >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-label">Horas esperadas</div>
                    <div class="card-value"><?= $s['totalExpected'] ?>h</div>
                </div>
                <div class="card">
                    <div class="card-label">Horas faltantes</div>
                    <div class="card-value <?= $s['totalRemaining'] > 0 ? 'danger' : 'success' ?>">
                        <?= $s['totalRemaining'] > 0 ? $s['totalRemaining'] . 'h' : 'Completo' ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-label">Avance</div>
                    <div class="card-value <?= $s['completionPercent'] >= 100 ? 'success' : ($s['completionPercent'] >= 50 ? 'warning' : 'danger') ?>">
                        <?= $s['completionPercent'] ?>%
                    </div>
                </div>
            </div>

            <?php foreach ($report['days'] as $day): ?>
                <?php
                    if ($day['isWeekend']) {
                        $badgeClass = 'badge-weekend';
                        $badgeText = 'Fin de semana';
                    } elseif ($day['totalHours'] >= $hoursPerDay) {
                        $badgeClass = 'badge-complete';
                        $badgeText = 'Completo';
                    } elseif ($day['totalHours'] > 0) {
                        $badgeClass = 'badge-partial';
                        $badgeText = 'Faltan ' . $day['remainingHours'] . 'h';
                    } else {
                        $badgeClass = 'badge-empty';
                        $badgeText = 'Sin registro';
                    }
                ?>
                <div class="day-section">
                    <div class="day-header" onclick="this.parentElement.querySelector('.day-body').classList.toggle('collapsed')">
                        <div class="day-title">
                            <?= htmlspecialchars($day['dayName']) ?> &mdash; <?= $day['date'] ?>
                            <span class="day-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </div>
                        <div class="day-hours">
                            <strong><?= $day['totalHours'] ?>h</strong> / <?= $day['expectedHours'] ?>h
                        </div>
                    </div>
                    <div class="day-body">
                        <?php if (!empty($day['worklogs'])): ?>
                            <table class="worklog-table">
                                <thead>
                                    <tr>
                                        <th>Tarea</th>
                                        <th>Proyecto</th>
                                        <th>Descripci&oacute;n</th>
                                        <th>Estado</th>
                                        <th>Hora</th>
                                        <th>Tiempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($day['worklogs'] as $wl): ?>
                                        <tr>
                                            <td>
                                                <a class="issue-key"
                                                   href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($wl['issueKey']) ?>"
                                                   target="_blank">
                                                    <?= htmlspecialchars($wl['issueKey']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($wl['project']) ?></td>
                                            <td><?= htmlspecialchars(mb_strimwidth($wl['summary'], 0, 60, '...')) ?></td>
                                            <td><span class="status-badge"><?= htmlspecialchars($wl['status']) ?></span></td>
                                            <td class="time-cell"><?= $wl['started'] ?></td>
                                            <td class="time-cell"><?= htmlspecialchars($wl['timeSpent']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-day">
                                <?= $day['isWeekend'] ? 'Fin de semana' : 'No hay horas registradas este d&iacute;a' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Settings Modal -->
<div class="modal-overlay" id="settingsModal">
    <div class="modal">
        <h2>Configuraci&oacute;n de Jira</h2>
        <p class="subtitle">Las credenciales se guardan en tu navegador (localStorage) y se env&iacute;an como cookies al servidor.</p>

        <div class="form-group">
            <label for="cfg-url">URL de Jira</label>
            <input type="url" id="cfg-url" placeholder="https://miempresa.atlassian.net">
            <div class="hint">La URL base de tu instancia de Jira Cloud</div>
        </div>

        <div class="form-group">
            <label for="cfg-email">Email</label>
            <input type="email" id="cfg-email" placeholder="tu@email.com">
            <div class="hint">El email con el que inicias sesi&oacute;n en Jira</div>
        </div>

        <div class="form-group">
            <label for="cfg-token">API Token</label>
            <input type="password" id="cfg-token" placeholder="Tu API token de Jira">
            <div class="hint">
                Genera uno en
                <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" style="color: var(--info);">
                    id.atlassian.com/manage-profile/security/api-tokens
                </a>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn-danger-sm" onclick="clearCredentials()">Borrar datos</button>
            <div style="flex: 1;"></div>
            <button class="btn btn-ghost" onclick="closeSettings()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveSettings()">Guardar y conectar</button>
        </div>
    </div>
</div>

<script>
const STORAGE_KEYS = {
    url: 'jira_base_url',
    email: 'jira_email',
    token: 'jira_token'
};

function syncLocalStorageToCookies() {
    const url = localStorage.getItem(STORAGE_KEYS.url) || '';
    const email = localStorage.getItem(STORAGE_KEYS.email) || '';
    const token = localStorage.getItem(STORAGE_KEYS.token) || '';

    const maxAge = 365 * 24 * 60 * 60;
    const opts = `;path=/;max-age=${maxAge};SameSite=Lax`;

    document.cookie = `jira_base_url=${encodeURIComponent(url)}${opts}`;
    document.cookie = `jira_email=${encodeURIComponent(email)}${opts}`;
    document.cookie = `jira_token=${encodeURIComponent(token)}${opts}`;
}

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
}

function loadSettingsToForm() {
    document.getElementById('cfg-url').value = localStorage.getItem(STORAGE_KEYS.url) || '';
    document.getElementById('cfg-email').value = localStorage.getItem(STORAGE_KEYS.email) || '';
    document.getElementById('cfg-token').value = localStorage.getItem(STORAGE_KEYS.token) || '';
}

function openSettings() {
    loadSettingsToForm();
    document.getElementById('settingsModal').classList.add('active');
    document.getElementById('cfg-url').focus();
}

function closeSettings() {
    document.getElementById('settingsModal').classList.remove('active');
}

function saveSettings() {
    const url = document.getElementById('cfg-url').value.trim().replace(/\/+$/, '');
    const email = document.getElementById('cfg-email').value.trim();
    const token = document.getElementById('cfg-token').value.trim();

    if (!url || !email || !token) {
        alert('Todos los campos son requeridos');
        return;
    }

    localStorage.setItem(STORAGE_KEYS.url, url);
    localStorage.setItem(STORAGE_KEYS.email, email);
    localStorage.setItem(STORAGE_KEYS.token, token);

    syncLocalStorageToCookies();
    location.reload();
}

function clearCredentials() {
    if (!confirm('Se borrar\u00e1n las credenciales guardadas. \u00bfContinuar?')) return;

    Object.values(STORAGE_KEYS).forEach(k => localStorage.removeItem(k));

    const opts = ';path=/;max-age=0';
    document.cookie = `jira_base_url=${opts}`;
    document.cookie = `jira_email=${opts}`;
    document.cookie = `jira_token=${opts}`;

    location.reload();
}

document.getElementById('settingsModal').addEventListener('click', function(e) {
    if (e.target === this) closeSettings();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSettings();
});

(function bootSync() {
    const lsUrl = localStorage.getItem(STORAGE_KEYS.url) || '';
    const cookieUrl = getCookie('jira_base_url');

    if (lsUrl && lsUrl !== cookieUrl) {
        syncLocalStorageToCookies();
        location.reload();
    }
})();
</script>
</body>
</html>
