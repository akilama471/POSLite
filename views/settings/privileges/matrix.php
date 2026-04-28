<?php
$formAction = $type === "functions"
    ? "/settings/privileges/" . (int) $privilege["privilegeid"] . "/functions"
    : "/settings/privileges/" . (int) $privilege["privilegeid"] . "/reports";
$label = $type === "functions" ? "Function" : "Report";
?>
<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand"><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?> Permission Matrix</div>
            <div class="muted" style="color: #b8c6cf;"><?= htmlspecialchars((string) ($privilege["privilegename"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
        </div>
        <a class="btn btn-ghost" href="/settings/privileges">Back</a>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="panel" style="padding: 22px;">
                <h1 style="margin:0 0 8px;"><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?> permissions</h1>
                <p class="section-copy">This writes directly to `sys_privilegemap` using the same legacy keys (`<?= $type === "functions" ? "p_*" : "r_*" ?>`) but through MVC routes and CSRF-protected forms.</p>
            </section>

            <form method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES, "UTF-8") ?>" class="card" style="margin-top:18px;">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                    <?php foreach ($catalog as $key => $text): ?>
                        <label class="card" style="padding:14px; display:flex; gap:12px; align-items:flex-start;">
                            <input type="checkbox" name="<?= htmlspecialchars($key, ENT_QUOTES, "UTF-8") ?>" value="1" <?= !empty($values[$key]) ? "checked" : "" ?> style="margin-top:4px;">
                            <span>
                                <strong><?= htmlspecialchars($key, ENT_QUOTES, "UTF-8") ?></strong><br>
                                <span class="muted"><?= htmlspecialchars($text, ENT_QUOTES, "UTF-8") ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:18px;">
                    <button class="btn btn-primary" type="submit">Save Permission Matrix</button>
                </div>
            </form>
        </main>
    </div>
</div>
