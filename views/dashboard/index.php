<?php
$displayName = $auth["display_name"] ?? "User";
$shopName = $auth["shop_name"] ?? "All Shops";
$currentPath = parse_url($_SERVER["REQUEST_URI"] ?? "/dashboard", PHP_URL_PATH) ?: "/dashboard";

$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};

$cards = [
    ["label" => "Today Sale", "value" => $formatMoney($summary["today_sale"] ?? 0)],
    ["label" => "Today Purchases", "value" => $formatMoney($summary["today_purchases"] ?? 0)],
    ["label" => "Bill Count", "value" => (string) (int) ($summary["bill_count"] ?? 0)],
    ["label" => "Expenses", "value" => $formatMoney($summary["expense_total"] ?? 0)],
    ["label" => "Income", "value" => $formatMoney($summary["income_total"] ?? 0)],
    ["label" => "Repair Jobs", "value" => (string) (int) ($summary["repair_count"] ?? 0)],
    ["label" => "Repair Done", "value" => (string) (int) ($summary["repair_done"] ?? 0)],
    ["label" => "Return Bills", "value" => (string) (int) ($summary["return_bill"] ?? 0)],
];
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Nextgen Easy POS</div>
            <div class="muted" style="color: #b8c6cf;">Welcome <?= htmlspecialchars($displayName, ENT_QUOTES, "UTF-8") ?> | Shop: <?= htmlspecialchars($shopName, ENT_QUOTES, "UTF-8") ?></div>
        </div>

        <form method="POST" action="<?= htmlspecialchars(app_url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-ghost" type="submit">Log Out</button>
        </form>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>Navigation</h3>
            <div class="nav-group">
                <?php foreach ($menu as $item): ?>
                    <?php if (!($permissions[$item["key"]] ?? false)): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php if ($item["href"] === "#"): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <a
                        class="nav-link <?= $currentPath === $item["href"] ? "active" : "" ?>"
                        href="<?= htmlspecialchars($item["href"], ENT_QUOTES, "UTF-8") ?>"
                    >
                        <?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="page">
            <div class="stack">
                <section class="panel" style="padding: 22px;">
                    <h1 style="margin: 0 0 8px;">Today's Overview</h1>
                    <p class="section-copy">Real-time snapshot of today's sales, purchases, repairs, and cashier activity.</p>
                </section>

                <section class="grid cards">
                    <?php foreach ($cards as $card): ?>
                        <article class="card">
                            <p class="metric-label"><?= htmlspecialchars($card["label"], ENT_QUOTES, "UTF-8") ?></p>
                            <p class="metric-value"><?= htmlspecialchars($card["value"], ENT_QUOTES, "UTF-8") ?></p>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                    <article class="card">
                        <h2 class="section-title">15 Day Sales Trend</h2>
                        <ul class="list">
                            <?php foreach ($salesTrend as $point): ?>
                                <li>
                                    <span><?= htmlspecialchars($point["label"], ENT_QUOTES, "UTF-8") ?></span>
                                    <strong><?= htmlspecialchars($formatMoney($point["value"]), ENT_QUOTES, "UTF-8") ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>

                    <article class="card">
                        <h2 class="section-title">Fast Moving Items</h2>
                        <ul class="list">
                            <?php foreach ($topItems as $item): ?>
                                <li>
                                    <span><?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?></span>
                                    <strong><?= (int) $item["count"] ?></strong>
                                </li>
                            <?php endforeach; ?>
                            <?php if ($topItems === []): ?>
                                <li><span class="muted">No item movement data available.</span></li>
                            <?php endif; ?>
                        </ul>
                    </article>

                    <article class="card">
                        <h2 class="section-title">Fast Moving Phones</h2>
                        <ul class="list">
                            <?php foreach ($topPhones as $item): ?>
                                <li>
                                    <span><?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?></span>
                                    <strong><?= (int) $item["count"] ?></strong>
                                </li>
                            <?php endforeach; ?>
                            <?php if ($topPhones === []): ?>
                                <li><span class="muted">No phone movement data available.</span></li>
                            <?php endif; ?>
                        </ul>
                    </article>

                    <article class="card">
                        <h2 class="section-title">Fast Moving Cards</h2>
                        <ul class="list">
                            <?php foreach ($topCards as $item): ?>
                                <li>
                                    <span><?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?></span>
                                    <strong><?= (int) $item["count"] ?></strong>
                                </li>
                            <?php endforeach; ?>
                            <?php if ($topCards === []): ?>
                                <li><span class="muted">No recharge card movement data available.</span></li>
                            <?php endif; ?>
                        </ul>
                    </article>
                </section>
            </div>
        </main>
    </div>
</div>
