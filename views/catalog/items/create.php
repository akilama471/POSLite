<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Item</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_item_a.php` with recharge-card support</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 960px;">
                <h1 style="margin-top:0;">Create item</h1>
                <form method="POST" action="/items">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="item_cat">Item Category</label>
                            <select class="input" id="item_cat" name="item_cat" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category["catid"] ?>">
                                        <?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="used_type">Stock Controlled By</label>
                            <select class="input" id="used_type" name="used_type" required onchange="toggleRechargeFields(this.value)">
                                <option value="">Select type</option>
                                <option value="1">By Item Code</option>
                                <option value="2">By IMEI Number</option>
                                <option value="3">By Recharge Card</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="item_name">Item Name</label>
                            <input class="input" id="item_name" name="item_name" list="item_names" required>
                            <datalist id="item_names">
                                <?php foreach ($existingItems as $item): ?>
                                    <option value="<?= htmlspecialchars((string) $item["item_name"], ENT_QUOTES, "UTF-8") ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>

                    <div id="recharge-fields" hidden>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                            <div class="form-row">
                                <label for="operator_id">Operator</label>
                                <select class="input" id="operator_id" name="operator_id">
                                    <option value="">Select operator</option>
                                    <?php foreach ($operators as $operator): ?>
                                        <option value="<?= (int) $operator["recordid"] ?>">
                                            <?= htmlspecialchars((string) $operator["operator_name"], ENT_QUOTES, "UTF-8") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label for="card_remark">Remark</label>
                                <input class="input" id="card_remark" name="card_remark">
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Create Item</button>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
function toggleRechargeFields(value) {
    const isRecharge = String(value) === "3";
    const wrapper = document.getElementById("recharge-fields");
    const operator = document.getElementById("operator_id");

    wrapper.hidden = !isRecharge;
    operator.required = isRecharge;
}
</script>
