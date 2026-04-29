<?php $shopLabel = (string) (($auth["shop_name"] ?? "") !== "" ? $auth["shop_name"] : "Current Shop"); ?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Item Alert Configuration</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `item_alert_config.php`, `c_itm_alert.php`, and `c_itm_alert_view.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <p class="section-copy" style="margin:0 0 14px;">
                    Add low-stock alert limits for <?= htmlspecialchars($shopLabel, ENT_QUOTES, "UTF-8") ?>. You can update or remove records at any time.
                </p>

                <form method="POST" action="/item-alerts">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="alert_category">Select Category Name</label>
                            <select class="input" id="alert_category" name="category_name">
                                <option value="">Choose category...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>">
                                        <?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="alert_item">Select Item Name</label>
                            <input class="input" id="alert_item" name="item_name" list="alert_item_list" required>
                            <datalist id="alert_item_list"></datalist>
                        </div>
                        <div class="form-row">
                            <label for="alert_qty">Alert Qty</label>
                            <input class="input" id="alert_qty" name="alert_qty" type="number" min="1" required>
                        </div>
                        <div>
                            <button class="btn btn-primary" type="submit">Add New Record</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="card">
                <h2 class="section-title">Current alert limits</h2>
                <div class="stack">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="card" style="padding:16px;">
                            <div style="display:grid; gap:12px; grid-template-columns: 1fr auto;">
                                <form method="POST" action="/item-alerts/<?= (int) $alert["recordid"] ?>">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <div class="grid" style="grid-template-columns: minmax(260px, 1fr) 180px;">
                                        <div class="form-row">
                                            <label for="alert_name_<?= (int) $alert["recordid"] ?>">Item Name</label>
                                            <input class="input" id="alert_name_<?= (int) $alert["recordid"] ?>" value="<?= htmlspecialchars((string) $alert["item_name"], ENT_QUOTES, "UTF-8") ?>" readonly>
                                        </div>
                                        <div class="form-row">
                                            <label for="alert_qty_<?= (int) $alert["recordid"] ?>">Alert Qty</label>
                                            <input class="input" id="alert_qty_<?= (int) $alert["recordid"] ?>" name="alert_qty" type="number" min="1" value="<?= (int) $alert["alert_qty"] ?>" required>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Update</button>
                                </form>
                                <form method="POST" action="/item-alerts/<?= (int) $alert["recordid"] ?>/delete" onsubmit="return confirm('Remove this alert record?');" style="align-self:end;">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($alerts === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No alert limits configured for this shop yet.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
const alertCategory = document.getElementById("alert_category");
const alertItemList = document.getElementById("alert_item_list");
const alertItemInput = document.getElementById("alert_item");

async function loadAlertItems(categoryName) {
    alertItemList.innerHTML = "";
    alertItemInput.value = "";

    if (!categoryName) {
        return;
    }

    const response = await fetch("/api/items/by-category?category=" + encodeURIComponent(categoryName));
    if (!response.ok) {
        return;
    }

    const items = await response.json();
    for (const name of items) {
        const option = document.createElement("option");
        option.value = name;
        alertItemList.appendChild(option);
    }
}

alertCategory?.addEventListener("change", function () {
    loadAlertItems(this.value);
});
</script>
