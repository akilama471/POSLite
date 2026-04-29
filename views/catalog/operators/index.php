<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Manage Operators</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_operator.php`, `c_man_rcv_ope_show.php`, and `c_man_rcv_ope_updt.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 760px; margin-bottom:18px;">
                <h1 style="margin-top:0;">Add new operator</h1>
                <form method="POST" action="/operators">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="form-row">
                        <label for="operator_name">Operator Name</label>
                        <input class="input" id="operator_name" name="operator_name" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Add Operator</button>
                </form>
            </section>

            <section class="card">
                <h2 class="section-title">Current operators</h2>
                <div class="stack">
                    <?php foreach ($operators as $operator): ?>
                        <form method="POST" action="/operators/<?= (int) $operator["recordid"] ?>" class="card" style="padding:16px;">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <div style="display:grid; gap:12px; grid-template-columns: 110px 1fr auto;">
                                <div>
                                    <div class="muted" style="font-size:0.86rem;">Operator ID</div>
                                    <div><?= (int) $operator["recordid"] ?></div>
                                </div>
                                <div class="form-row" style="margin:0;">
                                    <label for="operator_<?= (int) $operator["recordid"] ?>">Name</label>
                                    <input
                                        class="input"
                                        id="operator_<?= (int) $operator["recordid"] ?>"
                                        name="operator_name"
                                        value="<?= htmlspecialchars((string) $operator["operator_name"], ENT_QUOTES, "UTF-8") ?>"
                                        required
                                    >
                                </div>
                                <div style="align-self:end;">
                                    <button class="btn btn-primary" type="submit">Update</button>
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
