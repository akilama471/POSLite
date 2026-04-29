<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Manage Item Colors</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_item_color.php` and `c_man_col_show.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 640px;">
                <h1 style="margin-top:0;">Create color</h1>
                <form method="POST" action="/item-colors">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="form-row">
                        <label for="name">Color Name</label>
                        <input class="input" id="name" name="name" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Add Color</button>
                </form>
            </section>

            <section class="card" style="margin-top:18px;">
                <h2 class="section-title">Current colors</h2>
                <div class="stack">
                    <?php foreach ($colors as $color): ?>
                        <div class="card" style="padding:16px;">
                            <div style="display:grid; gap:12px; grid-template-columns: 1fr auto;">
                                <form method="POST" action="/item-colors/<?= (int) $color["color_id"] ?>">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <div class="form-row" style="margin-bottom:10px;">
                                        <label for="color_<?= (int) $color["color_id"] ?>">Color Name</label>
                                        <input class="input" id="color_<?= (int) $color["color_id"] ?>" name="name" value="<?= htmlspecialchars((string) $color["color_name"], ENT_QUOTES, "UTF-8") ?>" required>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Update</button>
                                </form>
                                <form method="POST" action="/item-colors/<?= (int) $color["color_id"] ?>/delete" onsubmit="return confirm('Delete this color?');" style="align-self:end;">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
