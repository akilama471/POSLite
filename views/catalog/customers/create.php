<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Customer</div>
            <div class="muted" style="color: #b8c6cf;">Creates a `shop_customer` record through MVC</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>
            <section class="card" style="max-width: 960px;">
                <h1 style="margin-top:0;">New customer</h1>
                <form method="POST" action="/customers">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="cus_name">Customer Name</label>
                            <input class="input" id="cus_name" name="cus_name" required>
                        </div>
                        <div class="form-row">
                            <label for="cus_addr">Customer Address</label>
                            <input class="input" id="cus_addr" name="cus_addr">
                        </div>
                        <div class="form-row">
                            <label for="cus_nic">NIC No</label>
                            <input class="input" id="cus_nic" name="cus_nic">
                        </div>
                        <div class="form-row">
                            <label for="cus_emai">Email Address</label>
                            <input class="input" id="cus_emai" name="cus_emai">
                        </div>
                        <div class="form-row">
                            <label for="cus_mobile">Mobile Number</label>
                            <input class="input" id="cus_mobile" name="cus_mobile" required>
                        </div>
                        <div class="form-row">
                            <label for="cus_tpno">Telephone Number</label>
                            <input class="input" id="cus_tpno" name="cus_tpno">
                        </div>
                        <div class="form-row">
                            <label for="cus_bday">Birthday</label>
                            <input class="input" id="cus_bday" name="cus_bday" type="date">
                        </div>
                        <div class="form-row">
                            <label for="cus_remark">Remark</label>
                            <input class="input" id="cus_remark" name="cus_remark">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Create Customer</button>
                </form>
            </section>
        </main>
    </div>
</div>
