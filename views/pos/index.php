<?php
$cart = is_array($cart ?? null) ? $cart : [];
$customer = $cart["customer"] ?? ["id" => 0, "name" => "Cash Customer"];
$lines = $cart["lines"] ?? [];
$payment = $cart["payment"] ?? ["method" => "cash", "cash_amount" => 0, "card_amount" => 0, "card_number" => ""];
$summary = $cart["summary"] ?? ["total" => 0, "paid" => 0, "balance" => 0];
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Point Of Sale</div>
            <div class="muted" style="color: #b8c6cf;">First MVC POS slice: customer lookup, stock lookup, cart staging, and totals. Final checkout/printing still pending.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link active" href="/pos">Current Bill</a>
                <a class="nav-link" href="/cashier">Cashier Duty</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: minmax(240px, 1fr) auto;">
                    <div>
                        <div class="muted">Customer</div>
                        <strong id="selected_customer_label"><?= htmlspecialchars((string) $customer["name"], ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <form method="POST" action="/pos/reset" onsubmit="return confirm('Clear the current staged bill?');">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Clear Bill</button>
                    </form>
                </div>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Select Customer</h2>
                <div class="grid" style="grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto; align-items:end;">
                    <div class="form-row">
                        <label for="customer_name_search">Search By Name</label>
                        <input class="input" id="customer_name_search">
                    </div>
                    <div class="form-row">
                        <label for="customer_mobile_search">Search By Mobile</label>
                        <input class="input" id="customer_mobile_search">
                    </div>
                    <div>
                        <button class="btn btn-primary" type="button" onclick="searchCustomers()">Search Customer</button>
                    </div>
                </div>

                <div id="customer_results" class="stack" style="margin-top:16px;"></div>

                <form method="POST" action="/pos/customer" id="customer_form" style="display:none;">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="customer_id" id="customer_id_input" value="<?= (int) $customer["id"] ?>">
                    <input type="hidden" name="customer_name" id="customer_name_input" value="<?= htmlspecialchars((string) $customer["name"], ENT_QUOTES, "UTF-8") ?>">
                </form>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Lookup Item</h2>
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                    <div class="form-row">
                        <label for="pos_category">Category</label>
                        <select class="input" id="pos_category">
                            <option value="">Choose category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>">
                                    <?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="pos_item_name">Item Name</label>
                        <input class="input" id="pos_item_name" list="pos_item_name_list">
                        <datalist id="pos_item_name_list"></datalist>
                    </div>
                    <div class="form-row">
                        <label for="pos_item_code">IMEI / Barcode / Code</label>
                        <input class="input" id="pos_item_code">
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <button class="btn btn-primary" type="button" onclick="lookupItemByName()">Find By Name</button>
                        <button class="btn btn-primary" type="button" onclick="lookupItemByCode()">Find By Code</button>
                    </div>
                </div>

                <form method="POST" action="/pos/items" id="add_item_form" style="margin-top:18px;">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="lookup_payload" id="lookup_payload">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                        <div class="form-row">
                            <label>Selected Item</label>
                            <input class="input" id="lookup_name" readonly>
                        </div>
                        <div class="form-row">
                            <label>Available Stock</label>
                            <input class="input" id="lookup_stock" readonly>
                        </div>
                        <div class="form-row">
                            <label>Cost</label>
                            <input class="input" id="lookup_cost" readonly>
                        </div>
                        <div class="form-row">
                            <label>Default Price</label>
                            <input class="input" id="sale_price" name="sale_price" type="number" min="0" step="0.01">
                        </div>
                        <div class="form-row">
                            <label>Qty</label>
                            <input class="input" id="qty" name="qty" type="number" min="1" step="1" value="1">
                        </div>
                        <div class="form-row">
                            <label>Discount</label>
                            <input class="input" id="discount" name="discount" type="number" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label>Warranty</label>
                            <input class="input" id="warranty" name="warranty">
                        </div>
                    </div>

                    <div style="margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Add To Bill</button>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Current Bill Items</h2>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">Item Name</th>
                                <th style="padding:12px;">Code</th>
                                <th style="padding:12px;">Qty</th>
                                <th style="padding:12px;">Price</th>
                                <th style="padding:12px;">Discount</th>
                                <th style="padding:12px;">Subtotal</th>
                                <th style="padding:12px;">Warranty</th>
                                <th style="padding:12px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $index => $line): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $line["item_name"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $line["code"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;">
                                        <form method="POST" action="/pos/items/<?= (int) $index ?>" style="display:flex; gap:8px; align-items:center;">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                            <input class="input" name="qty" type="number" min="1" step="1" value="<?= (int) $line["qty"] ?>" style="min-width:80px;">
                                    </td>
                                    <td style="padding:12px;">
                                            <input class="input" name="sale_price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $line["sale_price"], ENT_QUOTES, "UTF-8") ?>" style="min-width:100px;">
                                    </td>
                                    <td style="padding:12px;">
                                            <input class="input" name="discount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $line["discount"], ENT_QUOTES, "UTF-8") ?>" style="min-width:90px;">
                                    </td>
                                    <td style="padding:12px;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;">
                                            <input class="input" name="warranty" value="<?= htmlspecialchars((string) $line["warranty"], ENT_QUOTES, "UTF-8") ?>" style="min-width:120px;">
                                    </td>
                                    <td style="padding:12px; display:flex; gap:8px;">
                                            <button class="btn btn-primary" type="submit">Update</button>
                                        </form>
                                        <form method="POST" action="/pos/items/<?= (int) $index ?>/delete" onsubmit="return confirm('Remove this item from the bill?');">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                            <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($lines === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No items added to the current bill yet.</p>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2 class="section-title">Payment Draft</h2>
                <form method="POST" action="/pos/payment">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="method">Payment Method</label>
                            <select class="input" id="method" name="method" onchange="togglePaymentFields()">
                                <option value="cash" <?= $payment["method"] === "cash" ? "selected" : "" ?>>By Cash</option>
                                <option value="card" <?= $payment["method"] === "card" ? "selected" : "" ?>>By Card</option>
                                <option value="split" <?= $payment["method"] === "split" ? "selected" : "" ?>>By Cash & Card</option>
                            </select>
                        </div>
                        <div class="form-row" id="cash_amount_row">
                            <label for="cash_amount">Cash Amount</label>
                            <input class="input" id="cash_amount" name="cash_amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $payment["cash_amount"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row" id="card_amount_row">
                            <label for="card_amount">Card Amount</label>
                            <input class="input" id="card_amount" name="card_amount" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $payment["card_amount"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row" id="card_number_row">
                            <label for="card_number">Card Number</label>
                            <input class="input" id="card_number" name="card_number" value="<?= htmlspecialchars((string) $payment["card_number"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:18px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Stage Payment Details</button>
                        <span><strong>Total:</strong> <?= htmlspecialchars($formatMoney($summary["total"] ?? 0), ENT_QUOTES, "UTF-8") ?></span>
                        <span><strong>Paid:</strong> <?= htmlspecialchars($formatMoney($summary["paid"] ?? 0), ENT_QUOTES, "UTF-8") ?></span>
                        <span><strong>Balance:</strong> <?= htmlspecialchars($formatMoney($summary["balance"] ?? 0), ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                </form>

                <p class="muted" style="margin:16px 0 0;">
                    Final checkout, stock mutation, bill-number generation, ledger writes, and printing are intentionally deferred to the next POS migration pass because the legacy flow is not transaction-safe.
                </p>
            </section>
        </main>
    </div>
</div>

<script>
const itemCategory = document.getElementById("pos_category");
const itemNameInput = document.getElementById("pos_item_name");
const itemNameList = document.getElementById("pos_item_name_list");
const itemCodeInput = document.getElementById("pos_item_code");
const lookupPayloadInput = document.getElementById("lookup_payload");

function renderLookup(item) {
    lookupPayloadInput.value = JSON.stringify(item || {});
    document.getElementById("lookup_name").value = item?.name || "";
    document.getElementById("lookup_stock").value = item?.stock_total || "";
    document.getElementById("lookup_cost").value = item?.cost_price || "";
    document.getElementById("sale_price").value = item?.sell_price || "";
    document.getElementById("qty").value = item?.type === "2" ? "1" : "1";
    document.getElementById("discount").value = "0";
    document.getElementById("warranty").value = item?.warranty || "";
}

async function loadCategoryItems(categoryName) {
    itemNameList.innerHTML = "";
    itemNameInput.value = "";
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
        itemNameList.appendChild(option);
    }
}

async function lookupItemByName() {
    const response = await fetch("/pos/lookup/name", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({item_name: itemNameInput.value}),
    });
    if (!response.ok) {
        return;
    }
    const data = await response.json();
    renderLookup(data.item || {});
}

async function lookupItemByCode() {
    const response = await fetch("/pos/lookup/code", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({item_code: itemCodeInput.value}),
    });
    if (!response.ok) {
        return;
    }
    const data = await response.json();
    renderLookup(data.item || {});
}

async function searchCustomers() {
    const response = await fetch("/api/pos/customers", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({
            js_inp_cusname: document.getElementById("customer_name_search").value,
            js_inp_cusmobi: document.getElementById("customer_mobile_search").value,
        }),
    });
    if (!response.ok) {
        return;
    }

    const data = await response.json();
    const target = document.getElementById("customer_results");
    target.innerHTML = "";

    const ids = data[0] || [];
    const names = data[1] || [];
    const mobiles = data[2] || [];

    for (let i = 0; i < names.length; i++) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "btn";
        button.style.textAlign = "left";
        button.style.background = "#eef2f5";
        button.style.color = "#163041";
        button.textContent = names[i] + (mobiles[i] ? " (" + mobiles[i] + ")" : "");
        button.onclick = function () {
            document.getElementById("customer_id_input").value = ids[i] || 0;
            document.getElementById("customer_name_input").value = names[i] || "Cash Customer";
            document.getElementById("customer_form").submit();
        };
        target.appendChild(button);
    }

    const cashButton = document.createElement("button");
    cashButton.type = "button";
    cashButton.className = "btn";
    cashButton.style.textAlign = "left";
    cashButton.style.background = "#eef2f5";
    cashButton.style.color = "#163041";
    cashButton.textContent = "Use Cash Customer";
    cashButton.onclick = function () {
        document.getElementById("customer_id_input").value = 0;
        document.getElementById("customer_name_input").value = "Cash Customer";
        document.getElementById("customer_form").submit();
    };
    target.appendChild(cashButton);
}

function togglePaymentFields() {
    const method = document.getElementById("method").value;
    document.getElementById("cash_amount_row").hidden = method === "card";
    document.getElementById("card_amount_row").hidden = method === "cash";
    document.getElementById("card_number_row").hidden = method === "cash";
}

itemCategory?.addEventListener("change", function () {
    loadCategoryItems(this.value);
});

togglePaymentFields();
</script>
