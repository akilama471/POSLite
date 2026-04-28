<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Shop</div>
            <div class="muted" style="color: #b8c6cf;">Creates a `sys_shop` record through MVC</div>
        </div>
        <div class="tag">Next Shop ID: <?= (int) $nextShopId ?></div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 920px;">
                <h1 style="margin-top:0;">New shop</h1>
                <form method="POST" action="/settings/shops">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label>Generated Shop ID</label>
                            <input class="input" value="<?= (int) $nextShopId ?>" readonly>
                        </div>
                        <div class="form-row">
                            <label for="pos_uniq">Bill Unique ID</label>
                            <input class="input" id="pos_uniq" name="pos_uniq" maxlength="2" required>
                        </div>
                        <div class="form-row">
                            <label for="shopname">Shop Name</label>
                            <input class="input" id="shopname" name="shopname" required>
                        </div>
                        <div class="form-row">
                            <label for="shop_info_name">Shop Info Name</label>
                            <input class="input" id="shop_info_name" name="shop_info_name" required>
                        </div>
                        <div class="form-row">
                            <label for="shopaddress">Address</label>
                            <input class="input" id="shopaddress" name="shopaddress" required>
                        </div>
                        <div class="form-row">
                            <label for="shop_tel_1">Telephone</label>
                            <input class="input" id="shop_tel_1" name="shop_tel_1" required>
                        </div>
                        <div class="form-row">
                            <label for="shopemail">Email</label>
                            <input class="input" id="shopemail" name="shopemail">
                        </div>
                        <div class="form-row">
                            <label for="shop_fax">Fax</label>
                            <input class="input" id="shop_fax" name="shop_fax">
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="bill_foot_1">Bill Foot Note 1</label>
                        <textarea class="input" id="bill_foot_1" name="bill_foot_1" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <label for="bill_foot_2">Bill Foot Note 2</label>
                        <textarea class="input" id="bill_foot_2" name="bill_foot_2" rows="3"></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Create Shop</button>
                </form>
            </section>
        </main>
    </div>
</div>
