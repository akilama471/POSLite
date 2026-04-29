<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? "Print", ENT_QUOTES, "UTF-8") ?></title>
    <style>
        :root {
            --ink: #111;
            --line: #d0d0d0;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: #fff;
        }
        a { color: inherit; }
        .print-shell {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px;
        }
        .print-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .print-btn {
            border: 1px solid #111;
            background: #fff;
            color: #111;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            text-decoration: none;
        }
        @media print {
            .print-actions {
                display: none;
            }
            .print-shell {
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<?= $content ?>
</body>
</html>
