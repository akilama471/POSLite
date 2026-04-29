<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Edit Customer</div>
            <div class="muted" style="color: #b8c6cf;">Updates the legacy customer record with routed MVC forms</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 960px;">
                <h1 style="margin-top:0;">Customer #<?= (int) $customer["recordid"] ?></h1>
                <form method="POST" action="/customers/<?= (int) $customer["recordid"] ?>">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="cus_name">Customer Name</label>
                            <input class="input" id="cus_name" name="cus_name" value="<?= htmlspecialchars((string) $customer["cus_name"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="cus_addr">Customer Address</label>
                            <input class="input" id="cus_addr" name="cus_addr" value="<?= htmlspecialchars((string) ($customer["cus_addr"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="cus_nic">NIC No</label>
                            <input class="input" id="cus_nic" name="cus_nic" value="<?= htmlspecialchars((string) ($customer["cus_nic"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="cus_emai">Email Address</label>
                            <input class="input" id="cus_emai" name="cus_emai" value="<?= htmlspecialchars((string) ($customer["cus_emai"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="cus_mobile">Mobile Number</label>
                            <input class="input" id="cus_mobile" name="cus_mobile" value="<?= htmlspecialchars((string) $customer["cus_mobile"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>
                        <div class="form-row">
                            <label for="cus_tpno">Telephone Number</label>
                            <input class="input" id="cus_tpno" name="cus_tpno" value="<?= htmlspecialchars((string) ($customer["cus_tpno"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="cus_bday">Birthday</label>
                            <input class="input" id="cus_bday" name="cus_bday" type="date" value="<?= htmlspecialchars((string) ($customer["cus_bday"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="cus_remark">Remark</label>
                            <input class="input" id="cus_remark" name="cus_remark" value="<?= htmlspecialchars((string) ($customer["cus_remark"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a class="btn" href="/customers" style="background:#eef2f5; color:#163041;">Cancel</a>
                        <button class="btn btn-primary" type="submit">Update Customer</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
