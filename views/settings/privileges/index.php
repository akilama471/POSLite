<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Function Permission</div>
            <div class="muted" style="color: #b8c6cf;">Permission group registry migrated to MVC</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: 320px 1fr; align-items:start;">
                <section class="card">
                    <h1 style="margin-top:0;">New permission group</h1>
                    <form method="POST" action="/settings/privileges">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="form-row">
                            <label for="name">Permission Name</label>
                            <input class="input" id="name" name="name" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Add Group</button>
                    </form>
                </section>

                <section class="card">
                    <h1 style="margin-top:0;">Current permission groups</h1>
                    <div class="stack">
                        <?php foreach ($privileges as $privilege): ?>
                            <form method="POST" action="/settings/privileges/<?= (int) $privilege["privilegeid"] ?>" class="card" style="padding:16px;">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                <div class="form-row" style="margin-bottom:10px;">
                                    <label for="privilege_<?= (int) $privilege["privilegeid"] ?>">Group Name</label>
                                    <input class="input" id="privilege_<?= (int) $privilege["privilegeid"] ?>" name="name" value="<?= htmlspecialchars((string) $privilege["privilegename"], ENT_QUOTES, "UTF-8") ?>" required>
                                </div>
                                <button class="btn btn-primary" type="submit">Rename Group</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
