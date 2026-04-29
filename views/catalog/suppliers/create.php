<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Supplier</div>
            <div class="muted" style="color: #b8c6cf;">Creates a `shop_supplier` record through MVC</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 760px;">
                <h1 style="margin-top:0;">New supplier</h1>
                <form method="POST" action="/suppliers">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="form-row">
                        <label for="supplier_name">Supplier Name</label>
                        <input class="input" id="supplier_name" name="supplier_name" required>
                    </div>

                    <div class="form-row">
                        <label for="supplier_address">Address</label>
                        <input class="input" id="supplier_address" name="supplier_address" required>
                    </div>

                    <div class="form-row">
                        <label for="supplier_mobile">Mobile Number</label>
                        <input class="input" id="supplier_mobile" name="supplier_mobile" required>
                    </div>

                    <button class="btn btn-primary" type="submit">Create Supplier</button>
                </form>
            </section>
        </main>
    </div>
</div>
