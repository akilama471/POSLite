<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Edit Shop</div>
            <div class="muted" style="color: #b8c6cf;">Updates the legacy `sys_shop` record</div>
        </div>
        <div class="tag">Shop ID: <?= (int) $shop["shopid"] ?></div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 920px;">
                <h1 style="margin-top:0;">Shop details</h1>
                <form method="POST" action="/settings/shops/<?= (int) $shop["shopid"] ?>">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="shopname">Shop Name</label>
                            <input class="input" id="shopname" name="shopname" value="<?= htmlspecialchars((string) $shop["shopname"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="shop_info_name">Shop Info Name</label>
                            <input class="input" id="shop_info_name" name="shop_info_name" value="<?= htmlspecialchars((string) $shop["shop_info_name"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="shopaddress">Address</label>
                            <input class="input" id="shopaddress" name="shopaddress" value="<?= htmlspecialchars((string) $shop["shopaddress"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="shop_tel_1">Telephone</label>
                            <input class="input" id="shop_tel_1" name="shop_tel_1" value="<?= htmlspecialchars((string) $shop["shop_tel_1"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="shopemail">Email</label>
                            <input class="input" id="shopemail" name="shopemail" value="<?= htmlspecialchars((string) $shop["shopemail"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="shop_fax">Fax</label>
                            <input class="input" id="shop_fax" name="shop_fax" value="<?= htmlspecialchars((string) $shop["shop_fax"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="bill_foot_1">Bill Foot Note 1</label>
                        <textarea class="input" id="bill_foot_1" name="bill_foot_1" rows="3"><?= htmlspecialchars((string) $shop["bill_foot_1"], ENT_QUOTES, "UTF-8") ?></textarea>
                    </div>
                    <div class="form-row">
                        <label for="bill_foot_2">Bill Foot Note 2</label>
                        <textarea class="input" id="bill_foot_2" name="bill_foot_2" rows="3"><?= htmlspecialchars((string) $shop["bill_foot_2"], ENT_QUOTES, "UTF-8") ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Update Shop</button>
                </form>
            </section>
        </main>
    </div>
</div>
