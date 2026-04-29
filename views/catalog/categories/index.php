<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Manage Categories</div>
            <div class="muted" style="color: #b8c6cf;">`prod_category` CRUD migrated from the legacy page and AJAX helpers</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 640px;">
                <h1 style="margin-top:0;">Create category</h1>
                <form method="POST" action="/categories">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="form-row">
                        <label for="name">Category Name</label>
                        <input class="input" id="name" name="name" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Add Category</button>
                </form>
            </section>

            <section class="card" style="margin-top:18px;">
                <h2 class="section-title">Current categories</h2>
                <div class="stack">
                    <?php foreach ($categories as $category): ?>
                        <div class="card" style="padding:16px;">
                            <div style="display:grid; gap:12px; grid-template-columns: 1fr auto;">
                                <form method="POST" action="/categories/<?= (int) $category["catid"] ?>">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <div class="form-row" style="margin-bottom:10px;">
                                        <label for="cat_<?= (int) $category["catid"] ?>">Category Name</label>
                                        <input class="input" id="cat_<?= (int) $category["catid"] ?>" name="name" value="<?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>" required>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Update</button>
                                </form>
                                <form method="POST" action="/categories/<?= (int) $category["catid"] ?>/delete" onsubmit="return confirm('Delete this category?');" style="align-self:end;">
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
