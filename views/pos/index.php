<?php
$cart = is_array($cart ?? null) ? $cart : [];
$customer = $cart["customer"] ?? ["id" => 0, "name" => "Cash Customer"];
$seller = $cart["seller"] ?? ["id" => 0, "name" => ""];
$lines = $cart["lines"] ?? [];
$payment = $cart["payment"] ?? ["method" => "cash", "cash_amount" => 0, "card_amount" => 0, "card_number" => ""];
$summary = $cart["summary"] ?? ["total" => 0, "paid" => 0, "balance" => 0];
$activeSlot = (int) ($activeSlot ?? 1);
$slotStates = is_array($slotStates ?? null) ? $slotStates : [];
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Point Of Sale</div>
            <div class="muted" style="color: #b8c6cf;">MVC POS now covers customer lookup, stock lookup, checkout, receipt reprint, and barcode-label output.</div>
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

            <h3 style="margin-top:20px;">Bill Slots</h3>
            <div class="nav-group">
                <?php foreach ($slotStates as $slotState): ?>
                    <?php
                    $slotId = (int) $slotState["slot"];
                    $open = (bool) $slotState["open"];
                    $itemCount = (int) $slotState["item_count"];
                    $label = $slotId === 1 ? "Default" : "Slot " . $slotId;
                    ?>
                    <a class="nav-link <?= $slotState["active"] ? "active" : "" ?>" href="/pos/slots/<?= $slotId ?>">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <strong><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></strong>
                            <span><?= $open ? "Open" : "Closed" ?></span>
                        </div>
                        <div class="muted" style="margin-top:6px; color:#c8d4db;">
                            <?= htmlspecialchars((string) $slotState["customer_name"], ENT_QUOTES, "UTF-8") ?>
                            · <?= $itemCount ?> item(s)
                            · <?= htmlspecialchars($formatMoney($slotState["total"]), ENT_QUOTES, "UTF-8") ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: minmax(240px, 1fr) auto;">
                    <div>
                        <div class="tag" style="margin-bottom:10px;"><?= $activeSlot === 1 ? "Default Bill Slot" : "Working In Slot " . $activeSlot ?></div>
                        <div class="muted">Customer</div>
                        <strong id="selected_customer_label"><?= htmlspecialchars((string) $customer["name"], ENT_QUOTES, "UTF-8") ?></strong>
                        <div class="muted" style="margin-top:12px;">Sale Person</div>
                        <strong id="selected_seller_label"><?= htmlspecialchars((string) ($seller["name"] ?: "Current Cashier"), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                        <form method="POST" action="/pos/slots/<?= $activeSlot ?>/clear" onsubmit="return confirm('Clear the current staged bill?');">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="mode" value="clear">
                            <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Clear Bill</button>
                        </form>
                        <?php if ($activeSlot > 1): ?>
                            <form method="POST" action="/pos/slots/<?= $activeSlot ?>/clear" onsubmit="return confirm('Close this extra bill slot?');">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="mode" value="close">
                                <button class="btn" type="submit" style="background:#1f3140; color:#fff;">Close Slot</button>
                            </form>
                        <?php endif; ?>
                    </div>
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
                <h2 class="section-title">Sale Person</h2>
                <div class="grid" style="grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr) auto; align-items:end;">
                    <div class="form-row">
                        <label for="seller_id_search">Sale Person ID</label>
                        <input class="input" id="seller_id_search" value="<?= (int) ($seller["id"] ?? 0) ?>">
                    </div>
                    <div class="form-row">
                        <label for="seller_name_preview">Matched User</label>
                        <input class="input" id="seller_name_preview" value="<?= htmlspecialchars((string) ($seller["name"] ?? ""), ENT_QUOTES, "UTF-8") ?>" readonly>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <button class="btn btn-primary" type="button" onclick="lookupSalesPerson()">Check ID</button>
                        <button class="btn" type="button" onclick="useCurrentCashier()" style="background:#eef2f5; color:#163041;">Use Current Cashier</button>
                    </div>
                </div>

                <form method="POST" action="/pos/seller" id="seller_form" style="display:none;">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="seller_id" id="seller_id_input" value="<?= (int) ($seller["id"] ?? 0) ?>">
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

                <p class="muted" style="margin:16px 0 0;">This draft writes committed bills transactionally and hands off to the new MVC receipt/print flow.</p>

                <form method="POST" action="/pos/checkout" style="margin-top:16px;">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <button class="btn btn-primary" type="submit" <?= $lines === [] ? "disabled" : "" ?>>Finish Bill</button>
                </form>
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
const sellerIdInput = document.getElementById("seller_id_search");

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

async function lookupSalesPerson(autoSubmit = true) {
    const sellerId = (sellerIdInput?.value || "").trim();
    if (!sellerId) {
        return;
    }

    const response = await fetch("/api/pos/salespeople/" + encodeURIComponent(sellerId));
    if (!response.ok) {
        return;
    }

    const data = await response.json();
    const preview = document.getElementById("seller_name_preview");
    const hiddenId = document.getElementById("seller_id_input");

    if (!data.found || !data.seller) {
        preview.value = "Not Found";
        hiddenId.value = "";
        sellerIdInput.focus();
        return;
    }

    preview.value = data.seller.name || data.seller.username || "";
    hiddenId.value = data.seller.id || "";

    if (autoSubmit) {
        document.getElementById("seller_form").submit();
    }
}

function useCurrentCashier() {
    document.getElementById("seller_id_input").value = "";
    document.getElementById("seller_form").submit();
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

sellerIdInput?.addEventListener("change", function () {
    lookupSalesPerson();
});

document.addEventListener("keydown", function (event) {
    if (event.key === "F4") {
        event.preventDefault();
        document.getElementById("pos_category")?.focus();
    }

    if (event.key === "F8") {
        event.preventDefault();
        document.getElementById("pos_item_code")?.focus();
    }

    if (event.key === "F2") {
        event.preventDefault();
        document.getElementById("cash_amount")?.focus();
    }
});

document.getElementById("cash_amount")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter" && <?= $customer["id"] > 0 ? "true" : "false" ?>) {
        event.preventDefault();
        document.querySelector('form[action="/pos/checkout"] button[type="submit"]')?.click();
    }
});

togglePaymentFields();
</script>
