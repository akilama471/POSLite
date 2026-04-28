<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add User</div>
            <div class="muted" style="color: #b8c6cf;">Creates a legacy `sys_user` record through the new MVC layer</div>
        </div>
        <div class="tag">Default password: pass123</div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 760px;">
                <h1 style="margin-top:0;">New system user</h1>
                <form method="POST" action="/settings/users">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="form-row">
                        <label for="username">Username</label>
                        <input class="input" id="username" name="username" required>
                    </div>

                    <div class="form-row">
                        <label for="display_name">Visible Name</label>
                        <input class="input" id="display_name" name="display_name" required>
                    </div>

                    <div class="form-row">
                        <label for="shop_id">Shop</label>
                        <select class="input" id="shop_id" name="shop_id" required>
                            <option value="">Select a shop</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?= (int) $shop["shopid"] ?>"><?= htmlspecialchars($shop["shopid"] . " | " . ($shop["shop_info_name"] ?: $shop["shopname"]), ENT_QUOTES, "UTF-8") ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="privilege_id">Privilege Group</label>
                        <select class="input" id="privilege_id" name="privilege_id">
                            <?php foreach ($privileges as $privilege): ?>
                                <option value="<?= (int) $privilege["privilegeid"] ?>" <?= (int) $privilege["privilegeid"] === 2 ? "selected" : "" ?>>
                                    <?= htmlspecialchars((string) $privilege["privilegename"], ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn btn-primary" type="submit">Create User</button>
                </form>
            </section>
        </main>
    </div>
</div>
