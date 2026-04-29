<?php
$context = is_array($context ?? null) ? $context : [];
$slot = $context["slot"] ?? null;
$activeLog = $context["activeLog"] ?? null;
$latestLog = $context["latestLog"] ?? null;
$isActive = (bool) ($context["isActive"] ?? false);
$canStart = (bool) ($context["canStart"] ?? false);
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Cashier Duty</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `cashier_onoff.php` and the core `c_cashier.php` duty-close flow</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>Cashier</h3>
            <div class="nav-group">
                <a class="nav-link active" href="/cashier">Duty On / Off</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Shop</div>
                        <strong><?= htmlspecialchars((string) ($auth["shop_name"] ?? "Unknown Shop"), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Cashier Slot</div>
                        <strong><?= $slot ? "#" . (int) $slot["recordid"] : "Not Assigned" ?></strong>
                    </div>
                    <div>
                        <div class="muted">Status</div>
                        <strong><?= $isActive ? "Active" : "Closed" ?></strong>
                    </div>
                </div>

                <?php if ($slot === null): ?>
                    <p class="section-copy" style="margin:16px 0 0;">
                        You are not assigned to a cashier point yet. A system administrator must configure a slot before duty can be opened.
                    </p>
                <?php elseif ($isActive): ?>
                    <p class="section-copy" style="margin:16px 0 0;">
                        Cashier duty is active for this user. Close the slot before leaving payment or POS work.
                    </p>
                <?php else: ?>
                    <p class="section-copy" style="margin:16px 0 0;">
                        Check your drawer and sign in to cashier duty before using payment or POS operations.
                    </p>
                <?php endif; ?>
            </section>

            <?php if ($slot !== null && $canDutyOn && $canStart): ?>
                <section class="card" style="margin-bottom:18px;">
                    <h2 class="section-title">Open Duty</h2>
                    <form method="POST" action="/cashier/start">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                            <div class="form-row">
                                <label for="cash_open_balance">User Cash Open Balance</label>
                                <input class="input" id="cash_open_balance" name="cash_open_balance" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($latestLog["cash_closebal"] ?? "0"), ENT_QUOTES, "UTF-8") ?>" required>
                            </div>
                            <div class="form-row">
                                <label for="card_open_balance">User Card Open Balance</label>
                                <input class="input" id="card_open_balance" name="card_open_balance" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($latestLog["card_closebal"] ?? "0"), ENT_QUOTES, "UTF-8") ?>" required>
                            </div>
                            <div>
                                <button class="btn btn-primary" type="submit">Update Cash Details And Sign In</button>
                            </div>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($slot !== null && $isActive && $canDutyOff): ?>
                <section class="card" style="margin-bottom:18px;">
                    <h2 class="section-title">Close Duty</h2>
                    <form method="POST" action="/cashier/close">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                            <div class="form-row">
                                <label for="cash_close_balance">User Cash Close Balance</label>
                                <input class="input" id="cash_close_balance" name="cash_close_balance" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($activeLog["cash_openbal"] ?? "0"), ENT_QUOTES, "UTF-8") ?>" required>
                            </div>
                            <div class="form-row">
                                <label for="card_close_balance">User Card Close Balance</label>
                                <input class="input" id="card_close_balance" name="card_close_balance" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($activeLog["card_openbal"] ?? "0"), ENT_QUOTES, "UTF-8") ?>" required>
                            </div>
                            <div>
                                <button class="btn btn-primary" type="submit">Close Cashier Duty</button>
                            </div>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($latestLog !== null): ?>
                <section class="card">
                    <h2 class="section-title">Latest Duty Record</h2>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                        <div>
                            <div class="muted">Opened</div>
                            <strong><?= htmlspecialchars((string) ($latestLog["recordtime"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Closed</div>
                            <strong><?= htmlspecialchars((string) ($latestLog["close_time"] ?? "Still Open"), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Cash Open</div>
                            <strong><?= htmlspecialchars($formatMoney($latestLog["cash_openbal"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Card Open</div>
                            <strong><?= htmlspecialchars($formatMoney($latestLog["card_openbal"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
