<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? "Nextgen Easy POS", ENT_QUOTES, "UTF-8") ?></title>
    <style>
        :root {
            --bg: #f4f6f8;
            --panel: #ffffff;
            --panel-alt: #10212f;
            --text: #163041;
            --muted: #5f7382;
            --border: #d9e0e6;
            --accent: #db5b35;
            --accent-soft: #f8e0d7;
            --success: #1d8f6a;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #edf2f5 0%, var(--bg) 220px);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .app-shell { min-height: 100vh; }
        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .auth-card, .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(20, 35, 48, 0.08);
        }
        .topbar {
            background: var(--panel-alt);
            color: #fff;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .brand {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .shell-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: calc(100vh - 72px);
        }
        .sidebar {
            background: #142634;
            color: #eef4f7;
            padding: 24px 18px;
        }
        .sidebar h3 {
            margin: 0 0 16px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9fb0bd;
        }
        .nav-group {
            display: grid;
            gap: 8px;
        }
        .nav-link {
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
        }
        .nav-link.active {
            background: rgba(219, 91, 53, 0.18);
            color: #fff4ef;
            border: 1px solid rgba(219, 91, 53, 0.35);
        }
        .page {
            padding: 24px;
        }
        .grid {
            display: grid;
            gap: 18px;
        }
        .grid.cards {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .card {
            padding: 18px;
            border-radius: 18px;
            background: var(--panel);
            border: 1px solid var(--border);
        }
        .metric-label {
            margin: 0 0 8px;
            color: var(--muted);
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .metric-value {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 700;
        }
        .stack {
            display: grid;
            gap: 16px;
        }
        .section-title {
            margin: 0 0 8px;
            font-size: 1.1rem;
        }
        .section-copy {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-soft);
            color: #8f3b1f;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .form-row {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }
        .form-row label {
            font-size: 0.92rem;
            font-weight: 600;
        }
        .input {
            width: 100%;
            border: 1px solid #cfd7de;
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 1rem;
            background: #fff;
        }
        .btn {
            border: 0;
            border-radius: 12px;
            padding: 12px 16px;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--accent);
            color: #fff;
            font-weight: 700;
        }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }
        .alert-error {
            background: #fde8e4;
            color: #932d18;
            border: 1px solid #f6c6ba;
        }
        .list {
            display: grid;
            gap: 12px;
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .list li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            background: #fbfcfd;
            border: 1px solid var(--border);
            border-radius: 14px;
        }
        .muted { color: var(--muted); }
        .success { color: var(--success); }

        @media (max-width: 900px) {
            .shell-grid {
                grid-template-columns: 1fr;
            }
            .sidebar {
                padding-bottom: 12px;
            }
        }
    </style>
</head>
<body>
<?= $content ?>
</body>
</html>
