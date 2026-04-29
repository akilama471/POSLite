<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Edit Supplier</div>
            <div class="muted" style="color: #b8c6cf;">Updates the legacy supplier record without the old modal AJAX flow</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 760px;">
                <h1 style="margin-top:0;">Supplier #<?= (int) $supplier["supplierid"] ?></h1>
                <form method="POST" action="/suppliers/<?= (int) $supplier["supplierid"] ?>">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="form-row">
                        <label for="supplier_name">Supplier Name</label>
                        <input class="input" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?>" required>
                    </div>

                    <div class="form-row">
                        <label for="supplier_address">Address</label>
                        <input class="input" id="supplier_address" name="supplier_address" value="<?= htmlspecialchars((string) $supplier["supplier_address"], ENT_QUOTES, "UTF-8") ?>" required>
                    </div>

                    <div class="form-row">
                        <label for="supplier_mobile">Mobile Number</label>
                        <input class="input" id="supplier_mobile" name="supplier_mobile" value="<?= htmlspecialchars((string) $supplier["supplier_mobile"], ENT_QUOTES, "UTF-8") ?>" required>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a class="btn" href="/suppliers" style="background:#eef2f5; color:#163041;">Cancel</a>
                        <button class="btn btn-primary" type="submit">Update Supplier</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
