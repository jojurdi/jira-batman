<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jira Batman</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #fff;
            color: #1a1a1a;
            line-height: 1.5;
            font-size: 14px;
        }

        .container { max-width: 960px; margin: 0 auto; padding: 2rem 1.5rem; }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e5e5;
        }

        .logo-group { display: flex; align-items: center; gap: 0.5rem; }
        .logo-group svg { width: 32px; height: 20px; }
        header h1 { font-size: 1.1rem; font-weight: 600; color: #333; }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-info { color: #666; font-size: 0.8rem; }

        .btn-settings {
            background: none;
            border: 1px solid #ddd;
            color: #888;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-settings:hover { border-color: #999; color: #555; }

        .filters {
            display: flex;
            gap: 0.35rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
            align-items: center;
        }

        .filters a, .filters button {
            padding: 0.3rem 0.75rem;
            border-radius: 3px;
            font-size: 0.8rem;
            text-decoration: none;
            color: #555;
            background: #f5f5f5;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .filters a:hover, .filters button:hover { background: #eee; }
        .filters a.active { background: #333; color: #fff; border-color: #333; }

        .filters input[type="date"] {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            font-size: 0.8rem;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
        }

        .summary-item {
            padding: 0.9rem 1rem;
            border-right: 1px solid #e5e5e5;
        }

        .summary-item:last-child { border-right: none; }

        .summary-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #999;
            margin-bottom: 0.15rem;
        }

        .summary-value { font-size: 1.4rem; font-weight: 600; color: #1a1a1a; }
        .summary-value.ok { color: #16793a; }
        .summary-value.pending { color: #b35c00; }
        .summary-value.missing { color: #c33; }

        .progress-track {
            width: 100%;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-top: 0.4rem;
        }

        .progress-track-fill {
            height: 100%;
            border-radius: 2px;
            background: #333;
        }

        .day {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }

        .day-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0.9rem;
            background: #fafafa;
            cursor: pointer;
            user-select: none;
        }

        .day-head:hover { background: #f5f5f5; }

        .day-label { font-weight: 500; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }

        .tag {
            font-size: 0.68rem;
            padding: 0.1rem 0.45rem;
            border-radius: 2px;
            font-weight: 500;
        }

        .tag-ok { background: #e6f4ea; color: #16793a; }
        .tag-partial { background: #fff3e0; color: #b35c00; }
        .tag-empty { background: #fde8e8; color: #c33; }
        .tag-weekend { background: #f0f0f0; color: #999; }

        .day-hrs { font-size: 0.8rem; color: #888; font-family: 'SF Mono', 'Consolas', monospace; }
        .day-hrs strong { color: #333; }

        .day-content { padding: 0 0.9rem 0.6rem; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        th {
            text-align: left;
            padding: 0.4rem 0.3rem;
            color: #999;
            font-weight: 500;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #eee;
        }

        td {
            padding: 0.4rem 0.3rem;
            border-bottom: 1px solid #f5f5f5;
            color: #444;
        }

        tr:last-child td { border-bottom: none; }

        .key-link { color: #0052cc; font-weight: 500; text-decoration: none; }
        .key-link:hover { text-decoration: underline; }

        .status-tag {
            font-size: 0.68rem;
            padding: 0.1rem 0.4rem;
            border-radius: 2px;
            background: #e4ecf7;
            color: #0052cc;
        }

        .mono { font-family: 'SF Mono', 'Consolas', monospace; font-weight: 500; }

        .no-data {
            text-align: center;
            padding: 1rem;
            color: #bbb;
            font-size: 0.82rem;
        }

        .error-msg {
            background: #fef2f2;
            border: 1px solid #e5c5c5;
            border-radius: 4px;
            padding: 0.9rem 1rem;
            color: #8b2020;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .error-msg strong { display: block; margin-bottom: 0.2rem; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .overlay.active { display: flex; }

        .dialog {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .dialog h2 { font-size: 1rem; font-weight: 600; color: #333; margin-bottom: 1rem; }

        .field { margin-bottom: 0.9rem; }

        .field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: #666;
            margin-bottom: 0.2rem;
        }

        .field input {
            width: 100%;
            padding: 0.45rem 0.6rem;
            border-radius: 3px;
            border: 1px solid #ddd;
            font-size: 0.85rem;
            color: #333;
        }

        .field input:focus { outline: none; border-color: #999; }

        .field .note {
            font-size: 0.7rem;
            color: #aaa;
            margin-top: 0.15rem;
        }

        .field .note a { color: #0052cc; }

        .dialog-footer {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1.25rem;
            padding-top: 0.75rem;
            border-top: 1px solid #eee;
        }

        .btn-sm {
            padding: 0.35rem 0.9rem;
            border-radius: 3px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid #ddd;
            background: #f5f5f5;
            color: #555;
        }

        .btn-sm:hover { background: #eee; }

        .btn-sm.primary { background: #333; color: #fff; border-color: #333; }
        .btn-sm.primary:hover { background: #444; }

        .btn-sm.danger { color: #c33; border-color: transparent; background: transparent; font-size: 0.75rem; }
        .btn-sm.danger:hover { background: #fef2f2; }

        .setup-msg {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #888;
        }

        .setup-msg h2 { font-size: 1rem; color: #333; margin-bottom: 0.4rem; }
        .setup-msg p { font-size: 0.85rem; margin-bottom: 1rem; }

        @media (max-width: 640px) {
            .container { padding: 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 0.4rem; }
            .summary { grid-template-columns: repeat(2, 1fr); }
            .summary-item:nth-child(2) { border-right: none; }
        }
    </style>
</head>
<body>
<div class="container"
     data-cfg-email="<?= htmlspecialchars($jiraEmail) ?>"
     data-has-token="<?= $hasToken ? '1' : '0' ?>">
    <header>
        <div class="logo-group">
            <svg viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg" fill="#1a1a1a"><path d="M50 2C42 2 33 10 25 20c-4 5-8 10-13 14 2-1 6-2 10-2 3 0 6 1 8 2 2-5 6-11 10-15 2-3 5-6 10-8 5 2 8 5 10 8 4 4 8 10 10 15 2-1 5-2 8-2 4 0 8 1 10 2-5-4-9-9-13-14C87 10 78 2 70 2c-6 0-12 3-20 0z"/></svg>
            <h1>Jira Batman</h1>
        </div>
        <div class="header-right">
            <?php if ($displayName): ?>
                <span class="user-info"><?= htmlspecialchars($displayName) ?> &middot; <?= htmlspecialchars($timezone) ?></span>
            <?php endif; ?>
            <button class="btn-settings" onclick="openSettings()" title="Configuraci&oacute;n">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 4.754a3.246 3.246 0 100 6.492 3.246 3.246 0 000-6.492zM5.754 8a2.246 2.246 0 114.492 0 2.246 2.246 0 01-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 01-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 01-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 01.52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 011.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 011.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 01.52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 01-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 01-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 002.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 001.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 00-1.115 2.693l.16.291c.415.764-.421 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 00-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 00-2.692-1.115l-.292.16c-.764.415-1.6-.421-1.184-1.185l.159-.291A1.873 1.873 0 001.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 003.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 002.692-1.115l.094-.319z"/></svg>
            </button>
        </div>
    </header>

    <?php if ($needsSetup): ?>
        <div class="setup-msg">
            <h2>Sin conexi&oacute;n configurada</h2>
            <p>Configura tu email y API token de Jira para consultar tus horas.</p>
            <button class="btn-sm primary" onclick="openSettings()">Configurar</button>
        </div>
    <?php else: ?>
        <div class="filters">
            <a href="?range=today" class="<?= $rangeType === 'today' ? 'active' : '' ?>">Hoy</a>
            <a href="?range=week" class="<?= $rangeType === 'week' ? 'active' : '' ?>">Semana</a>
            <a href="?range=month" class="<?= $rangeType === 'month' ? 'active' : '' ?>">Mes</a>
            <span style="color: #ccc; margin: 0 0.2rem;">|</span>
            <form method="get" style="display: flex; gap: 0.35rem; align-items: center;">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>">
                <span style="color: #bbb;">&ndash;</span>
                <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>">
                <button type="submit">Ir</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <strong>Error</strong>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($report): ?>
            <?php $s = $report['summary']; ?>
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Registradas</div>
                    <div class="summary-value"><?= $s['totalLogged'] ?>h</div>
                    <div class="progress-track">
                        <div class="progress-track-fill" style="width: <?= min(100, $s['completionPercent']) ?>%;"></div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Esperadas</div>
                    <div class="summary-value"><?= $s['totalExpected'] ?>h</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Faltantes</div>
                    <div class="summary-value <?= $s['totalRemaining'] > 0 ? 'missing' : 'ok' ?>">
                        <?= $s['totalRemaining'] > 0 ? $s['totalRemaining'] . 'h' : '0h' ?>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Avance</div>
                    <div class="summary-value <?= $s['completionPercent'] >= 100 ? 'ok' : ($s['completionPercent'] >= 50 ? 'pending' : 'missing') ?>">
                        <?= $s['completionPercent'] ?>%
                    </div>
                </div>
            </div>

            <?php foreach ($report['days'] as $day): ?>
                <?php
                    if ($day['isWeekend']) {
                        $tagClass = 'tag-weekend';
                        $tagText = 'Descanso';
                    } elseif ($day['totalHours'] >= $hoursPerDay) {
                        $tagClass = 'tag-ok';
                        $tagText = 'Completo';
                    } elseif ($day['totalHours'] > 0) {
                        $tagClass = 'tag-partial';
                        $tagText = '-' . $day['remainingHours'] . 'h';
                    } else {
                        $tagClass = 'tag-empty';
                        $tagText = 'Sin registro';
                    }
                ?>
                <div class="day">
                    <div class="day-head" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
                        <div class="day-label">
                            <?= htmlspecialchars($day['dayName']) ?> <?= date('d/m', strtotime($day['date'])) ?>
                            <span class="tag <?= $tagClass ?>"><?= $tagText ?></span>
                        </div>
                        <div class="day-hrs">
                            <strong><?= $day['totalHours'] ?></strong> / <?= $day['expectedHours'] ?>h
                        </div>
                    </div>
                    <div class="day-content" <?= empty($day['worklogs']) && $day['isWeekend'] ? 'style="display:none"' : '' ?>>
                        <?php if (!empty($day['worklogs'])): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Clave</th>
                                        <th>Proyecto</th>
                                        <th>Descripci&oacute;n</th>
                                        <th>Estado</th>
                                        <th>Inicio</th>
                                        <th>Tiempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($day['worklogs'] as $wl): ?>
                                        <tr>
                                            <td>
                                                <a class="key-link"
                                                   href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($wl['issueKey']) ?>"
                                                   target="_blank">
                                                    <?= htmlspecialchars($wl['issueKey']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($wl['project']) ?></td>
                                            <td><?= htmlspecialchars(mb_strimwidth($wl['summary'], 0, 55, '...')) ?></td>
                                            <td><span class="status-tag"><?= htmlspecialchars($wl['status']) ?></span></td>
                                            <td class="mono"><?= $wl['started'] ?></td>
                                            <td class="mono"><?= htmlspecialchars($wl['timeSpent']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data"><?= $day['isWeekend'] ? 'Descanso' : 'Sin horas registradas' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="overlay" id="cfgModal">
    <div class="dialog">
        <h2>Configuraci&oacute;n</h2>
        <div class="field">
            <label>Jira</label>
            <input type="text" value="<?= htmlspecialchars($jiraBaseUrl) ?>" readonly
                   style="background:#f5f5f5; color:#999; cursor:default;">
        </div>
        <div class="field">
            <label for="cfg-email">Email</label>
            <input type="email" id="cfg-email" placeholder="tu@email.com">
        </div>
        <div class="field">
            <label for="cfg-token">API Token</label>
            <div style="position:relative;">
                <input type="password" id="cfg-token" placeholder="Pega aqu&iacute; tu token">
                <span id="token-indicator"></span>
            </div>
            <div class="note">
                Obt&eacute;n uno en <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank">id.atlassian.com</a>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn-sm danger" onclick="clearCfg()">Borrar</button>
            <div style="flex:1"></div>
            <button class="btn-sm" onclick="closeCfg()">Cancelar</button>
            <button class="btn-sm primary" onclick="saveCfg()">Guardar</button>
        </div>
    </div>
</div>

<script>
(function() {
    var LS_EMAIL = 'jira_email';
    var LS_TOKEN = 'jira_token';

    function setCookie(name, value) {
        var opts = ';path=/;max-age=' + (365 * 86400) + ';SameSite=Lax';
        document.cookie = name + '=' + encodeURIComponent(value) + opts;
    }

    function deleteCookie(name) {
        document.cookie = name + '=;path=/;max-age=0';
    }

    function loadForm() {
        var savedEmail = localStorage.getItem(LS_EMAIL) || '';
        var savedToken = localStorage.getItem(LS_TOKEN) || '';
        var serverEmail = document.querySelector('.container').dataset.cfgEmail || '';
        var serverHasToken = document.querySelector('.container').dataset.hasToken === '1';

        document.getElementById('cfg-email').value = savedEmail || serverEmail;
        document.getElementById('cfg-token').value = savedToken;

        var ind = document.getElementById('token-indicator');
        ind.style.cssText = 'position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:0.7rem;';
        if (savedToken) {
            ind.textContent = 'Guardado';
            ind.style.color = '#16793a';
        } else if (serverHasToken) {
            ind.textContent = 'Activo (.env)';
            ind.style.color = '#0052cc';
        } else {
            ind.textContent = 'Sin token';
            ind.style.color = '#c33';
        }
    }

    window.openSettings = function() {
        loadForm();
        document.getElementById('cfgModal').classList.add('active');
        document.getElementById('cfg-email').focus();
    };

    window.closeCfg = function() {
        document.getElementById('cfgModal').classList.remove('active');
    };

    window.saveCfg = function() {
        var email = document.getElementById('cfg-email').value.trim();
        var token = document.getElementById('cfg-token').value.trim();

        if (!email) { alert('El email es requerido.'); return; }
        if (!token) { alert('El API token es requerido.'); return; }

        localStorage.setItem(LS_EMAIL, email);
        localStorage.setItem(LS_TOKEN, token);
        setCookie(LS_EMAIL, email);
        setCookie(LS_TOKEN, token);
        location.reload();
    };

    window.clearCfg = function() {
        if (!confirm('Se borrar\u00e1n las credenciales del navegador. \u00bfContinuar?')) return;
        localStorage.removeItem(LS_EMAIL);
        localStorage.removeItem(LS_TOKEN);
        deleteCookie(LS_EMAIL);
        deleteCookie(LS_TOKEN);
        location.reload();
    };

    document.getElementById('cfgModal').addEventListener('click', function(e) {
        if (e.target === this) closeCfg();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCfg();
    });
})();
</script>
</body>
</html>
