<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars(\App\AuthSession::appBaseUrl()) ?>">
    <title>Jira Batman</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 96' fill='%231a1a1a'><path d='M128 4c-3 9-7 17-12 24-5-3-11-3-16-1-3-7-7-13-12-19-3 7-6 14-9 21-12-6-26-7-39-2-12 4-22 12-31 21 13-2 26-2 38 3 12 5 22 14 28 26 7-9 18-13 30-12 6 9 12 18 18 28 1 1 3 1 4 0 6-10 12-19 18-28 12-1 23 3 30 12 6-12 16-21 28-26 12-5 25-5 38-3-9-9-19-17-31-21-13-5-27-4-39 2-3-7-6-14-9-21-5 6-9 12-12 19-5-2-11-2-16 1-5-7-9-15-12-24z'/></svg>">
    <style>
        :root {
            --color-bg: #ffffff;
            --color-bg-alt: #fafbfc;
            --color-bg-subtle: #f4f5f7;
            --color-border: #e6e8ec;
            --color-border-strong: #c1c7d0;
            --color-text: #172b4d;
            --color-text-muted: #6b778c;
            --color-text-subtle: #97a0af;
            --color-primary: #0052cc;
            --color-primary-hover: #0747a6;
            --color-primary-bg: #deebff;
            --color-success: #00875a;
            --color-success-bg: #e3fcef;
            --color-warning: #ff8b00;
            --color-warning-bg: #fff7e6;
            --color-danger: #de350b;
            --color-danger-bg: #ffebe6;
        }

        [data-theme="dark"] {
            --color-bg: #1e2128;
            --color-bg-alt: #1a1d24;
            --color-bg-subtle: #262a32;
            --color-border: #353a44;
            --color-border-strong: #4a505b;
            --color-text: #e6e8ec;
            --color-text-muted: #a0a8b3;
            --color-text-subtle: #6b778c;
            --color-primary: #4c9aff;
            --color-primary-hover: #79b1ff;
            --color-primary-bg: #1a3a6e;
            --color-success: #36b37e;
            --color-success-bg: #1a3a2c;
            --color-warning: #ffab00;
            --color-warning-bg: #3d2e0e;
            --color-danger: #ff5630;
            --color-danger-bg: #3d1c14;
        }
        :root {
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.5rem;
            --space-6: 2rem;
            --radius-sm: 4px;
            --radius-md: 6px;
            --radius-lg: 8px;
            --radius-pill: 999px;
            --shadow-sm: 0 1px 2px rgba(9, 30, 66, 0.06), 0 0 0 1px rgba(9, 30, 66, 0.04);
            --shadow-md: 0 3px 6px rgba(9, 30, 66, 0.08), 0 0 0 1px rgba(9, 30, 66, 0.06);
            --shadow-lg: 0 12px 24px rgba(9, 30, 66, 0.12), 0 0 0 1px rgba(9, 30, 66, 0.06);
            --t-fast: 120ms cubic-bezier(0.2, 0, 0, 1);
            --t-default: 200ms cubic-bezier(0.2, 0, 0, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        *:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; border-radius: 2px; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: var(--color-bg-alt);
            color: var(--color-text);
            line-height: 1.5;
            font-size: 14px;
        }

        a { color: var(--color-primary); }

        .container { max-width: 1555px; margin: 0 auto; padding: var(--space-6) var(--space-5); }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-4);
            border-bottom: 1px solid var(--color-border);
        }

        .logo-group { display: flex; align-items: center; gap: var(--space-3); }
        .logo-group svg {
            width: 42px;
            height: auto;
            color: var(--color-text);
            transition: transform var(--t-default);
        }
        .logo-group:hover svg { transform: scale(1.08); }
        header h1 { font-size: 1.05rem; font-weight: 600; color: var(--color-text); letter-spacing: -0.01em; }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .user-info {
            color: var(--color-text-muted);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        .user-info .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--color-success-bg);
            color: var(--color-success);
            padding: 2px 8px;
            border-radius: var(--radius-pill);
            font-size: 0.7rem;
            font-weight: 600;
        }
        .user-info .badge::before {
            content: '';
            width: 6px; height: 6px;
            background: var(--color-success);
            border-radius: 50%;
        }

        .btn-settings {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--t-fast);
            text-decoration: none;
        }
        .btn-settings:hover {
            background: var(--color-bg-subtle);
            border-color: var(--color-border-strong);
            color: var(--color-text);
        }

        .filters {
            display: flex;
            gap: var(--space-2);
            flex-wrap: wrap;
            margin-bottom: var(--space-5);
            align-items: center;
            padding: 6px;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }

        .filters a, .filters button {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--color-text-muted);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
            transition: all var(--t-fast);
        }

        .filters a:hover, .filters button:hover {
            background: var(--color-bg-subtle);
            color: var(--color-text);
        }
        .filters a.active {
            background: var(--color-primary-bg);
            color: var(--color-primary);
            font-weight: 600;
        }

        .filters input[type="date"] {
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 0.82rem;
            font-family: inherit;
            transition: all var(--t-fast);
        }
        .filters input[type="date"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-bg);
        }
        .filters .divider {
            width: 1px;
            height: 18px;
            background: var(--color-border);
            margin: 0 4px;
        }

        .filterbar {
            display: flex;
            gap: var(--space-2);
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: var(--space-4);
            padding: var(--space-2) var(--space-3);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
        }
        .filterbar-search {
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 4px 10px;
            min-width: 220px;
            flex: 1;
            color: var(--color-text-subtle);
            transition: all var(--t-fast);
        }
        .filterbar-search:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-bg);
            color: var(--color-primary);
        }
        .filterbar-search input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--color-text);
            width: 100%;
            padding: 4px 0;
        }
        .filterbar-search input::placeholder { color: var(--color-text-subtle); }

        .select-filter {
            padding: 6px 24px 6px 10px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-bg);
            color: var(--color-text);
            font-size: 0.82rem;
            font-family: inherit;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path fill='%236b778c' d='M5 6L0 0h10z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 8px center;
            transition: all var(--t-fast);
            max-width: 220px;
        }
        .select-filter:hover { border-color: var(--color-border-strong); }
        .select-filter:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px var(--color-primary-bg); }
        .select-filter.has-value { background-color: var(--color-primary-bg); color: var(--color-primary); border-color: var(--color-primary); font-weight: 500; }

        .filter-count {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            font-feature-settings: "tnum";
        }
        .filter-count[hidden] { display: none; }

        .hr-day-toggle {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-bg);
            font-size: 0.8rem;
            color: var(--color-text-subtle);
            user-select: none;
            cursor: pointer;
        }
        .hr-day-label {
            cursor: pointer;
            padding: 0 4px;
            font-weight: 500;
            transition: color var(--t-fast);
        }
        .hr-day-label:hover { color: var(--color-text); }
        .hr-day-label.active { color: var(--color-primary); font-weight: 700; }
        .hr-day-sep { color: var(--color-border); }

        .summary {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0;
            margin-bottom: var(--space-5);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .summary-item {
            padding: var(--space-4);
            border-right: 1px solid var(--color-border);
        }

        .summary-item:last-child { border-right: none; }

        .summary-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-subtle);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-text);
            letter-spacing: -0.02em;
            font-feature-settings: "tnum";
        }
        .summary-value.ok { color: var(--color-success); }
        .summary-value.pending { color: var(--color-warning); }
        .summary-value.missing { color: var(--color-danger); }

        .summary-delta {
            font-size: 0.72rem;
            color: var(--color-text-subtle);
            margin-top: 4px;
            font-feature-settings: "tnum";
        }
        .summary-delta.up   { color: var(--color-success); }
        .summary-delta.down { color: var(--color-warning); }
        .summary-delta.loading {
            color: var(--color-text-subtle);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            opacity: 0.7;
        }

        .heatmap-section {
            margin-bottom: var(--space-5);
            padding: var(--space-4);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        .heatmap-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-3);
        }
        .heatmap-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-subtle);
            font-weight: 600;
        }
        .heatmap-legend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
        }
        .heatmap-wrap {
            display: flex;
            gap: 6px;
            overflow-x: auto;
        }
        .heatmap-rows-labels {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding-top: 1px;
            font-size: 0.65rem;
            color: var(--color-text-subtle);
            line-height: 14px;
        }
        .heatmap-rows-labels span {
            height: 14px;
            text-align: center;
            font-weight: 500;
        }
        .heatmap-grid {
            display: flex;
            gap: 3px;
        }
        .heatmap-col {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .heatmap-cell {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            background: var(--color-bg-subtle);
            transition: transform var(--t-fast);
            display: inline-block;
        }
        .heatmap-cell:hover {
            transform: scale(1.3);
            box-shadow: 0 0 0 1px var(--color-border-strong);
        }
        .heatmap-cell.empty { background: transparent; }
        .heatmap-cell.h0 { background: var(--color-bg-subtle); }
        .heatmap-cell.h1 { background: #c6e9d3; }
        .heatmap-cell.h2 { background: #84d6a4; }
        .heatmap-cell.h3 { background: #38b878; }
        .heatmap-cell.h4 { background: var(--color-success); }
        .heatmap-cell.h5 { background: var(--color-warning); }
        .heatmap-cell.weekend { opacity: 0.55; }

        .byproject-section {
            margin-bottom: var(--space-5);
            padding: var(--space-4);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        .byproject-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: var(--space-3);
        }
        .byproject-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-subtle);
            font-weight: 600;
        }
        .byproject-count {
            font-size: 0.78rem;
            color: var(--color-text-muted);
        }
        .byproject-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .byproject-row {
            display: grid;
            grid-template-columns: minmax(120px, 1.3fr) 2fr auto auto;
            gap: var(--space-3);
            align-items: center;
            font-size: 0.85rem;
        }
        .byproject-name {
            color: var(--color-text);
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .byproject-bar {
            height: 6px;
            background: var(--color-bg-subtle);
            border-radius: var(--radius-pill);
            overflow: hidden;
        }
        .byproject-fill {
            height: 100%;
            background: var(--color-primary);
            border-radius: var(--radius-pill);
            transition: width var(--t-default);
        }
        .byproject-hours {
            font-feature-settings: "tnum";
            color: var(--color-text);
            font-weight: 600;
            text-align: right;
            min-width: 50px;
        }
        .byproject-pct {
            font-size: 0.78rem;
            color: var(--color-text-subtle);
            font-feature-settings: "tnum";
            min-width: 38px;
            text-align: right;
        }

        .amort-section {
            margin-bottom: var(--space-5);
            padding: var(--space-4);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        .amort-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: var(--space-3);
        }
        .amort-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-subtle);
            font-weight: 600;
        }
        .amort-total { font-size: 0.78rem; color: var(--color-text-muted); font-feature-settings: "tnum"; }
        .amort-bar {
            display: flex;
            height: 28px;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--color-bg-subtle);
            margin-bottom: var(--space-3);
        }
        .amort-segment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 600;
            overflow: hidden;
            white-space: nowrap;
            transition: all var(--t-default);
            font-feature-settings: "tnum";
        }
        .amort-amortizable { background: var(--color-success); }
        .amort-non { background: var(--color-text-muted); }
        .amort-legend {
            display: flex;
            gap: var(--space-5);
            font-size: 0.8rem;
            color: var(--color-text-muted);
            flex-wrap: wrap;
        }
        .amort-legend-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        .amort-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .amort-legend strong { color: var(--color-text); font-feature-settings: "tnum"; }
        .amort-initiatives {
            margin-top: var(--space-3);
            padding-top: var(--space-3);
            border-top: 1px solid var(--color-border);
            font-size: 0.8rem;
            display: flex;
            flex-direction: column;
            gap: var(--space-1);
        }
        .amort-init-row {
            display: flex;
            justify-content: space-between;
            color: var(--color-text-muted);
            padding: 4px 0;
            border-radius: var(--radius-sm);
        }
        .amort-init-row a { color: var(--color-primary); text-decoration: none; font-weight: 500; font-feature-settings: "tnum"; }
        .amort-init-row a:hover { text-decoration: underline; }
        .amort-init-row .mono { color: var(--color-text); font-weight: 600; }

        .progress-track {
            width: 100%;
            height: 4px;
            background: var(--color-border);
            border-radius: var(--radius-pill);
            margin-top: var(--space-2);
            overflow: hidden;
        }

        .progress-track-fill {
            height: 100%;
            border-radius: var(--radius-pill);
            background: var(--color-primary);
            transition: width var(--t-default);
        }

        .day {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-2);
            overflow: hidden;
            transition: box-shadow var(--t-fast), border-color var(--t-fast);
        }

        .day:hover { box-shadow: var(--shadow-sm); border-color: var(--color-border-strong); }

        .day-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-3) var(--space-4);
            cursor: pointer;
            user-select: none;
            transition: background var(--t-fast);
        }

        .day-head:hover { background: var(--color-bg-alt); }

        .day-label {
            font-weight: 500;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .day-chevron {
            color: var(--color-text-subtle);
            transition: transform var(--t-default);
            flex-shrink: 0;
        }
        .day.collapsed .day-chevron { transform: rotate(-90deg); }
        .day.collapsed .day-content { display: none; }

        .tag {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .tag-ok { background: var(--color-success-bg); color: var(--color-success); }
        .tag-partial { background: var(--color-warning-bg); color: var(--color-warning); }
        .tag-empty { background: var(--color-danger-bg); color: var(--color-danger); }
        .tag-overtime { background: var(--color-warning-bg); color: var(--color-warning); }
        .tag-weekend { background: var(--color-bg-subtle); color: var(--color-text-subtle); }

        .day-hrs {
            font-size: 0.85rem;
            color: var(--color-text-subtle);
            font-feature-settings: "tnum";
        }
        .day-hrs strong { color: var(--color-text); font-weight: 600; }

        .day-content {
            padding: 0 var(--space-4) var(--space-3);
            border-top: 1px solid var(--color-border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        th {
            text-align: left;
            padding: var(--space-2) var(--space-2);
            color: var(--color-text-subtle);
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--color-border);
            background: transparent;
        }

        td {
            padding: var(--space-2);
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background var(--t-fast); }
        tbody tr:hover { background: var(--color-bg-alt); }

        .key-link {
            color: var(--color-primary);
            font-weight: 600;
            text-decoration: none;
            font-feature-settings: "tnum";
        }
        .key-link:hover { text-decoration: underline; }

        .status-tag {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            background: var(--color-primary-bg);
            color: var(--color-primary);
            font-weight: 500;
            white-space: nowrap;
            display: inline-block;
        }
        .status-tag.s-done    { background: var(--color-success-bg); color: var(--color-success); }
        .status-tag.s-progress{ background: var(--color-primary-bg); color: var(--color-primary); }
        .status-tag.s-todo    { background: var(--color-bg-subtle); color: var(--color-text-muted); }
        .status-tag.s-blocked { background: var(--color-danger-bg); color: var(--color-danger); }

        .mono {
            font-family: 'SF Mono', Monaco, Consolas, 'Courier New', monospace;
            font-feature-settings: "tnum";
        }

        .no-data {
            text-align: center;
            padding: var(--space-5);
            color: var(--color-text-subtle);
            font-size: 0.85rem;
        }

        .error-msg {
            background: var(--color-danger-bg);
            border: 1px solid #ffbdad;
            border-left: 3px solid var(--color-danger);
            border-radius: var(--radius-md);
            padding: var(--space-3) var(--space-4);
            color: #bf2600;
            margin-bottom: var(--space-4);
            font-size: 0.85rem;
        }

        .error-msg strong { display: block; margin-bottom: 2px; color: var(--color-danger); }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(9, 30, 66, 0.45);
            backdrop-filter: blur(2px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: var(--space-4);
            animation: fadeIn 150ms ease-out;
        }
        .overlay.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(8px) } to { opacity: 1; transform: translateY(0) } }

        .dialog {
            background: var(--color-bg);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            animation: slideUp 200ms cubic-bezier(0.2, 0, 0, 1);
        }

        .dialog h2 {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: var(--space-4);
            letter-spacing: -0.01em;
        }

        .field { margin-bottom: var(--space-3); }

        .field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--color-text-muted);
            margin-bottom: 4px;
        }

        .field input {
            width: 100%;
            padding: 8px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            font-size: 0.88rem;
            color: var(--color-text);
            background: var(--color-bg);
            font-family: inherit;
            transition: all var(--t-fast);
        }

        .field input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-bg);
        }

        .field .note {
            font-size: 0.72rem;
            color: var(--color-text-subtle);
            margin-top: 4px;
        }

        .field .note a { color: var(--color-primary); }

        .dialog-footer {
            display: flex;
            gap: var(--space-2);
            justify-content: flex-end;
            align-items: center;
            margin-top: var(--space-4);
            padding-top: var(--space-3);
            border-top: 1px solid var(--color-border);
        }

        .btn-sm {
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            cursor: pointer;
            border: 1px solid var(--color-border);
            background: var(--color-bg);
            color: var(--color-text);
            font-family: inherit;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            transition: all var(--t-fast);
        }

        .btn-sm:hover {
            background: var(--color-bg-subtle);
            border-color: var(--color-border-strong);
        }

        .btn-sm.primary {
            background: var(--color-primary);
            color: #fff;
            border-color: var(--color-primary);
        }
        .btn-sm.primary:hover {
            background: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
        }

        .btn-sm.danger {
            color: var(--color-danger);
            border-color: transparent;
            background: transparent;
            font-size: 0.78rem;
        }
        .btn-sm.danger:hover { background: var(--color-danger-bg); }
        .btn-sm:disabled { opacity: 0.6; cursor: not-allowed; }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: inline-block;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg) } }

        .dialog-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--color-text-subtle);
            cursor: pointer;
            line-height: 1;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: all var(--t-fast);
        }
        .dialog-close:hover { background: var(--color-bg-subtle); color: var(--color-text); }

        .form-error {
            margin-top: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: var(--color-danger-bg);
            color: #bf2600;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            border-left: 3px solid var(--color-danger);
        }
        .form-error[hidden] { display: none; }

        .field-hint {
            margin-top: 4px;
            padding: 6px 10px;
            background: var(--color-primary-bg);
            color: var(--color-primary);
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
        }
        .field-hint[hidden] { display: none; }

        .duration-chips {
            display: flex;
            gap: 6px;
            margin-top: var(--space-2);
            flex-wrap: wrap;
        }

        .chip {
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--color-border);
            background: var(--color-bg);
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--color-text-muted);
            cursor: pointer;
            font-family: inherit;
            transition: all var(--t-fast);
        }
        .chip:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
            background: var(--color-primary-bg);
        }
        .chip.active {
            border-color: var(--color-primary);
            background: var(--color-primary);
            color: #fff;
        }
        .chip.chip-rest { font-style: italic; }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            max-height: 280px;
            overflow-y: auto;
            z-index: 10;
        }
        .suggestions[hidden] { display: none; }

        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            border-bottom: 1px solid var(--color-border);
            transition: background var(--t-fast);
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover, .suggestion-item.active {
            background: var(--color-primary-bg);
        }
        .suggestion-key {
            font-weight: 600;
            color: var(--color-primary);
            font-feature-settings: "tnum";
            font-size: 0.82rem;
            white-space: nowrap;
        }
        .suggestion-summary {
            color: var(--color-text);
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }
        .suggestion-project {
            font-size: 0.72rem;
            color: var(--color-text-subtle);
            white-space: nowrap;
        }
        .suggestion-empty {
            padding: 12px;
            text-align: center;
            color: var(--color-text-subtle);
            font-size: 0.82rem;
        }

        .kbd-hint {
            font-size: 0.72rem;
            color: var(--color-text-subtle);
            font-family: 'SF Mono', Monaco, Consolas, monospace;
        }

        .theme-toggle {
            display: flex;
            gap: 4px;
            background: var(--color-bg-subtle);
            padding: 4px;
            border-radius: var(--radius-md);
        }
        .theme-btn {
            flex: 1;
            padding: 6px 10px;
            border: none;
            background: transparent;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-family: inherit;
            color: var(--color-text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all var(--t-fast);
        }
        .theme-btn:hover { color: var(--color-text); }
        .theme-btn.active {
            background: var(--color-bg);
            color: var(--color-primary);
            box-shadow: var(--shadow-sm);
            font-weight: 600;
        }

        .mt-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 50vh;
            overflow-y: auto;
        }
        .mt-row {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            transition: all var(--t-fast);
        }
        .mt-row:hover { border-color: var(--color-border-strong); box-shadow: var(--shadow-sm); }
        .mt-row[hidden] { display: none; }
        .mt-row-key {
            font-weight: 600;
            color: var(--color-primary);
            font-feature-settings: "tnum";
            white-space: nowrap;
            text-decoration: none;
        }
        .mt-row-key:hover { text-decoration: underline; }
        .mt-row-body {
            flex: 1;
            min-width: 0;
        }
        .mt-row-summary {
            color: var(--color-text);
            font-weight: 500;
            font-size: 0.88rem;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mt-row-meta {
            display: flex;
            gap: var(--space-2);
            font-size: 0.75rem;
            color: var(--color-text-muted);
            align-items: center;
        }
        .mt-row-type {
            font-weight: 500;
            padding: 1px 6px;
            border-radius: var(--radius-sm);
            background: var(--color-bg-subtle);
        }
        .mt-row-type.bug { background: var(--color-danger-bg); color: var(--color-danger); }
        .mt-row-type.story { background: var(--color-success-bg); color: var(--color-success); }
        .mt-row-type.task { background: var(--color-primary-bg); color: var(--color-primary); }
        .mt-row .btn-sm { flex-shrink: 0; }

        .bug-toggle {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.78rem;
            color: var(--color-text-muted);
            cursor: pointer;
            user-select: none;
        }
        .bug-toggle input { margin-right: 4px; }

        .parent-card {
            padding: var(--space-3);
            background: var(--color-bg-subtle);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
        }
        .parent-card .key {
            font-weight: 600;
            color: var(--color-primary);
        }

        kbd {
            display: inline-block;
            padding: 1px 6px;
            font-family: 'SF Mono', Monaco, Consolas, monospace;
            font-size: 0.72rem;
            color: var(--color-text);
            background: var(--color-bg-subtle);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            box-shadow: 0 1px 0 var(--color-border-strong);
            line-height: 1.4;
        }

        .help-shortcuts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            font-size: 0.85rem;
        }
        .help-shortcuts dt {
            color: var(--color-text-muted);
        }
        .help-shortcuts dd {
            text-align: right;
        }

        .setup-msg {
            text-align: center;
            max-width: 560px;
            margin: var(--space-6) auto;
            padding: var(--space-6) var(--space-5);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            color: var(--color-text-muted);
        }

        .setup-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--color-primary-bg);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
        }

        .setup-msg.auth-error .setup-icon-wrap {
            background: var(--color-warning-bg);
            color: var(--color-warning);
        }

        .setup-msg h2 {
            font-size: 1.25rem;
            color: var(--color-text);
            margin-bottom: var(--space-2);
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        .setup-msg p {
            font-size: 0.9rem;
            margin-bottom: var(--space-4);
            line-height: 1.55;
        }

        .setup-actions {
            display: flex;
            gap: var(--space-2);
            justify-content: center;
            flex-wrap: wrap;
        }

        .setup-details {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--color-border);
            text-align: left;
        }

        .setup-details summary {
            cursor: pointer;
            font-size: 0.82rem;
            color: var(--color-primary);
            font-weight: 500;
            text-align: center;
            list-style: none;
            user-select: none;
        }

        .setup-details summary::-webkit-details-marker { display: none; }

        .setup-details summary::after {
            content: ' ▾';
            transition: transform var(--t-fast);
            display: inline-block;
        }

        .setup-details[open] summary::after {
            transform: rotate(180deg);
        }

        .setup-details summary:hover { text-decoration: underline; }

        .setup-steps {
            margin-top: var(--space-3);
            font-size: 0.85rem;
            color: var(--color-text);
            line-height: 1.6;
        }

        .setup-steps strong {
            display: block;
            margin: var(--space-3) 0 var(--space-1);
            color: var(--color-text);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--color-text-subtle);
            font-weight: 600;
        }

        .setup-steps strong:first-child { margin-top: 0; }

        .setup-steps ol {
            padding-left: var(--space-5);
            color: var(--color-text-muted);
        }

        .setup-steps li { margin-bottom: 4px; }

        .setup-steps em {
            color: var(--color-text);
            font-style: normal;
            font-weight: 600;
        }

        .setup-steps a {
            color: var(--color-primary);
            text-decoration: none;
        }
        .setup-steps a:hover { text-decoration: underline; }

        .hier-init {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-2);
            overflow: hidden;
        }

        .hier-init-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-3) var(--space-4);
            background: var(--color-bg-alt);
            cursor: pointer;
            user-select: none;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--color-text);
            transition: background var(--t-fast);
        }

        .hier-init-head:hover { background: var(--color-bg-subtle); }

        .hier-epic {
            border-top: 1px solid var(--color-border);
        }

        .hier-epic-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-2) var(--space-4) var(--space-2) var(--space-6);
            background: var(--color-bg);
            cursor: pointer;
            user-select: none;
            font-size: 0.83rem;
            color: var(--color-text-muted);
            transition: background var(--t-fast);
        }

        .hier-epic-head:hover { background: var(--color-bg-alt); }

        .hier-epic-body table { margin: 0; }

        .hier-epic-body td, .hier-epic-body th {
            padding-left: 2.75rem;
        }

        .hier-epic-body th:first-child,
        .hier-epic-body td:first-child { padding-left: 2.75rem; }

        .hier-hrs {
            font-feature-settings: "tnum";
            font-size: 0.8rem;
            color: var(--color-text-subtle);
            white-space: nowrap;
        }

        .hier-hrs strong { color: var(--color-text); font-weight: 600; }

        .btn-add-worklog {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            width: 24px;
            height: 24px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            flex-shrink: 0;
            transition: all var(--t-fast);
        }

        .btn-add-worklog:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
            background: var(--color-primary-bg);
        }

        .btn-edit-wl, .btn-del-wl {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 6px;
            font-size: 0.85rem;
            line-height: 1;
            border-radius: var(--radius-sm);
            transition: all var(--t-fast);
        }

        .btn-edit-wl { color: var(--color-text-subtle); }
        .btn-edit-wl:hover { color: var(--color-primary); background: var(--color-primary-bg); }

        .btn-del-wl { color: var(--color-text-subtle); }
        .btn-del-wl:hover { color: var(--color-danger); background: var(--color-danger-bg); }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: var(--space-5);
            gap: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: sticky;
            top: 0;
            background: var(--color-bg-alt);
            z-index: 50;
            padding-top: var(--space-2);
            margin-top: calc(-1 * var(--space-2));
        }

        .tab-btn {
            padding: 10px 18px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--color-text-muted);
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all var(--t-fast);
            white-space: nowrap;
            font-family: inherit;
            font-weight: 500;
        }

        .tab-btn:hover { color: var(--color-text); }
        .tab-btn.active { color: var(--color-primary); font-weight: 600; border-bottom-color: var(--color-primary); }

        .matrix-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 70vh;
            margin-bottom: var(--space-5);
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .matrix-table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8rem;
            white-space: nowrap;
            width: 100%;
        }

        .matrix-table th, .matrix-table td {
            padding: 6px 10px;
            border-bottom: 1px solid var(--color-border);
            border-right: 1px solid var(--color-border);
            text-align: center;
            background: var(--color-bg);
        }

        .matrix-table th:last-child, .matrix-table td:last-child { border-right: none; }
        .matrix-table tr:last-child td { border-bottom: none; }

        .matrix-table .col-issue {
            text-align: left;
            width: 220px;
            max-width: 220px;
            white-space: normal;
            word-break: break-word;
            position: sticky;
            left: 0;
            background: var(--color-bg);
            z-index: 2;
            border-right: 2px solid var(--color-border-strong);
        }

        .matrix-table thead th {
            background: var(--color-bg-alt);
            font-weight: 600;
            color: var(--color-text-muted);
            font-size: 0.7rem;
            line-height: 1.3;
            position: sticky;
            top: 0;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .matrix-table thead .col-issue { background: var(--color-bg-alt); z-index: 3; }

        .matrix-table .col-weekend { background: var(--color-bg-subtle); color: var(--color-text-subtle); }
        .matrix-table thead .col-weekend { color: var(--color-text-subtle); }

        .matrix-table tbody tr:hover td { background: var(--color-bg-alt); }
        .matrix-table tbody tr:hover td.col-issue { background: var(--color-bg-alt); }

        .matrix-table .cell-val {
            color: var(--color-primary);
            font-weight: 600;
            font-feature-settings: "tnum";
        }

        .matrix-table .col-total {
            background: var(--color-bg-alt);
            font-weight: 600;
            font-feature-settings: "tnum";
            border-left: 2px solid var(--color-border-strong);
        }

        .matrix-table tfoot td {
            background: var(--color-bg-subtle);
            font-weight: 700;
            font-feature-settings: "tnum";
            color: var(--color-text);
            border-top: 2px solid var(--color-border-strong);
            position: sticky;
            bottom: 0;
        }

        .matrix-table tfoot .col-issue {
            background: var(--color-bg-subtle);
            color: var(--color-text-muted);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Toasts */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 2000;
            pointer-events: none;
        }
        .toast {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-left: 3px solid var(--color-success);
            box-shadow: var(--shadow-lg);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 0.85rem;
            color: var(--color-text);
            min-width: 240px;
            max-width: 360px;
            pointer-events: auto;
            animation: toastIn 200ms ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toast.error { border-left-color: var(--color-danger); }
        .toast.warning { border-left-color: var(--color-warning); }
        @keyframes toastIn { from { opacity: 0; transform: translateX(20px) } to { opacity: 1; transform: translateX(0) } }
        @keyframes toastOut { to { opacity: 0; transform: translateX(20px) } }
        .toast.removing { animation: toastOut 200ms ease-out forwards; }
        .toast-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--color-success-bg);
            color: var(--color-success);
            font-size: 0.75rem;
            font-weight: 700;
        }
        .toast.error .toast-icon { background: var(--color-danger-bg); color: var(--color-danger); }
        .toast.warning .toast-icon { background: var(--color-warning-bg); color: var(--color-warning); }

        @media (max-width: 760px) {
            .container { padding: var(--space-4) var(--space-3); }
            header {
                flex-direction: column;
                align-items: stretch;
                gap: var(--space-3);
            }
            .header-right { justify-content: space-between; }
            .summary { grid-template-columns: repeat(2, 1fr); }
            .summary-item:nth-child(2) { border-right: none; }
            .summary-item:nth-child(3), .summary-item:nth-child(4) {
                border-top: 1px solid var(--color-border);
            }
            .summary-value { font-size: 1.25rem; }
            .day-head { padding: var(--space-3); }
            .day-content { padding: 0 var(--space-3) var(--space-3); }
            .toast-container { bottom: 12px; right: 12px; left: 12px; }
            .toast { min-width: 0; max-width: none; }
            .amort-legend { gap: var(--space-3); }
        }
    </style>
</head>
<body>
<?php
function jiraStatusClass(string $status): string {
    $s = mb_strtolower($status);
    if (preg_match('/done|cerrad|resuelto|resolved|closed|complet|terminad|finaliz/u', $s)) return 's-done';
    if (preg_match('/progres|curso|doing|trabajan|en\s*revisi/u', $s)) return 's-progress';
    if (preg_match('/block|bloque|impedi/u', $s)) return 's-blocked';
    if (preg_match('/todo|por\s*hacer|backlog|abiert|open|nuev/u', $s)) return 's-todo';
    return '';
}
?>
<div class="container"
     data-cfg-email="<?= htmlspecialchars($jiraEmail) ?>"
     data-has-token="<?= $hasToken ? '1' : '0' ?>">
    <header>
        <div class="logo-group">
            <svg viewBox="0 0 256 96" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-label="Batman">
                <path d="M128 4c-3 9-7 17-12 24-5-3-11-3-16-1-3-7-7-13-12-19-3 7-6 14-9 21-12-6-26-7-39-2-12 4-22 12-31 21 13-2 26-2 38 3 12 5 22 14 28 26 7-9 18-13 30-12 6 9 12 18 18 28 1 1 3 1 4 0 6-10 12-19 18-28 12-1 23 3 30 12 6-12 16-21 28-26 12-5 25-5 38-3-9-9-19-17-31-21-13-5-27-4-39 2-3-7-6-14-9-21-5 6-9 12-12 19-5-2-11-2-16 1-5-7-9-15-12-24z"/>
            </svg>
            <h1>Jira Batman</h1>
        </div>
        <div class="header-right">
            <?php if ($displayName): ?>
                <span class="user-info">
                    <span><?= htmlspecialchars($displayName) ?></span>
                    <?php if ($authMethod === 'oauth'): ?>
                        <span class="badge" title="Sesi&oacute;n OAuth activa">OAuth</span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <?php if ($authMethod === 'oauth'): ?>
                <a class="btn-settings" href="oauth/logout.php" title="Cerrar sesi&oacute;n" style="text-decoration:none;">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M6 12.5a.5.5 0 01.5.5v.5a.5.5 0 00.5.5h6a.5.5 0 00.5-.5v-11a.5.5 0 00-.5-.5h-6a.5.5 0 00-.5.5V3a.5.5 0 01-1 0v-.5A1.5 1.5 0 016.5.5h6A1.5 1.5 0 0114 2v11.5a1.5 1.5 0 01-1.5 1.5h-6A1.5 1.5 0 015 13.5V13a.5.5 0 01.5-.5z"/><path d="M.146 8.354a.5.5 0 010-.708l3-3a.5.5 0 11.708.708L1.707 7.5H10.5a.5.5 0 010 1H1.707l2.147 2.146a.5.5 0 01-.708.708l-3-3z"/></svg>
                </a>
            <?php endif; ?>
            <button class="btn-settings" onclick="openMyTasks()" title="Mis tareas asignadas">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </button>
            <button class="btn-settings" onclick="openHelp()" title="Atajos de teclado (?)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </button>
            <button class="btn-settings" onclick="openSettings()" title="Configuraci&oacute;n">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 4.754a3.246 3.246 0 100 6.492 3.246 3.246 0 000-6.492zM5.754 8a2.246 2.246 0 114.492 0 2.246 2.246 0 01-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 01-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 01-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 01.52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 011.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 011.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 01.52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 01-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 01-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 002.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 001.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 00-1.115 2.693l.16.291c.415.764-.421 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 00-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 00-2.692-1.115l-.292.16c-.764.415-1.6-.421-1.184-1.185l.159-.291A1.873 1.873 0 001.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 003.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 002.692-1.115l.094-.319z"/></svg>
            </button>
        </div>
    </header>

    <?php if ($authError): ?>
        <div class="setup-msg auth-error">
            <div class="setup-icon-wrap">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </div>
            <h2>Tu sesi&oacute;n expir&oacute;</h2>
            <p>
                <?php if ($authErrorMethod === 'oauth'): ?>
                    Atlassian rechaz&oacute; tu sesi&oacute;n OAuth. Esto pasa si revocaste el acceso o el token caduc&oacute;.
                <?php elseif ($authErrorMethod === 'basic'): ?>
                    Tu API token actual fue rechazado por Jira (probablemente expir&oacute; o fue revocado).
                <?php else: ?>
                    Las credenciales guardadas ya no funcionan.
                <?php endif; ?>
                Vuelve a iniciar sesi&oacute;n para continuar.
            </p>
            <div class="setup-actions">
                <?php if ($oauthAvailable): ?>
                    <a class="btn-sm primary" href="oauth/login.php">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.571 11.513H0a5.218 5.218 0 005.215 5.215h2.129v2.057A5.215 5.215 0 0012.56 24V12.5a.987.987 0 00-.989-.987zm5.715-5.785H5.715A5.215 5.215 0 0010.93 10.95h2.131v2.058a5.218 5.218 0 005.215 5.215V6.715a.987.987 0 00-.989-.987zM23 0H11.43a5.215 5.215 0 005.215 5.215h2.131v2.058A5.215 5.215 0 0024 12.5V.987A.987.987 0 0023 0z"/></svg>
                        Iniciar sesi&oacute;n con Atlassian
                    </a>
                    <button class="btn-sm" onclick="openSettings()">Usar API token</button>
                <?php else: ?>
                    <button class="btn-sm primary" onclick="openSettings()">Cambiar API token</button>
                <?php endif; ?>
            </div>

            <details class="setup-details">
                <summary>&iquest;C&oacute;mo arreglarlo?</summary>
                <?php if ($oauthAvailable): ?>
                <div class="setup-steps">
                    <strong>Opci&oacute;n recomendada — OAuth:</strong>
                    <ol>
                        <li>Pulsa <em>Iniciar sesi&oacute;n con Atlassian</em>.</li>
                        <li>Autoriza el acceso desde tu cuenta.</li>
                        <li>Volver&aacute;s autom&aacute;ticamente al reporte.</li>
                    </ol>
                    <strong>Alternativa — API token:</strong>
                    <ol>
                        <li>Ve a <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" rel="noopener">id.atlassian.com</a> y crea un nuevo token.</li>
                        <li>Pulsa <em>Usar API token</em> y pega el nuevo token.</li>
                    </ol>
                </div>
                <?php else: ?>
                <div class="setup-steps">
                    <ol>
                        <li>Ve a <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" rel="noopener">id.atlassian.com/manage-profile/security/api-tokens</a>.</li>
                        <li>Pulsa <em>Create API token</em>, ponle un nombre (ej. "Batman Worklog").</li>
                        <li>Copia el token y pulsa <em>Cambiar API token</em> arriba.</li>
                        <li>Pega el token y guarda.</li>
                    </ol>
                </div>
                <?php endif; ?>
            </details>
        </div>
    <?php elseif ($needsSetup): ?>
        <div class="setup-msg">
            <div class="setup-icon-wrap">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
            </div>
            <h2>Sin conexi&oacute;n configurada</h2>
            <p>
                <?php if ($oauthAvailable): ?>
                    Inicia sesi&oacute;n con tu cuenta de Atlassian o usa un API token.
                <?php else: ?>
                    Configura tu email y API token de Jira para consultar tus horas.
                <?php endif; ?>
            </p>
            <div class="setup-actions">
                <?php if ($oauthAvailable): ?>
                    <a class="btn-sm primary" href="oauth/login.php">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.571 11.513H0a5.218 5.218 0 005.215 5.215h2.129v2.057A5.215 5.215 0 0012.56 24V12.5a.987.987 0 00-.989-.987zm5.715-5.785H5.715A5.215 5.215 0 0010.93 10.95h2.131v2.058a5.218 5.218 0 005.215 5.215V6.715a.987.987 0 00-.989-.987zM23 0H11.43a5.215 5.215 0 005.215 5.215h2.131v2.058A5.215 5.215 0 0024 12.5V.987A.987.987 0 0023 0z"/></svg>
                        Iniciar sesi&oacute;n con Atlassian
                    </a>
                    <button class="btn-sm" onclick="openSettings()">Usar API token</button>
                <?php else: ?>
                    <button class="btn-sm primary" onclick="openSettings()">Configurar</button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="filters">
            <a href="?range=today" class="<?= $rangeType === 'today' ? 'active' : '' ?>">Hoy</a>
            <a href="?range=week" class="<?= $rangeType === 'week' ? 'active' : '' ?>">Semana</a>
            <a href="?range=month" class="<?= $rangeType === 'month' ? 'active' : '' ?>">Mes</a>
            <span class="divider"></span>
            <form method="get" style="display: flex; gap: 0.35rem; align-items: center;">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>">
                <span style="color: var(--color-text-subtle); font-size: 0.8rem;">&ndash;</span>
                <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>">
                <button type="submit">Aplicar</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <strong>Error</strong>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($report): ?>
            <?php
            $s = $report['summary'];
            // Recolectar opciones de filtros
            $_filterProjects = [];
            $_filterStatuses = [];
            $_issueInitiative = [];
            foreach ($report['days'] ?? [] as $_d) {
                foreach ($_d['worklogs'] ?? [] as $_wl) {
                    if ($_wl['project']) $_filterProjects[$_wl['project']] = true;
                    if ($_wl['status'])  $_filterStatuses[$_wl['status']]   = true;
                }
            }
            $_filterInitiatives = [];
            foreach ($report['byHierarchy'] ?? [] as $_init) {
                if (!empty($_init['key'])) {
                    $_filterInitiatives[$_init['key']] = $_init['summary'];
                }
                foreach ($_init['epics'] ?? [] as $_ep) {
                    foreach ($_ep['issues'] ?? [] as $_iss) {
                        $_issueInitiative[$_iss['issueKey']] = $_init['key'] ?? '';
                    }
                }
            }
            ksort($_filterProjects);
            ksort($_filterStatuses);
            asort($_filterInitiatives);
            ?>
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Registradas</div>
                    <div class="summary-value" data-hours="<?= $s['totalLogged'] ?>"><?= $s['totalLogged'] ?>h</div>
                    <div class="summary-delta loading" id="delta-placeholder" data-current="<?= $s['totalLogged'] ?>" data-start="<?= htmlspecialchars($startDate) ?>" data-end="<?= htmlspecialchars($endDate) ?>">
                        <span class="spinner" style="width:8px;height:8px;border-width:1px;"></span>
                        Calculando vs. anterior…
                    </div>
                    <div class="progress-track">
                        <div class="progress-track-fill" style="width: <?= min(100, $s['completionPercent']) ?>%;"></div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Esperadas</div>
                    <div class="summary-value" data-hours="<?= $s['totalExpected'] ?>"><?= $s['totalExpected'] ?>h</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Faltantes</div>
                    <div class="summary-value <?= $s['totalRemaining'] > 0 ? 'missing' : 'ok' ?>" data-hours="<?= $s['totalRemaining'] ?>">
                        <?= $s['totalRemaining'] > 0 ? $s['totalRemaining'] . 'h' : '0h' ?>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Avance</div>
                    <div class="summary-value <?= $s['completionPercent'] >= 100 ? 'ok' : ($s['completionPercent'] >= 50 ? 'pending' : 'missing') ?>">
                        <?= $s['completionPercent'] ?>%
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Promedio diario</div>
                    <div class="summary-value" data-hours="<?= $s['avgDailyHours'] ?? 0 ?>"><?= $s['avgDailyHours'] ?? 0 ?>h</div>
                    <div class="summary-delta"><?= $s['workDays'] ?> días hábiles</div>
                </div>
            </div>

            <?php
            // Heatmap (solo si el rango cubre >=7 días)
            $_heatmapDays = $report['days'] ?? [];
            $_showHeatmap = count($_heatmapDays) >= 7;
            ?>
            <?php if ($_showHeatmap): ?>
            <?php
                // Agrupar días en columnas (semanas) tipo GitHub: L,M,M,J,V,S,D verticalmente
                $_weeks = [];
                $_curWeek = array_fill(0, 7, null);
                $_first = true;
                foreach ($_heatmapDays as $_hd) {
                    $_dow = (int) date('N', strtotime($_hd['date'])); // 1=Mon..7=Sun
                    if ($_first) {
                        // sembrar la primera semana respetando el día inicial
                        $_curWeek = array_fill(0, 7, null);
                        $_first = false;
                    }
                    $_curWeek[$_dow - 1] = $_hd;
                    if ($_dow === 7) {
                        $_weeks[] = $_curWeek;
                        $_curWeek = array_fill(0, 7, null);
                    }
                }
                $_hasIncompleteWeek = false;
                foreach ($_curWeek as $_c) { if ($_c !== null) { $_hasIncompleteWeek = true; break; } }
                if ($_hasIncompleteWeek) $_weeks[] = $_curWeek;
            ?>
            <div class="heatmap-section">
                <div class="heatmap-header">
                    <span class="heatmap-title">Calendario</span>
                    <span class="heatmap-legend">
                        <span style="color: var(--color-text-subtle);">Menos</span>
                        <span class="heatmap-cell h0"></span>
                        <span class="heatmap-cell h1"></span>
                        <span class="heatmap-cell h2"></span>
                        <span class="heatmap-cell h3"></span>
                        <span class="heatmap-cell h4"></span>
                        <span style="color: var(--color-text-subtle);">Más</span>
                    </span>
                </div>
                <div class="heatmap-wrap">
                    <div class="heatmap-rows-labels">
                        <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                    </div>
                    <div class="heatmap-grid">
                    <?php foreach ($_weeks as $_week): ?>
                        <div class="heatmap-col">
                            <?php foreach ($_week as $_dayCell): ?>
                                <?php if ($_dayCell === null): ?>
                                    <span class="heatmap-cell empty"></span>
                                <?php else:
                                    $_h = $_dayCell['totalHours'];
                                    $_isWk = $_dayCell['isWeekend'];
                                    $_lvl = 0;
                                    if ($_h > $hoursPerDay)         $_lvl = 5;
                                    elseif ($_h >= $hoursPerDay - 0.01) $_lvl = 4;
                                    elseif ($_h >= $hoursPerDay * 0.6)  $_lvl = 3;
                                    elseif ($_h >= $hoursPerDay * 0.3)  $_lvl = 2;
                                    elseif ($_h > 0)                    $_lvl = 1;
                                    $_title = $_dayCell['dayName'] . ' ' . date('d/m', strtotime($_dayCell['date'])) . ' — ' . $_h . 'h';
                                ?>
                                    <span class="heatmap-cell h<?= $_lvl ?> <?= $_isWk ? 'weekend' : '' ?>" title="<?= htmlspecialchars($_title) ?>"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php $a = $report['amortization']; ?>
            <?php if ($a['totalHours'] > 0): ?>
            <div class="amort-section">
                <div class="amort-header">
                    <span class="amort-title">Amortizable vs no amortizable</span>
                    <span class="amort-total"><span data-hours="<?= $a['totalHours'] ?>"><?= $a['totalHours'] ?>h</span> totales</span>
                </div>
                <div class="amort-bar">
                    <?php if ($a['amortizablePercent'] > 0): ?>
                        <div class="amort-segment amort-amortizable" style="width: <?= $a['amortizablePercent'] ?>%">
                            <?= $a['amortizablePercent'] >= 8 ? $a['amortizableHours'] . 'h' : '' ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($a['nonAmortizablePercent'] > 0): ?>
                        <div class="amort-segment amort-non" style="width: <?= $a['nonAmortizablePercent'] ?>%">
                            <?= $a['nonAmortizablePercent'] >= 8 ? $a['nonAmortizableHours'] . 'h' : '' ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="amort-legend">
                    <div class="amort-legend-item">
                        <span class="amort-legend-dot" style="background:#16793a;"></span>
                        Amortizable <strong data-hours="<?= $a['amortizableHours'] ?>"><?= $a['amortizableHours'] ?>h</strong>
                        <span style="color:var(--color-text-subtle);">(<?= $a['amortizablePercent'] ?>%)</span>
                    </div>
                    <div class="amort-legend-item">
                        <span class="amort-legend-dot" style="background:#999;"></span>
                        No amortizable <strong data-hours="<?= $a['nonAmortizableHours'] ?>"><?= $a['nonAmortizableHours'] ?>h</strong>
                        <span style="color:var(--color-text-subtle);">(<?= $a['nonAmortizablePercent'] ?>%)</span>
                    </div>
                </div>
                <?php if (!empty($a['initiatives'])): ?>
                <div class="amort-initiatives">
                    <?php foreach ($a['initiatives'] as $init): ?>
                        <div class="amort-init-row">
                            <span>
                                <a href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($init['key']) ?>" target="_blank"><?= htmlspecialchars($init['key']) ?></a>
                                &middot; <?= htmlspecialchars($init['summary']) ?>
                            </span>
                            <span class="mono"><?= $init['totalHours'] ?>h</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['byProject'])): ?>
            <div class="byproject-section">
                <div class="byproject-header">
                    <span class="byproject-title">Por proyecto</span>
                    <span class="byproject-count"><?= count($report['byProject']) ?> proyecto<?= count($report['byProject']) !== 1 ? 's' : '' ?></span>
                </div>
                <div class="byproject-list">
                    <?php foreach ($report['byProject'] as $_p): ?>
                    <div class="byproject-row">
                        <div class="byproject-name"><?= htmlspecialchars($_p['name']) ?></div>
                        <div class="byproject-bar">
                            <div class="byproject-fill" style="width: <?= $_p['percent'] ?>%"></div>
                        </div>
                        <div class="byproject-hours" data-hours="<?= $_p['totalHours'] ?>"><?= $_p['totalHours'] ?>h</div>
                        <div class="byproject-pct"><?= $_p['percent'] ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="filterbar">
                <div class="filterbar-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="search-input" placeholder="Buscar tarea, proyecto, descripción…" autocomplete="off">
                </div>
                <select id="filter-project" class="select-filter">
                    <option value="">Todos los proyectos</option>
                    <?php foreach (array_keys($_filterProjects) as $_p): ?>
                        <option value="<?= htmlspecialchars($_p) ?>"><?= htmlspecialchars($_p) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-initiative" class="select-filter">
                    <option value="">Todas las iniciativas</option>
                    <?php foreach ($_filterInitiatives as $_initKey => $_initSummary): ?>
                        <option value="<?= htmlspecialchars($_initKey) ?>"><?= htmlspecialchars($_initSummary) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-status" class="select-filter">
                    <option value="">Todos los estados</option>
                    <?php foreach (array_keys($_filterStatuses) as $_st): ?>
                        <option value="<?= htmlspecialchars($_st) ?>"><?= htmlspecialchars($_st) ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="filter-clear" class="btn-sm" onclick="clearFilters()" hidden>Limpiar</button>
                <span class="filter-count" id="filter-count"></span>
                <div style="flex:1"></div>
                <a class="btn-sm" href="?range=<?= htmlspecialchars($rangeType) ?>&start=<?= htmlspecialchars($startDate) ?>&end=<?= htmlspecialchars($endDate) ?>&export=csv" title="Descargar reporte como CSV">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    CSV
                </a>
                <label class="hr-day-toggle" title="Cambiar entre horas y días">
                    <span class="hr-day-label active" data-unit="h">h</span>
                    <span class="hr-day-sep">·</span>
                    <span class="hr-day-label" data-unit="d">d</span>
                </label>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="dias"      onclick="switchTab('dias')">Captura de horas</button>
                <button class="tab-btn"         data-tab="jerarquia" onclick="switchTab('jerarquia')">Concentrado</button>
                <button class="tab-btn"         data-tab="matriz"   onclick="switchTab('matriz')">Matriz</button>
            </div>

            <div id="tab-jerarquia" style="display:none;">
            <?php if (!empty($report['byHierarchy'])): ?>
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
                                            <tr data-project="<?= htmlspecialchars($iss['project']) ?>"
                                                data-status="<?= htmlspecialchars($iss['status']) ?>"
                                                data-initiative="<?= htmlspecialchars($init['key'] ?? '') ?>"
                                                data-search="<?= htmlspecialchars(mb_strtolower($iss['issueKey'] . ' ' . $iss['summary'] . ' ' . $iss['project'])) ?>">
                                                <td><a class="key-link" href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($iss['issueKey']) ?>" target="_blank"><?= htmlspecialchars($iss['issueKey']) ?></a></td>
                                                <td><?= htmlspecialchars($iss['project']) ?></td>
                                                <td><?= htmlspecialchars(mb_strimwidth($iss['summary'], 0, 55, '...')) ?></td>
                                                <td><span class="status-tag <?= jiraStatusClass($iss['status']) ?>"><?= htmlspecialchars($iss['status']) ?></span></td>
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
            <?php else: ?>
                <div class="no-data">Sin datos de jerarqu&iacute;a en el periodo</div>
            <?php endif; ?>
            </div><!-- /tab-jerarquia -->

            <div id="tab-dias">
            <?php foreach ($report['days'] as $day): ?>
                <?php if ($day['isWeekend'] && empty($day['worklogs'])) continue; ?>
                <?php
                    if ($day['isWeekend']) {
                        $tagClass = 'tag-weekend';
                        $tagText = 'Descanso';
                    } elseif ($day['totalHours'] > $hoursPerDay) {
                        $tagClass = 'tag-overtime';
                        $tagText = '+' . round($day['totalHours'] - $hoursPerDay, 2) . 'h';
                    } elseif ($day['totalHours'] == $hoursPerDay) {
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
                            <svg class="day-chevron" width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M3 4l3 3 3-3" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span><?= htmlspecialchars($day['dayName']) ?> <?= date('d/m', strtotime($day['date'])) ?></span>
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
                                        <tr data-wl-id="<?= htmlspecialchars($wl['id']) ?>"
                                            data-wl-seconds="<?= (int)$wl['timeSpentSeconds'] ?>"
                                            data-project="<?= htmlspecialchars($wl['project']) ?>"
                                            data-status="<?= htmlspecialchars($wl['status']) ?>"
                                            data-initiative="<?= htmlspecialchars($_issueInitiative[$wl['issueKey']] ?? '') ?>"
                                            data-search="<?= htmlspecialchars(mb_strtolower($wl['issueKey'] . ' ' . $wl['summary'] . ' ' . $wl['project'])) ?>">
                                            <td>
                                                <a class="key-link"
                                                   href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($wl['issueKey']) ?>"
                                                   target="_blank">
                                                    <?= htmlspecialchars($wl['issueKey']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($wl['project']) ?></td>
                                            <td><?= htmlspecialchars(mb_strimwidth($wl['summary'], 0, 55, '...')) ?></td>
                                            <td><span class="status-tag <?= jiraStatusClass($wl['status']) ?>"><?= htmlspecialchars($wl['status']) ?></span></td>
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
                            <div class="no-data">
                                <?= $day['isWeekend'] ? 'Descanso' : 'Sin horas registradas' ?>
                                <?php if (!$day['isWeekend']): ?>
                                    <div style="margin-top: 0.6rem; display: flex; gap: 0.4rem; justify-content: center;">
                                        <button class="btn-sm" onclick="openAddWorklog(event,'<?= $day['date'] ?>')">+ Registrar</button>
                                        <button class="btn-sm" onclick="repeatLastWorklog(event,'<?= $day['date'] ?>')" title="Pre-llena con el último worklog">↻ Repetir último</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div><!-- /tab-dias -->

            <?php $mx = $report['matrix']; ?>
            <div id="tab-matriz" style="display:none;">
                <?php if (!empty($mx['rows'])): ?>
                <div class="matrix-wrap">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th class="col-issue">Tarea</th>
                                <?php foreach ($mx['dates'] as $col): ?>
                                    <?php if ($col['isWeekend']) continue; ?>
                                    <th><?= htmlspecialchars($col['label']) ?></th>
                                <?php endforeach; ?>
                                <th class="col-total">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mx['rows'] as $row): ?>
                            <tr data-project="<?= htmlspecialchars($row['project']) ?>"
                                data-initiative="<?= htmlspecialchars($_issueInitiative[$row['issueKey']] ?? '') ?>"
                                data-search="<?= htmlspecialchars(mb_strtolower($row['issueKey'] . ' ' . $row['summary'] . ' ' . $row['project'])) ?>">
                                <td class="col-issue">
                                    <a class="key-link" href="<?= htmlspecialchars($jiraBaseUrl) ?>/browse/<?= htmlspecialchars($row['issueKey']) ?>" target="_blank"><?= htmlspecialchars($row['issueKey']) ?></a>
                                    <span style="color:#aaa;margin-left:0.3rem;font-size:0.7rem;"><?= htmlspecialchars(mb_strimwidth($row['summary'], 0, 40, '...')) ?></span>
                                </td>
                                <?php foreach ($mx['dates'] as $col): ?>
                                    <?php if ($col['isWeekend']) continue; ?>
                                    <?php $h = $row['cells'][$col['date']] ?? 0; ?>
                                    <td><?php if ($h > 0): ?><span class="cell-val"><?= $h ?>h</span><?php endif; ?></td>
                                <?php endforeach; ?>
                                <td class="col-total"><?= $row['totalHours'] ?>h</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="col-issue">Total</td>
                                <?php foreach ($mx['dates'] as $col): ?>
                                    <?php if ($col['isWeekend']) continue; ?>
                                    <?php $t = $mx['totals'][$col['date']] ?? 0; ?>
                                    <td><?php if ($t > 0): ?><?= $t ?>h<?php endif; ?></td>
                                <?php endforeach; ?>
                                <td class="col-total"><?= $mx['grandTotal'] ?>h</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                    <div class="no-data">Sin datos en el periodo</div>
                <?php endif; ?>
            </div><!-- /tab-matriz -->

        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="overlay" id="addWorklogModal">
    <div class="dialog">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-4);">
            <h2 id="wl-title" style="margin-bottom:0;">Registrar tiempo</h2>
            <button onclick="closeAddWorklog()" class="dialog-close" aria-label="Cerrar">&times;</button>
        </div>
        <input type="hidden" id="wl-mode" value="add">
        <input type="hidden" id="wl-worklog-id" value="">
        <div class="field" style="position:relative;">
            <label for="wl-key">Clave de tarea</label>
            <input type="text" id="wl-key" placeholder="Empieza a escribir o pega la clave (PROJ-123)…" autocomplete="off" style="text-transform:uppercase">
            <div id="wl-suggestions" class="suggestions" hidden></div>
            <div id="wl-key-hint" class="field-hint" hidden></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
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
            <div class="duration-chips">
                <button type="button" class="chip" data-dur="30m">30m</button>
                <button type="button" class="chip" data-dur="1h">1h</button>
                <button type="button" class="chip" data-dur="2h">2h</button>
                <button type="button" class="chip" data-dur="4h">4h</button>
                <button type="button" class="chip chip-rest" id="wl-chip-rest">Resto del día</button>
            </div>
        </div>
        <div id="wl-error" class="form-error" hidden></div>
        <div class="dialog-footer">
            <span class="kbd-hint">⌘ Enter para guardar</span>
            <div style="flex:1"></div>
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

        <?php if ($oauthAvailable): ?>
        <div class="field" style="border:1px solid #e5e5e5; border-radius:4px; padding:0.7rem 0.8rem; background:#fafafa;">
            <label style="margin-bottom:0.3rem;">Sesi&oacute;n con Atlassian</label>
            <?php if ($authMethod === 'oauth' && $oauthSession): ?>
                <div style="font-size:0.78rem; color:#666; margin-bottom:0.5rem;">
                    Activa en <strong><?= htmlspecialchars($oauthSession['cloud_name'] ?: $oauthSession['cloud_url']) ?></strong>
                </div>
                <a class="btn-sm danger" href="oauth/logout.php" style="text-decoration:none;">Cerrar sesi&oacute;n</a>
            <?php else: ?>
                <div style="font-size:0.78rem; color:#888; margin-bottom:0.5rem;">
                    Recomendado. M&aacute;s seguro que usar un API token directamente.
                </div>
                <a class="btn-sm primary" href="oauth/login.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M11.571 11.513H0a5.218 5.218 0 005.215 5.215h2.129v2.057A5.215 5.215 0 0012.56 24V12.5a.987.987 0 00-.989-.987zm5.715-5.785H5.715A5.215 5.215 0 0010.93 10.95h2.131v2.058a5.218 5.218 0 005.215 5.215V6.715a.987.987 0 00-.989-.987zM23 0H11.43a5.215 5.215 0 005.215 5.215h2.131v2.058A5.215 5.215 0 0024 12.5V.987A.987.987 0 0023 0z"/></svg>
                    Iniciar sesi&oacute;n con Atlassian
                </a>
            <?php endif; ?>
        </div>
        <div style="text-align:center; color:#bbb; font-size:0.7rem; margin:0.6rem 0; text-transform:uppercase; letter-spacing:0.05em;">o usa un API token</div>
        <?php endif; ?>

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
        <div class="field" style="border-top:1px solid var(--color-border); padding-top:var(--space-3); margin-top:var(--space-3);">
            <label>Apariencia</label>
            <div class="theme-toggle">
                <button type="button" class="theme-btn" data-theme="light" onclick="setTheme('light')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                    Claro
                </button>
                <button type="button" class="theme-btn" data-theme="dark" onclick="setTheme('dark')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    Oscuro
                </button>
                <button type="button" class="theme-btn" data-theme="auto" onclick="setTheme('auto')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Auto
                </button>
            </div>
        </div>
        <div class="field">
            <label>Auto-actualizar</label>
            <div class="theme-toggle">
                <button type="button" class="theme-btn" data-refresh="0" onclick="setAutoRefresh(0)">Off</button>
                <button type="button" class="theme-btn" data-refresh="60" onclick="setAutoRefresh(60)">1 min</button>
                <button type="button" class="theme-btn" data-refresh="300" onclick="setAutoRefresh(300)">5 min</button>
                <button type="button" class="theme-btn" data-refresh="900" onclick="setAutoRefresh(900)">15 min</button>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn-sm danger" onclick="clearCfg()">Borrar credenciales</button>
            <div style="flex:1"></div>
            <button class="btn-sm" onclick="closeCfg()">Cancelar</button>
            <button class="btn-sm primary" onclick="saveCfg()">Guardar</button>
        </div>
    </div>
</div>

<div class="overlay" id="myTasksModal">
    <div class="dialog" style="max-width: 640px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-4);gap:var(--space-3);">
            <h2 style="margin-bottom:0;">Mis tareas asignadas</h2>
            <div style="display:flex;align-items:center;gap:var(--space-2);">
                <label class="bug-toggle"><input type="checkbox" id="mt-only-bugs"> Solo bugs</label>
                <button onclick="closeMyTasks()" class="dialog-close" aria-label="Cerrar">&times;</button>
            </div>
        </div>
        <div class="filterbar-search" style="margin-bottom: var(--space-3);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" id="mt-search" placeholder="Filtrar por clave o título…" autocomplete="off">
        </div>
        <div id="mt-list" class="mt-list">
            <div class="no-data"><span class="spinner"></span> Cargando…</div>
        </div>
    </div>
</div>

<div class="overlay" id="subtaskModal">
    <div class="dialog">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-4);">
            <h2 style="margin-bottom:0;">Nueva subtarea</h2>
            <button onclick="closeSubtask()" class="dialog-close" aria-label="Cerrar">&times;</button>
        </div>
        <div class="field">
            <label>Tarea padre</label>
            <div id="st-parent" class="parent-card"></div>
        </div>
        <div class="field">
            <label for="st-summary">Título</label>
            <input type="text" id="st-summary" placeholder="Resumen de la subtarea">
        </div>
        <div class="field">
            <label for="st-description">Descripción <span style="color:var(--color-text-subtle); font-weight:normal;">(opcional)</span></label>
            <textarea id="st-description" rows="4" placeholder="Detalles adicionales…"
                      style="width:100%; padding:8px 10px; border-radius:var(--radius-sm); border:1px solid var(--color-border); font-size:0.88rem; font-family:inherit; color:var(--color-text); background:var(--color-bg); resize:vertical;"></textarea>
        </div>
        <div id="st-error" class="form-error" hidden></div>
        <div class="dialog-footer">
            <span class="kbd-hint">⌘ Enter para crear</span>
            <div style="flex:1"></div>
            <button class="btn-sm" onclick="closeSubtask()">Cancelar</button>
            <button class="btn-sm primary" id="st-submit" onclick="submitSubtask()">Crear y poner en uso</button>
        </div>
    </div>
</div>

<div class="overlay" id="helpModal">
    <div class="dialog">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-4);">
            <h2 style="margin-bottom:0;">Atajos de teclado</h2>
            <button onclick="closeHelp()" class="dialog-close" aria-label="Cerrar">&times;</button>
        </div>
        <dl class="help-shortcuts">
            <dt>Nuevo registro hoy</dt><dd><kbd>N</kbd></dd>
            <dt>Editar último</dt><dd><kbd>E</kbd></dd>
            <dt>Buscar</dt><dd><kbd>/</kbd></dd>
            <dt>Mostrar esta ayuda</dt><dd><kbd>?</kbd></dd>
            <dt>Cerrar modal</dt><dd><kbd>Esc</kbd></dd>
            <dt>Guardar (en modal)</dt><dd><kbd>⌘</kbd> <kbd>Enter</kbd></dd>
            <dt>Cambiar tab</dt><dd><kbd>1</kbd> <kbd>2</kbd> <kbd>3</kbd></dd>
        </dl>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
var JIRA_BASE_URL = '<?= htmlspecialchars($jiraBaseUrl) ?>';

function jiraStatusClassJs(status) {
    var s = (status || '').toLowerCase();
    if (/done|cerrad|resuelto|resolved|closed|complet|terminad|finaliz/.test(s)) return 's-done';
    if (/progres|curso|doing|trabajan|en\s*revisi/.test(s)) return 's-progress';
    if (/block|bloque|impedi/.test(s)) return 's-blocked';
    if (/todo|por\s*hacer|backlog|abiert|open|nuev/.test(s)) return 's-todo';
    return '';
}

function toast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) return;
    var el = document.createElement('div');
    el.className = 'toast' + (type !== 'success' ? ' ' + type : '');
    var icon = type === 'error' ? '!' : (type === 'warning' ? '!' : '✓');
    el.innerHTML = '<span class="toast-icon">' + icon + '</span><span>' + (message || '') + '</span>';
    container.appendChild(el);
    setTimeout(function() {
        el.classList.add('removing');
        setTimeout(function() { el.remove(); }, 220);
    }, 3500);
}

function setBtnLoading(btn, loading, originalText) {
    if (!btn) return;
    if (loading) {
        btn.disabled = true;
        btn.dataset.origText = btn.textContent;
        btn.innerHTML = '<span class="spinner"></span><span>' + (originalText || 'Procesando…') + '</span>';
    } else {
        btn.disabled = false;
        btn.textContent = btn.dataset.origText || originalText || btn.textContent;
    }
}

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

        if (!email) { toast('El email es requerido', 'warning'); return; }
        if (!token) { toast('El API token es requerido', 'warning'); return; }

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

    window.switchTab = function(name) {
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.classList.toggle('active', b.dataset.tab === name);
        });
        ['dias', 'jerarquia', 'matriz'].forEach(function(t) {
            var el = document.getElementById('tab-' + t);
            if (el) el.style.display = t === name ? '' : 'none';
        });
        try { localStorage.setItem('jb_tab', name); } catch (e) {}
    };

    /* Restaurar tab al cargar */
    (function() {
        try {
            var savedTab = localStorage.getItem('jb_tab');
            if (savedTab && ['dias', 'jerarquia', 'matriz'].indexOf(savedTab) !== -1) {
                window.switchTab(savedTab);
            }
        } catch (e) {}
    })();

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
        var day = head.closest('.day');
        if (day) day.classList.toggle('collapsed');
    };

    var HOURS_PER_DAY = <?= (float) $hoursPerDay ?>;
    var TODAY_STR = '<?= $today->format('Y-m-d') ?>';

    /**
     * Devuelve "HH:MM" tras el último worklog del día (sumando su duración).
     * Si no hay worklogs ese día, devuelve '09:00'.
     */
    function calcStartTimeFor(date) {
        var dayEl = document.querySelector('.day[data-date="' + date + '"]');
        if (!dayEl) return '09:00';
        var rows = dayEl.querySelectorAll('tr[data-wl-id]');
        var lastEnd = null;
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length < 6) return;
            var time = (cells[4].textContent || '').trim();
            var sec = parseInt(row.dataset.wlSeconds || 0);
            var parts = time.split(':');
            if (parts.length !== 2) return;
            var endMin = parseInt(parts[0]) * 60 + parseInt(parts[1]) + Math.floor(sec / 60);
            if (lastEnd === null || endMin > lastEnd) lastEnd = endMin;
        });
        if (lastEnd === null) return '09:00';
        var h = Math.floor(lastEnd / 60), m = lastEnd % 60;
        return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
    }

    /** Suma seg de los worklogs del día. */
    function dayLoggedSeconds(date) {
        var dayEl = document.querySelector('.day[data-date="' + date + '"]');
        if (!dayEl) return 0;
        var total = 0;
        dayEl.querySelectorAll('tr[data-wl-id]').forEach(function(r) {
            total += parseInt(r.dataset.wlSeconds || 0);
        });
        return total;
    }

    function openWorklogModal(mode, issueKey, worklogId, date, time, duration) {
        document.getElementById('wl-mode').value = mode;
        document.getElementById('wl-worklog-id').value = worklogId || '';
        var keyInput = document.getElementById('wl-key');
        keyInput.value = issueKey || '';
        keyInput.readOnly = (mode === 'edit');
        keyInput.style.background = (mode === 'edit') ? 'var(--color-bg-subtle)' : '';
        keyInput.style.color = (mode === 'edit') ? 'var(--color-text-muted)' : '';
        document.getElementById('wl-date').value = date || '';

        // En modo "add", calcular hora inicio según último worklog del día.
        if (mode === 'add' && !time) {
            time = calcStartTimeFor(date);
        }
        document.getElementById('wl-time').value = time || '09:00';
        document.getElementById('wl-duration').value = duration || '';

        hideWlError();
        hideSuggestions();
        hideKeyHint();
        clearChipActive();

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
        openWorklogModal('add', '', '', date, '', '');
    };

    window.openEditWorklog = function(e, issueKey, worklogId, date, time, duration) {
        e.stopPropagation();
        openWorklogModal('edit', issueKey, worklogId, date, time, duration);
    };

    window.closeAddWorklog = function() {
        document.getElementById('addWorklogModal').classList.remove('active');
        hideSuggestions();
    };

    /* Repetir último worklog del día anterior (o cualquier worklog reciente). */
    window.repeatLastWorklog = function(e, date) {
        e.stopPropagation();
        var rows = document.querySelectorAll('.day tr[data-wl-id]');
        if (!rows.length) {
            toast('No hay worklogs anteriores para repetir', 'warning');
            return;
        }
        var last = rows[0];
        var cells = last.querySelectorAll('td');
        var key = (cells[0].textContent || '').trim();
        var sec = parseInt(last.dataset.wlSeconds || 0);
        var dur = secondsToDurationStr(sec);
        openWorklogModal('add', key, '', date, '', dur);
    };

    function secondsToDurationStr(s) {
        var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
        if (h && m) return h + 'h ' + m + 'm';
        if (h) return h + 'h';
        return m + 'm';
    }

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
        var statusCls = jiraStatusClassJs(data.status || '');
        tr.innerHTML =
            '<td><a class="key-link" href="' + JIRA_BASE_URL + '/browse/' + issueKey + '" target="_blank">' + issueKey + '</a></td>' +
            '<td>' + (data.project || '') + '</td>' +
            '<td>' + truncate(data.summary || '', 55) + '</td>' +
            '<td><span class="status-tag ' + statusCls + '">' + (data.status || '') + '</span></td>' +
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

        hideWlError();
        var btn = document.getElementById('wl-submit');
        setBtnLoading(btn, true, mode === 'edit' ? 'Guardando…' : 'Registrando…');

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
                    toast(duration + ' actualizado en ' + key, 'success');
                } else {
                    data.issueKey = key;
                    insertWorklogInDOM(data, date, time, duration);
                    toast(duration + ' registrado en ' + key, 'success');
                }
            } else {
                showWlError(data.error || 'Error desconocido');
                setBtnLoading(btn, false);
            }
        })
        .catch(function() {
            showWlError('Error de conexión');
            setBtnLoading(btn, false);
        });
    };

    window.deleteWorklog = function(e, issueKey, worklogId, timeSpent) {
        e.stopPropagation();
        if (!confirm('¿Eliminar el registro de ' + timeSpent + ' en ' + issueKey + '?')) return;

        var btn = e.target;
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '…';
        btn.style.color = 'var(--color-text-subtle)';

        var row = document.querySelector('tr[data-wl-id="' + worklogId + '"]');

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_worklog', issueKey: issueKey, worklogId: worklogId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                toast(timeSpent + ' eliminado de ' + issueKey, 'success');
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
                if (totalHours > expectedHours) {
                    var over = Math.round((totalHours - expectedHours) * 100) / 100;
                    tag.className = 'tag tag-overtime'; tag.textContent = '+' + over + 'h';
                } else if (totalHours === expectedHours) {
                    tag.className = 'tag tag-ok'; tag.textContent = 'Completo';
                } else if (totalHours > 0) {
                    var rem = Math.round((expectedHours - totalHours) * 100) / 100;
                    tag.className = 'tag tag-partial'; tag.textContent = '-' + rem + 'h';
                } else {
                    tag.className = 'tag tag-empty'; tag.textContent = 'Sin registro';
                }
            } else {
                btn.disabled = false;
                btn.textContent = originalText;
                btn.style.color = '';
                toast('Error al eliminar: ' + (data.error || 'desconocido'), 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = originalText;
            btn.style.color = '';
            toast('Error de conexión', 'error');
        });
    };

    function showWlError(msg) {
        var el = document.getElementById('wl-error');
        el.textContent = msg;
        el.hidden = false;
    }

    function hideWlError() {
        var el = document.getElementById('wl-error');
        if (el) el.hidden = true;
    }

    function hideSuggestions() {
        var el = document.getElementById('wl-suggestions');
        if (el) { el.hidden = true; el.innerHTML = ''; }
    }

    function showKeyHint(html) {
        var el = document.getElementById('wl-key-hint');
        el.innerHTML = html;
        el.hidden = false;
    }
    function hideKeyHint() {
        var el = document.getElementById('wl-key-hint');
        if (el) el.hidden = true;
    }

    function clearChipActive() {
        document.querySelectorAll('.duration-chips .chip.active').forEach(function(c) {
            c.classList.remove('active');
        });
    }

    /** Typeahead: llama a /pick_issue en POST. */
    var pickTimer = null;
    var pickAbort = null;
    function fetchSuggestions(query) {
        if (pickAbort) try { pickAbort.abort(); } catch(e) {}
        if (!query || query.length < 2) { hideSuggestions(); return; }

        var box = document.getElementById('wl-suggestions');
        box.innerHTML = '<div class="suggestion-empty"><span class="spinner" style="margin-right:6px;vertical-align:middle"></span>Buscando…</div>';
        box.hidden = false;

        pickAbort = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        var opts = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: 'pick_issue', query: query })
        };
        if (pickAbort) opts.signal = pickAbort.signal;

        fetch(location.pathname + location.search, opts)
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(text) {
                        var msg;
                        try { msg = JSON.parse(text).error || ('HTTP ' + r.status); }
                        catch(e) { msg = 'HTTP ' + r.status; }
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(function(data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Respuesta sin ok');
                }
                if (data._debug) console.log('[picker]', data._debug);
                renderSuggestions(data.issues || []);
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;
                box.innerHTML = '<div class="suggestion-empty" style="color:var(--color-danger);">Error: ' + escHtml(err.message || 'sin detalle') + '</div>';
                box.hidden = false;
                console.error('[picker]', err);
            });
    }

    function renderSuggestions(issues) {
        var box = document.getElementById('wl-suggestions');
        if (!issues.length) {
            box.innerHTML = '<div class="suggestion-empty">Sin coincidencias</div>';
            box.hidden = false;
            return;
        }
        box.innerHTML = issues.map(function(iss, i) {
            return '<div class="suggestion-item" data-key="' + escAttr(iss.key) + '" data-summary="' + escAttr(iss.summary) + '" data-project="' + escAttr(iss.project) + '">' +
                '<span class="suggestion-key">' + escHtml(iss.key) + '</span>' +
                '<span class="suggestion-summary">' + escHtml(iss.summary) + '</span>' +
                (iss.project ? '<span class="suggestion-project">' + escHtml(iss.project) + '</span>' : '') +
                '</div>';
        }).join('');
        box.hidden = false;

        Array.prototype.forEach.call(box.querySelectorAll('.suggestion-item'), function(item) {
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectSuggestion(item);
            });
        });
    }

    function selectSuggestion(item) {
        var key = item.dataset.key;
        var summary = item.dataset.summary;
        var project = item.dataset.project;
        document.getElementById('wl-key').value = key;
        showKeyHint('<strong>' + escHtml(summary) + '</strong>' + (project ? ' · ' + escHtml(project) : ''));
        hideSuggestions();
        document.getElementById('wl-duration').focus();
    }

    function escHtml(s) {
        return (s || '').replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }
    function escAttr(s) { return escHtml(s); }

    /* ===== Filtros y búsqueda ===== */
    function applyFilters() {
        var search = (document.getElementById('search-input').value || '').trim().toLowerCase();
        var project = document.getElementById('filter-project').value;
        var initiative = document.getElementById('filter-initiative').value;
        var status = document.getElementById('filter-status').value;

        // Toggle visual del botón "Limpiar" y del filter-count
        var anyFilter = !!(search || project || initiative || status);
        document.getElementById('filter-clear').hidden = !anyFilter;
        document.getElementById('filter-project').classList.toggle('has-value', !!project);
        document.getElementById('filter-initiative').classList.toggle('has-value', !!initiative);
        document.getElementById('filter-status').classList.toggle('has-value', !!status);

        var visibleCount = 0, totalCount = 0;
        document.querySelectorAll('[data-search]').forEach(function(el) {
            totalCount++;
            var s = el.dataset.search || '';
            var p = el.dataset.project || '';
            var i = el.dataset.initiative || '';
            var st = el.dataset.status || '';
            var hide = false;
            if (search && s.indexOf(search) === -1) hide = true;
            if (project && p !== project) hide = true;
            if (initiative && i !== initiative) hide = true;
            if (status && st !== status) hide = true;
            el.style.display = hide ? 'none' : '';
            if (!hide) visibleCount++;
        });

        // Ocultar días sin filas visibles
        document.querySelectorAll('.day').forEach(function(day) {
            var rows = day.querySelectorAll('tr[data-search]');
            if (rows.length === 0) return;
            var visible = Array.prototype.filter.call(rows, function(r) { return r.style.display !== 'none'; });
            day.style.display = (anyFilter && visible.length === 0) ? 'none' : '';
        });

        // Ocultar épicas/iniciativas sin issues visibles
        document.querySelectorAll('.hier-epic').forEach(function(ep) {
            var rows = ep.querySelectorAll('tbody tr[data-search]');
            if (rows.length === 0) return;
            var visible = Array.prototype.filter.call(rows, function(r) { return r.style.display !== 'none'; });
            ep.style.display = (anyFilter && visible.length === 0) ? 'none' : '';
        });
        document.querySelectorAll('.hier-init').forEach(function(it) {
            var epics = it.querySelectorAll('.hier-epic');
            if (epics.length === 0) return;
            var visible = Array.prototype.filter.call(epics, function(e) { return e.style.display !== 'none'; });
            it.style.display = (anyFilter && visible.length === 0) ? 'none' : '';
        });

        var countEl = document.getElementById('filter-count');
        if (anyFilter && totalCount > 0) {
            countEl.textContent = visibleCount + ' / ' + totalCount + ' resultados';
            countEl.hidden = false;
        } else {
            countEl.hidden = true;
        }
    }

    window.clearFilters = function() {
        document.getElementById('search-input').value = '';
        document.getElementById('filter-project').value = '';
        document.getElementById('filter-initiative').value = '';
        document.getElementById('filter-status').value = '';
        applyFilters();
    };

    var filterTimer;
    var searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(applyFilters, 120);
        });
    }
    ['filter-project', 'filter-initiative', 'filter-status'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
    });

    /* ===== Toggle horas / días ===== */
    var currentUnit = 'h';
    function applyUnit(unit) {
        currentUnit = unit;
        document.querySelectorAll('.hr-day-label').forEach(function(l) {
            l.classList.toggle('active', l.dataset.unit === unit);
        });
        document.querySelectorAll('[data-hours]').forEach(function(el) {
            var h = parseFloat(el.dataset.hours);
            if (isNaN(h)) return;
            if (unit === 'd') {
                el.textContent = (Math.round(h / HOURS_PER_DAY * 100) / 100) + 'd';
            } else {
                el.textContent = h + 'h';
            }
        });
    }
    document.querySelectorAll('.hr-day-label').forEach(function(l) {
        l.addEventListener('click', function() { applyUnit(l.dataset.unit); });
    });

    /* ===== Listeners del modal de worklog ===== */
    var keyInput = document.getElementById('wl-key');
    if (keyInput) {
        keyInput.addEventListener('input', function() {
            hideKeyHint();
            clearTimeout(pickTimer);
            var q = this.value.trim();
            pickTimer = setTimeout(function() { fetchSuggestions(q); }, 250);
        });
        keyInput.addEventListener('blur', function() {
            // Pequeño delay para permitir click en sugerencia.
            setTimeout(hideSuggestions, 180);
        });
        keyInput.addEventListener('keydown', function(e) {
            var box = document.getElementById('wl-suggestions');
            if (box.hidden) return;
            var items = box.querySelectorAll('.suggestion-item');
            if (!items.length) return;
            var active = box.querySelector('.suggestion-item.active');
            var idx = active ? Array.prototype.indexOf.call(items, active) : -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                items[(idx + 1) % items.length].classList.add('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                items[(idx - 1 + items.length) % items.length].classList.add('active');
            } else if (e.key === 'Enter' && active) {
                e.preventDefault();
                selectSuggestion(active);
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });
    }

    /* Chips de duración */
    document.querySelectorAll('.duration-chips .chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            clearChipActive();
            var dur = chip.dataset.dur;
            if (chip.id === 'wl-chip-rest') {
                // Resto del día = HOURS_PER_DAY - lo ya logueado en ese día
                var date = document.getElementById('wl-date').value;
                var loggedSec = dayLoggedSeconds(date);
                var remainSec = Math.max(0, HOURS_PER_DAY * 3600 - loggedSec);
                if (remainSec === 0) {
                    toast('Este día ya tiene la jornada completa', 'warning');
                    return;
                }
                dur = secondsToDurationStr(remainSec);
            }
            document.getElementById('wl-duration').value = dur;
            chip.classList.add('active');
        });
    });

    /* Cmd/Ctrl+Enter para guardar dentro del modal */
    var modal = document.getElementById('addWorklogModal');
    if (modal) {
        modal.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                window.submitWorklog();
            }
        });
    }

    /* ===== Comparativa async (fetch después del render) ===== */
    (function() {
        var ph = document.getElementById('delta-placeholder');
        if (!ph) return;
        var current = parseFloat(ph.dataset.current);
        var start = ph.dataset.start;
        var end = ph.dataset.end;
        if (isNaN(current) || !start || !end) { ph.remove(); return; }

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'compare', startDate: start, endDate: end, currentTotal: current })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) { ph.remove(); return; }
            ph.classList.remove('loading');
            var d = data.delta;
            var prev = data.previousLogged;
            var title = 'vs. periodo anterior (' + prev + 'h)';
            if (d > 0.01) {
                ph.className = 'summary-delta up';
                ph.title = title;
                ph.innerHTML = '▲ +' + d + 'h vs. anterior';
            } else if (d < -0.01) {
                ph.className = 'summary-delta down';
                ph.title = title;
                ph.innerHTML = '▼ ' + d + 'h vs. anterior';
            } else {
                ph.className = 'summary-delta';
                ph.title = title;
                ph.innerHTML = '≈ igual al periodo anterior';
            }
        })
        .catch(function() { ph.remove(); });
    })();

    /* ===== Tema (claro/oscuro/auto) ===== */
    window.setTheme = function(theme) {
        var resolved = theme;
        if (theme === 'auto') {
            resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        if (resolved === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        try { localStorage.setItem('jb_theme', theme); } catch (e) {}
        document.querySelectorAll('.theme-btn[data-theme]').forEach(function(b) {
            b.classList.toggle('active', b.dataset.theme === theme);
        });
    };

    function loadTheme() {
        var saved = 'auto';
        try { saved = localStorage.getItem('jb_theme') || 'auto'; } catch (e) {}
        window.setTheme(saved);
    }
    loadTheme();

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            var saved = 'auto';
            try { saved = localStorage.getItem('jb_theme') || 'auto'; } catch (e) {}
            if (saved === 'auto') window.setTheme('auto');
        });
    }

    /* ===== Auto-refresh ===== */
    var refreshTimer = null;
    window.setAutoRefresh = function(seconds) {
        if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
        try { localStorage.setItem('jb_autorefresh', String(seconds)); } catch (e) {}
        document.querySelectorAll('.theme-btn[data-refresh]').forEach(function(b) {
            b.classList.toggle('active', parseInt(b.dataset.refresh) === seconds);
        });
        if (seconds > 0) {
            refreshTimer = setInterval(function() { location.reload(); }, seconds * 1000);
        }
    };
    (function() {
        var s = 0;
        try { s = parseInt(localStorage.getItem('jb_autorefresh') || '0'); } catch (e) {}
        window.setAutoRefresh(s);
    })();

    /* ===== Help modal ===== */
    window.openHelp  = function() { document.getElementById('helpModal').classList.add('active'); };
    window.closeHelp = function() { document.getElementById('helpModal').classList.remove('active'); };

    /* ===== Mis tareas + subtarea rápida ===== */
    var myTasksData = [];

    window.openMyTasks = function() {
        var modal = document.getElementById('myTasksModal');
        modal.classList.add('active');
        var listEl = document.getElementById('mt-list');
        listEl.innerHTML = '<div class="no-data"><span class="spinner"></span> Cargando…</div>';

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list_assigned' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) throw new Error(data.error || 'Error');
            myTasksData = data.issues || [];
            renderMyTasks();
        })
        .catch(function(err) {
            listEl.innerHTML = '<div class="no-data" style="color:var(--color-danger);">Error: ' + escHtml(err.message) + '</div>';
        });
    };
    window.closeMyTasks = function() {
        document.getElementById('myTasksModal').classList.remove('active');
    };

    function renderMyTasks() {
        var listEl = document.getElementById('mt-list');
        var onlyBugs = document.getElementById('mt-only-bugs').checked;
        var filter = (document.getElementById('mt-search').value || '').toLowerCase();

        var filtered = myTasksData.filter(function(iss) {
            if (onlyBugs && !/bug|defect|error/i.test(iss.issuetype || '')) return false;
            if (filter) {
                var hay = (iss.key + ' ' + iss.summary + ' ' + iss.project).toLowerCase();
                if (hay.indexOf(filter) === -1) return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            listEl.innerHTML = '<div class="no-data">Sin resultados</div>';
            return;
        }

        listEl.innerHTML = filtered.map(function(iss) {
            var typeCls = '';
            var type = (iss.issuetype || '').toLowerCase();
            if (/bug|defect|error/.test(type)) typeCls = 'bug';
            else if (/story|hist/.test(type))  typeCls = 'story';
            else if (/task|tarea/.test(type)) typeCls = 'task';

            return '<div class="mt-row">' +
                '<div class="mt-row-body">' +
                    '<div class="mt-row-summary">' + escHtml(iss.summary) + '</div>' +
                    '<div class="mt-row-meta">' +
                        '<a class="mt-row-key" href="' + JIRA_BASE_URL + '/browse/' + escHtml(iss.key) + '" target="_blank">' + escHtml(iss.key) + '</a> · ' +
                        '<span class="mt-row-type ' + typeCls + '">' + escHtml(iss.issuetype || '?') + '</span> · ' +
                        '<span>' + escHtml(iss.project) + '</span> · ' +
                        '<span class="status-tag ' + jiraStatusClassJs(iss.status) + '">' + escHtml(iss.status) + '</span>' +
                    '</div>' +
                '</div>' +
                '<button class="btn-sm primary" data-key="' + escAttr(iss.key) + '" data-summary="' + escAttr(iss.summary) + '">+ Subtarea</button>' +
            '</div>';
        }).join('');

        // Bind
        Array.prototype.forEach.call(listEl.querySelectorAll('.btn-sm[data-key]'), function(btn) {
            btn.addEventListener('click', function() {
                openSubtask(btn.dataset.key, btn.dataset.summary);
            });
        });
    }

    document.getElementById('mt-only-bugs').addEventListener('change', renderMyTasks);
    document.getElementById('mt-search').addEventListener('input', renderMyTasks);

    window.openSubtask = function(parentKey, parentSummary) {
        document.getElementById('st-parent').innerHTML =
            '<span class="key">' + escHtml(parentKey) + '</span> · ' + escHtml(parentSummary || '');
        document.getElementById('st-parent').dataset.parent = parentKey;
        document.getElementById('st-summary').value = '';
        document.getElementById('st-description').value = '';
        document.getElementById('st-error').hidden = true;
        var btn = document.getElementById('st-submit');
        btn.disabled = false;
        btn.textContent = 'Crear y poner en uso';
        document.getElementById('subtaskModal').classList.add('active');
        setTimeout(function() { document.getElementById('st-summary').focus(); }, 50);
    };
    window.closeSubtask = function() {
        document.getElementById('subtaskModal').classList.remove('active');
    };

    window.submitSubtask = function() {
        var parentKey = document.getElementById('st-parent').dataset.parent;
        var summary = document.getElementById('st-summary').value.trim();
        var description = document.getElementById('st-description').value.trim();

        var errEl = document.getElementById('st-error');
        if (!summary) {
            errEl.textContent = 'El título es obligatorio';
            errEl.hidden = false;
            return;
        }
        errEl.hidden = true;

        var btn = document.getElementById('st-submit');
        setBtnLoading(btn, true, 'Creando…');

        fetch(location.pathname + location.search, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_subtask',
                parentKey: parentKey,
                summary: summary,
                description: description
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) throw new Error(data.error || 'Error desconocido');
            closeSubtask();
            var msg = 'Subtarea ' + data.key + ' creada';
            if (data.transitionName) {
                msg += ' (' + data.transitionName + ')';
            } else if (data.transitionFailed) {
                msg += ' — no se pudo cambiar el estado';
            }
            toast(msg, 'success');
        })
        .catch(function(err) {
            errEl.textContent = err.message;
            errEl.hidden = false;
            setBtnLoading(btn, false);
        });
    };

    /* Cmd+Enter en subtarea */
    document.getElementById('subtaskModal').addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            submitSubtask();
        }
    });

    /* ===== Atajos de teclado globales ===== */
    function inputFocused() {
        var t = document.activeElement;
        if (!t) return false;
        var tag = (t.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || t.isContentEditable;
    }
    function modalOpen() {
        return document.querySelector('.overlay.active') !== null;
    }
    document.addEventListener('keydown', function(e) {
        // Esc cierra cualquier overlay
        if (e.key === 'Escape') {
            document.querySelectorAll('.overlay.active').forEach(function(o) { o.classList.remove('active'); });
            return;
        }
        if (inputFocused() || modalOpen()) return;

        if (e.key === '?' && e.shiftKey) { e.preventDefault(); window.openHelp(); return; }
        if (e.key === '/')               { e.preventDefault(); var s = document.getElementById('search-input'); if (s) s.focus(); return; }
        if (e.key === 'n' || e.key === 'N') { e.preventDefault(); window.openAddWorklog({ stopPropagation:function(){} }, TODAY_STR); return; }
        if (e.key === 'e' || e.key === 'E') {
            e.preventDefault();
            var btn = document.querySelector('.btn-edit-wl');
            if (btn) btn.click();
            return;
        }
        if (e.key === '1') { e.preventDefault(); window.switchTab('dias');      return; }
        if (e.key === '2') { e.preventDefault(); window.switchTab('jerarquia'); return; }
        if (e.key === '3') { e.preventDefault(); window.switchTab('matriz');    return; }
    });
})();
</script>
</body>
</html>
