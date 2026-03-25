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

        .hier-init {
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            margin-bottom: 0.35rem;
            overflow: hidden;
        }

        .hier-init-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0.9rem;
            background: #f5f5f5;
            cursor: pointer;
            user-select: none;
            font-weight: 600;
            font-size: 0.83rem;
            color: #333;
        }

        .hier-init-head:hover { background: #eeeeee; }

        .hier-epic {
            border-top: 1px solid #f0f0f0;
        }

        .hier-epic-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.45rem 0.9rem 0.45rem 2rem;
            background: #fafafa;
            cursor: pointer;
            user-select: none;
            font-size: 0.8rem;
            color: #555;
        }

        .hier-epic-head:hover { background: #f5f5f5; }

        .hier-epic-body table { margin: 0; }

        .hier-epic-body td, .hier-epic-body th {
            padding-left: 2.5rem;
        }

        .hier-epic-body th:first-child,
        .hier-epic-body td:first-child { padding-left: 2.5rem; }

        .hier-hrs {
            font-family: 'SF Mono', 'Consolas', monospace;
            font-size: 0.78rem;
            color: #888;
            white-space: nowrap;
        }

        .hier-hrs strong { color: #333; }

        .btn-add-worklog {
            background: none;
            border: 1px solid #ddd;
            color: #aaa;
            width: 22px;
            height: 22px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            flex-shrink: 0;
        }

        .btn-add-worklog:hover { border-color: #0052cc; color: #0052cc; background: #f0f4ff; }

        .btn-edit-wl {
            background: none;
            border: none;
            color: #ccc;
            cursor: pointer;
            padding: 0 0.2rem;
            font-size: 0.85rem;
            line-height: 1;
        }

        .btn-edit-wl:hover { color: #0052cc; }

        .btn-del-wl {
            background: none;
            border: none;
            color: #ddd;
            cursor: pointer;
            padding: 0 0.2rem;
            font-size: 0.85rem;
            line-height: 1;
        }

        .btn-del-wl:hover { color: #c33; }

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
            <svg viewBox="0 0 300 150" xmlns="http://www.w3.org/2000/svg" fill="#1a1a1a"><path d="M150 10c-4 18-8 32-14 42-6 10-14 18-24 24l-10 6-14-30-16 22-24-12-38 48c20-10 38-16 56-16 12 0 22 3 30 8 8 6 16 14 22 26l4 8h2l2-4 4-8c6-12 14-20 22-26 8-5 18-8 30-8 18 0 36 6 56 16l-38-48-24 12-16-22-14 30-10-6c-10-6-18-14-24-24-6-10-10-24-14-42z"/></svg>
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

            <?php if (!empty($report['byHierarchy'])): ?>
            <div class="section-head" onclick="toggleSection('byHier')" style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;margin-bottom:0.4rem;user-select:none;">
                <span style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;">Concentrado por jerarqu&iacute;a</span>
                <span id="byHier-arrow" style="font-size:0.7rem;color:#bbb;">&#9650;</span>
            </div>
            <div id="byHier-body" style="margin-bottom:1.5rem;">
                <?php foreach ($report['byHierarchy'] as $iIdx => $init): ?>
                <div class="hier-init">
                    <div class="hier-init-head" onclick="toggleHier('i<?= $iIdx ?>')">
                        <span>
                            <?php if ($init['key']): ?>
                                <a class="key-link" href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($init['key']) ?>" target="_blank" onclick="event.stopPropagation()"><?= htmlspecialchars($init['key']) ?></a>
                                &nbsp;&middot;&nbsp;
                            <?php endif; ?>
                            <?= htmlspecialchars($init['summary']) ?>
                        </span>
                        <span class="hier-hrs"><strong><?= $init['totalHours'] ?></strong>h</span>
                    </div>
                    <div id="hier-i<?= $iIdx ?>">
                        <?php foreach ($init['epics'] as $eIdx => $epic): ?>
                        <div class="hier-epic">
                            <div class="hier-epic-head" onclick="toggleHier('i<?= $iIdx ?>e<?= $eIdx ?>')">
                                <span>
                                    <?php if ($epic['key']): ?>
                                        <a class="key-link" href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($epic['key']) ?>" target="_blank" onclick="event.stopPropagation()"><?= htmlspecialchars($epic['key']) ?></a>
                                        &nbsp;&middot;&nbsp;
                                    <?php endif; ?>
                                    <?= htmlspecialchars($epic['summary']) ?>
                                </span>
                                <span class="hier-hrs"><strong><?= $epic['totalHours'] ?></strong>h</span>
                            </div>
                            <div id="hier-i<?= $iIdx ?>e<?= $eIdx ?>" class="hier-epic-body">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Clave</th>
                                            <th>Proyecto</th>
                                            <th>Descripci&oacute;n</th>
                                            <th>Estado</th>
                                            <th style="text-align:right;">Horas</th>
                                            <th style="text-align:right;min-width:90px;">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($epic['issues'] as $iss): ?>
                                            <?php $pct = $report['summary']['totalLogged'] > 0
                                                ? round($iss['totalHours'] / $report['summary']['totalLogged'] * 100, 1)
                                                : 0; ?>
                                            <tr>
                                                <td><a class="key-link" href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($iss['issueKey']) ?>" target="_blank"><?= htmlspecialchars($iss['issueKey']) ?></a></td>
                                                <td><?= htmlspecialchars($iss['project']) ?></td>
                                                <td><?= htmlspecialchars(mb_strimwidth($iss['summary'], 0, 55, '...')) ?></td>
                                                <td><span class="status-tag"><?= htmlspecialchars($iss['status']) ?></span></td>
                                                <td class="mono" style="text-align:right;"><?= $iss['totalHours'] ?>h</td>
                                                <td style="text-align:right;">
                                                    <div style="display:flex;align-items:center;gap:0.4rem;justify-content:flex-end;">
                                                        <div style="flex:1;height:4px;background:#eee;border-radius:2px;min-width:40px;">
                                                            <div style="height:100%;background:#333;border-radius:2px;width:<?= $pct ?>%;"></div>
                                                        </div>
                                                        <span style="font-size:0.75rem;color:#888;white-space:nowrap;"><?= $pct ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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
                <div class="day" data-date="<?= $day['date'] ?>">
                    <div class="day-head" onclick="toggleDay(this)">
                        <div class="day-label">
                            <?= htmlspecialchars($day['dayName']) ?> <?= date('d/m', strtotime($day['date'])) ?>
                            <span class="tag <?= $tagClass ?>"><?= $tagText ?></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <div class="day-hrs">
                                <strong><?= $day['totalHours'] ?></strong> / <?= $day['expectedHours'] ?>h
                            </div>
                            <?php if (!$day['isWeekend']): ?>
                            <button class="btn-add-worklog"
                                    onclick="openAddWorklog(event,'<?= $day['date'] ?>')"
                                    title="Agregar registro">+</button>
                            <?php endif; ?>
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
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($day['worklogs'] as $wl): ?>
                                        <tr data-wl-id="<?= htmlspecialchars($wl['id']) ?>" data-wl-seconds="<?= (int)$wl['timeSpentSeconds'] ?>">
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
                                            <td style="white-space:nowrap;">
                                                <?php if ($wl['id']): ?>
                                                <button class="btn-edit-wl"
                                                        onclick="openEditWorklog(event,'<?= htmlspecialchars($wl['issueKey']) ?>','<?= htmlspecialchars($wl['id']) ?>','<?= $day['date'] ?>','<?= $wl['started'] ?>','<?= htmlspecialchars($wl['timeSpent']) ?>')"
                                                        title="Editar">&#9998;</button>
                                                <button class="btn-del-wl"
                                                        onclick="deleteWorklog(event,'<?= htmlspecialchars($wl['issueKey']) ?>','<?= htmlspecialchars($wl['id']) ?>','<?= htmlspecialchars($wl['timeSpent']) ?>')"
                                                        title="Eliminar">&#10005;</button>
                                                <?php endif; ?>
                                            </td>
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

<div class="overlay" id="addWorklogModal">
    <div class="dialog">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 id="wl-title" style="margin-bottom:0;">Registrar tiempo</h2>
            <button onclick="closeAddWorklog()" style="background:none;border:none;font-size:1.2rem;color:#aaa;cursor:pointer;line-height:1;padding:0 0.2rem;">&times;</button>
        </div>
        <input type="hidden" id="wl-mode" value="add">
        <input type="hidden" id="wl-worklog-id" value="">
        <div class="field">
            <label for="wl-key">Clave de tarea</label>
            <input type="text" id="wl-key" placeholder="PROJ-123" style="text-transform:uppercase">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.7rem;">
            <div class="field">
                <label for="wl-date">Fecha</label>
                <input type="date" id="wl-date">
            </div>
            <div class="field">
                <label for="wl-time">Hora inicio</label>
                <input type="time" id="wl-time" value="09:00">
            </div>
        </div>
        <div class="field">
            <label for="wl-duration">Duración</label>
            <input type="text" id="wl-duration" placeholder="ej. 2h 30m  ·  1h  ·  45m">
        </div>
        <div id="wl-error" style="display:none;color:#c33;font-size:0.8rem;margin-top:0.25rem;"></div>
        <div class="dialog-footer">
            <button class="btn-sm" onclick="closeAddWorklog()">Cancelar</button>
            <button class="btn-sm primary" id="wl-submit" onclick="submitWorklog()">Registrar</button>
        </div>
    </div>
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
var JIRA_BASE_URL = '<?= htmlspecialchars($jiraBaseUrl) ?>';
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCfg();
    });

    window.toggleHier = function(id) {
        var el = document.getElementById('hier-' + id);
        if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
    };

    window.toggleSection = function(id) {
        var body  = document.getElementById(id + '-body');
        var arrow = document.getElementById(id + '-arrow');
        var hidden = body.style.display === 'none';
        body.style.display  = hidden ? '' : 'none';
        arrow.innerHTML     = hidden ? '&#9650;' : '&#9660;';
    };

    window.toggleDay = function(head) {
        var content = head.nextElementSibling;
        content.style.display = content.style.display === 'none' ? 'block' : 'none';
    };

    function openWorklogModal(mode, issueKey, worklogId, date, time, duration) {
        document.getElementById('wl-mode').value = mode;
        document.getElementById('wl-worklog-id').value = worklogId || '';
        document.getElementById('wl-key').value = issueKey || '';
        document.getElementById('wl-key').readOnly = (mode === 'edit');
        document.getElementById('wl-key').style.background = (mode === 'edit') ? '#f5f5f5' : '';
        document.getElementById('wl-key').style.color = (mode === 'edit') ? '#999' : '';
        document.getElementById('wl-date').value = date || '';
        document.getElementById('wl-time').value = time || '09:00';
        document.getElementById('wl-duration').value = duration || '';
        document.getElementById('wl-error').style.display = 'none';
        document.getElementById('wl-title').textContent = (mode === 'edit') ? 'Editar registro' : 'Registrar tiempo';
        var btn = document.getElementById('wl-submit');
        btn.disabled = false;
        btn.textContent = (mode === 'edit') ? 'Guardar' : 'Registrar';
        document.getElementById('addWorklogModal').classList.add('active');
        setTimeout(function() {
            var focus = (mode === 'edit') ? 'wl-duration' : 'wl-key';
            document.getElementById(focus).focus();
        }, 50);
    }

    window.openAddWorklog = function(e, date) {
        e.stopPropagation();
        openWorklogModal('add', '', '', date, '09:00', '');
    };

    window.openEditWorklog = function(e, issueKey, worklogId, date, time, duration) {
        e.stopPropagation();
        openWorklogModal('edit', issueKey, worklogId, date, time, duration);
    };

    window.closeAddWorklog = function() {
        document.getElementById('addWorklogModal').classList.remove('active');
    };

    // Restaurar scroll si venimos de un add
    (function() {
        var y = sessionStorage.getItem('jb_scrollY');
        if (y !== null) { window.scrollTo(0, parseInt(y)); sessionStorage.removeItem('jb_scrollY'); }
    })();

    function parseDurToSeconds(dur) {
        var s = 0;
        var h = dur.match(/(\d+(?:\.\d+)?)\s*h/i);
        var m = dur.match(/(\d+)\s*m/i);
        if (h) s += Math.round(parseFloat(h[1]) * 3600);
        if (m) s += parseInt(m[1]) * 60;
        return s;
    }

    function fmtTimeSpent(s) {
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        if (h && m) return h + 'h ' + m + 'm';
        if (h) return h + 'h';
        return m + 'm';
    }

    function fmtHours(s) {
        return Math.round(s / 3600 * 100) / 100;
    }

    function updateWorklogInDOM(worklogId, newTime, durationStr) {
        var row = document.querySelector('tr[data-wl-id="' + worklogId + '"]');
        if (!row) return false;

        var newSec = parseDurToSeconds(durationStr);
        row.dataset.wlSeconds = newSec;
        var cells = row.querySelectorAll('td');
        cells[4].textContent = newTime;
        cells[5].textContent = fmtTimeSpent(newSec);

        // Recalcular total del día
        var dayContent = row.closest('.day-content');
        var dayHead    = dayContent.previousElementSibling;
        var totalSec   = 0;
        dayContent.querySelectorAll('tr[data-wl-id]').forEach(function(r) {
            totalSec += parseInt(r.dataset.wlSeconds || 0);
        });
        var totalHours    = fmtHours(totalSec);
        var dayHrsEl      = dayHead.querySelector('.day-hrs');
        var expectedHours = parseFloat(dayHrsEl.textContent.split('/')[1]) || 8;
        dayHrsEl.querySelector('strong').textContent = totalHours;

        var tag = dayHead.querySelector('.tag');
        if (totalHours >= expectedHours) {
            tag.className = 'tag tag-ok'; tag.textContent = 'Completo';
        } else if (totalHours > 0) {
            var rem = Math.round((expectedHours - totalHours) * 100) / 100;
            tag.className = 'tag tag-partial'; tag.textContent = '-' + rem + 'h';
        } else {
            tag.className = 'tag tag-empty'; tag.textContent = 'Sin registro';
        }
        return true;
    }

    function truncate(str, n) {
        return str.length > n ? str.slice(0, n) + '...' : str;
    }

    function insertWorklogInDOM(data, date, time, durationStr) {
        var dayEl = document.querySelector('.day[data-date="' + date + '"]');
        if (!dayEl) {
            // El día no está visible en el rango actual, recargar
            window.location.href = window.location.pathname + window.location.search;
            return;
        }

        var newSec     = parseDurToSeconds(durationStr);
        var dayContent = dayEl.querySelector('.day-content');
        var dayHead    = dayEl.querySelector('.day-head');

        // Asegurar que el día está visible
        dayContent.style.display = 'block';

        // Crear tabla si no existe (día sin registros previos)
        var tbody = dayContent.querySelector('tbody');
        if (!tbody) {
            dayContent.innerHTML =
                '<table><thead><tr>' +
                '<th>Clave</th><th>Proyecto</th><th>Descripci\u00f3n</th>' +
                '<th>Estado</th><th>Inicio</th><th>Tiempo</th><th></th>' +
                '</tr></thead><tbody></tbody></table>';
            tbody = dayContent.querySelector('tbody');
        }

        var issueKey  = data.issueKey || '';
        var editArgs  = [
            "'" + issueKey + "'",
            "'" + (data.worklogId || '') + "'",
            "'" + date + "'",
            "'" + time + "'",
            "'" + durationStr.replace(/'/g, "\\'") + "'"
        ].join(',');

        var tr = document.createElement('tr');
        tr.dataset.wlId      = data.worklogId || '';
        tr.dataset.wlSeconds = newSec;
        tr.innerHTML =
            '<td><a class="key-link" href="' + JIRA_BASE_URL + '/browse/' + issueKey + '" target="_blank">' + issueKey + '</a></td>' +
            '<td>' + (data.project || '') + '</td>' +
            '<td>' + truncate(data.summary || '', 55) + '</td>' +
            '<td><span class="status-tag">' + (data.status || '') + '</span></td>' +
            '<td class="mono">' + time + '</td>' +
            '<td class="mono">' + fmtTimeSpent(newSec) + '</td>' +
            '<td>' + (data.worklogId ? '<button class="btn-edit-wl" onclick="openEditWorklog(event,' + editArgs + ')" title="Editar">&#9998;</button>' : '') + '</td>';

        // Insertar en orden por hora
        var rows = tbody.querySelectorAll('tr');
        var inserted = false;
        for (var i = 0; i < rows.length; i++) {
            var rowTime = rows[i].querySelectorAll('td')[4].textContent;
            if (time < rowTime) { tbody.insertBefore(tr, rows[i]); inserted = true; break; }
        }
        if (!inserted) tbody.appendChild(tr);

        // Actualizar totales del día
        var totalSec = 0;
        tbody.querySelectorAll('tr[data-wl-id]').forEach(function(r) {
            totalSec += parseInt(r.dataset.wlSeconds || 0);
        });
        var totalHours    = fmtHours(totalSec);
        var dayHrsEl      = dayHead.querySelector('.day-hrs');
        var expectedHours = parseFloat(dayHrsEl.textContent.split('/')[1]) || 8;
        dayHrsEl.querySelector('strong').textContent = totalHours;

        var tag = dayHead.querySelector('.tag');
        if (totalHours >= expectedHours) {
            tag.className = 'tag tag-ok'; tag.textContent = 'Completo';
        } else if (totalHours > 0) {
            var rem = Math.round((expectedHours - totalHours) * 100) / 100;
            tag.className = 'tag tag-partial'; tag.textContent = '-' + rem + 'h';
        } else {
            tag.className = 'tag tag-empty'; tag.textContent = 'Sin registro';
        }
    }

    window.submitWorklog = function() {
        var mode     = document.getElementById('wl-mode').value;
        var key      = document.getElementById('wl-key').value.trim().toUpperCase();
        var date     = document.getElementById('wl-date').value;
        var time     = document.getElementById('wl-time').value;
        var duration = document.getElementById('wl-duration').value.trim();

        if (!key)      { showWlError('Ingresa la clave de la tarea'); return; }
        if (!date)     { showWlError('Selecciona la fecha'); return; }
        if (!duration) { showWlError('Ingresa la duración'); return; }

        document.getElementById('wl-error').style.display = 'none';
        var btn = document.getElementById('wl-submit');
        btn.disabled = true;
        btn.textContent = mode === 'edit' ? 'Guardando...' : 'Registrando...';

        var payload = { issueKey: key, date: date, time: time, duration: duration };
        if (mode === 'edit') {
            payload.action    = 'update_worklog';
            payload.worklogId = document.getElementById('wl-worklog-id').value;
        } else {
            payload.action = 'add_worklog';
        }

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                closeAddWorklog();
                if (mode === 'edit') {
                    updateWorklogInDOM(payload.worklogId, time, duration);
                } else {
                    data.issueKey = key;
                    insertWorklogInDOM(data, date, time, duration);
                }
            } else {
                showWlError(data.error || 'Error desconocido');
                btn.disabled = false;
                btn.textContent = mode === 'edit' ? 'Guardar' : 'Registrar';
            }
        })
        .catch(function() {
            showWlError('Error de conexión');
            btn.disabled = false;
            btn.textContent = mode === 'edit' ? 'Guardar' : 'Registrar';
        });
    };

    window.deleteWorklog = function(e, issueKey, worklogId, timeSpent) {
        e.stopPropagation();
        if (!confirm('¿Eliminar el registro de ' + timeSpent + ' en ' + issueKey + '?')) return;

        var row = document.querySelector('tr[data-wl-id="' + worklogId + '"]');

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_worklog', issueKey: issueKey, worklogId: worklogId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                if (!row) return;
                var oldSec     = parseInt(row.dataset.wlSeconds || 0);
                var dayContent = row.closest('.day-content');
                var dayHead    = row.closest('.day').querySelector('.day-head');
                row.remove();

                // Actualizar total del día
                var remaining = dayContent.querySelectorAll('tr[data-wl-id]');
                if (remaining.length === 0) {
                    var tbody = dayContent.querySelector('tbody');
                    if (tbody) {
                        tbody.closest('table').remove();
                        var noData = document.createElement('div');
                        noData.className = 'no-data';
                        noData.textContent = 'Sin horas registradas';
                        dayContent.appendChild(noData);
                    }
                }
                var totalSec = 0;
                dayContent.querySelectorAll('tr[data-wl-id]').forEach(function(r) {
                    totalSec += parseInt(r.dataset.wlSeconds || 0);
                });
                var totalHours    = fmtHours(totalSec);
                var dayHrsEl      = dayHead.querySelector('.day-hrs');
                var expectedHours = parseFloat(dayHrsEl.textContent.split('/')[1]) || 8;
                dayHrsEl.querySelector('strong').textContent = totalHours;

                var tag = dayHead.querySelector('.tag');
                if (totalHours >= expectedHours) {
                    tag.className = 'tag tag-ok'; tag.textContent = 'Completo';
                } else if (totalHours > 0) {
                    var rem = Math.round((expectedHours - totalHours) * 100) / 100;
                    tag.className = 'tag tag-partial'; tag.textContent = '-' + rem + 'h';
                } else {
                    tag.className = 'tag tag-empty'; tag.textContent = 'Sin registro';
                }
            } else {
                alert('Error al eliminar: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(function() { alert('Error de conexión'); });
    };

    function showWlError(msg) {
        var el = document.getElementById('wl-error');
        el.textContent = msg;
        el.style.display = 'block';
    }
})();
</script>
</body>
</html>
