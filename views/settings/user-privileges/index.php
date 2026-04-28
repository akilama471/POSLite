<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">User Function Mapping</div>
            <div class="muted" style="color: #b8c6cf;">User-to-privilege assignment migrated from the legacy modal workflow</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card">
                <h1 style="margin-top:0;">Assign privilege groups</h1>
                <div class="stack">
                    <?php foreach ($users as $user): ?>
                        <form method="POST" action="/settings/user-privileges/<?= (int) $user["myid"] ?>" class="card" style="padding:16px;">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <div style="display:grid; gap:12px; grid-template-columns: 1fr minmax(220px, 280px) auto; align-items:end;">
                                <div>
                                    <p class="metric-label">User</p>
                                    <p class="metric-value" style="font-size:1.1rem;"><?= htmlspecialchars((string) $user["visibledata"], ENT_QUOTES, "UTF-8") ?></p>
                                    <p class="muted" style="margin:4px 0 0;"><?= htmlspecialchars((string) $user["ankaya"], ENT_QUOTES, "UTF-8") ?></p>
                                </div>
                                <div class="form-row" style="margin-bottom:0;">
                                    <label for="privilege_id_<?= (int) $user["myid"] ?>">Privilege Group</label>
                                    <select class="input" id="privilege_id_<?= (int) $user["myid"] ?>" name="privilege_id">
                                        <?php foreach ($privileges as $privilege): ?>
                                            <option value="<?= (int) $privilege["privilegeid"] ?>" <?= (int) $privilege["privilegeid"] === (int) ($user["privilageid"] ?? 0) ? "selected" : "" ?>>
                                                <?= htmlspecialchars((string) $privilege["privilegename"], ENT_QUOTES, "UTF-8") ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="btn btn-primary" type="submit">Update</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
