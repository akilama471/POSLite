<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">System Settings</div>
            <div class="muted" style="color: #b8c6cf;">Centralized MVC settings and permission controls</div>
        </div>
        <div class="tag">Step 2 migration</div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>

        <main class="page">
            <section class="panel" style="padding: 22px;">
                <h1 style="margin: 0 0 8px;">Settings overview</h1>
                <p class="section-copy">This replaces the old iframe-based settings shell. The migrated routes below are active; the remaining shop and cashier settings are still queued for later migration steps.</p>
            </section>

            <section class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top: 18px;">
                <?php foreach ($sections as $section): ?>
                    <?php if ($section["permission"] && !can($section["permission"])) continue; ?>
                    <article class="card">
                        <h2 class="section-title"><?= htmlspecialchars($section["title"], ENT_QUOTES, "UTF-8") ?></h2>
                        <ul class="list">
                            <?php foreach ($section["items"] as $item): ?>
                                <?php if ($item["permission"] && !can($item["permission"])) continue; ?>
                                <li>
                                    <span><?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?></span>
                                    <a class="success" href="<?= htmlspecialchars($item["href"], ENT_QUOTES, "UTF-8") ?>">Open</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </section>
        </main>
    </div>
</div>
