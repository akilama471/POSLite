<?php
$draft = is_array($draft ?? null) ? $draft : [];
$lines = is_array($draft["lines"] ?? null) ? $draft["lines"] : [];
$suppliers = is_array($suppliers ?? null) ? $suppliers : [];
$shops = is_array($shops ?? null) ? $shops : [];
$categories = is_array($categories ?? null) ? $categories : [];
$subTotal = array_reduce($lines, static function (float $carry, array $line): float {
    return $carry + (float) ($line["sub_total"] ?? 0);
}, 0.0);
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add GRN</div>
            <div class="muted" style="color: #b8c6cf;">First MVC GRN write-side slice with cashier-gated draft staging and transaction-safe submit.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>Purchases</h3>
            <div class="nav-group">
                <a class="nav-link active" href="/grns/create">Add GRN</a>
                <?php if (can("p_45")): ?>
                    <a class="nav-link" href="/grns">Find GRN</a>
                <?php endif; ?>
                <?php if (can("p_29")): ?>
                    <a class="nav-link" href="/grn-payments">GRN Payments</a>
                <?php endif; ?>
                <a class="nav-link" href="/cashier">Cashier Duty</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <form method="POST" action="/grns/draft/header">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="supplier_id">Supplier</label>
                            <select class="input" id="supplier_id" name="supplier_id" required>
                                <option value="">Select supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int) ($supplier["supplierid"] ?? 0) ?>" <?= (int) ($draft["supplier_id"] ?? 0) === (int) ($supplier["supplierid"] ?? 0) ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) ($supplier["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="invoice_number">Invoice Number</label>
                            <input class="input" id="invoice_number" name="invoice_number" value="<?= htmlspecialchars((string) ($draft["invoice_number"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Save Header</button>
                    </div>
                </form>

                <?php if ((int) ($draft["supplier_id"] ?? 0) > 0): ?>
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top:18px;">
                        <div>
                            <div class="muted">Supplier Name</div>
                            <strong><?= htmlspecialchars((string) ($draft["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Supplier Address</div>
                            <strong><?= htmlspecialchars((string) ($draft["supplier_address"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Supplier Mobile</div>
                            <strong><?= htmlspecialchars((string) ($draft["supplier_mobile"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <h2 class="section-title">Add Line</h2>
                <form method="POST" action="/grns/draft/lines">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="category_name">Category</label>
                            <select class="input" id="category_name" name="category_name" onchange="loadGrnCategoryItems(this.value)">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars((string) ($category["catname"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                                        <?= htmlspecialchars((string) ($category["catname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="item_name">Item Name</label>
                            <input class="input" id="item_name" name="item_name" list="grn_items" onchange="loadGrnItemDetails(this.value)" required>
                            <datalist id="grn_items"></datalist>
                        </div>
                        <div class="form-row">
                            <label for="item_type_label">Type</label>
                            <input class="input" id="item_type_label" readonly>
                        </div>
                        <div class="form-row">
                            <label for="imei_no">IMEI</label>
                            <input class="input" id="imei_no" name="imei_no">
                        </div>
                        <div class="form-row">
                            <label for="item_color">Color</label>
                            <input class="input" id="item_color" name="item_color">
                        </div>
                        <div class="form-row">
                            <label for="qty">Qty</label>
                            <input class="input" type="number" id="qty" name="qty" min="1" step="1" value="1" required>
                        </div>
                        <div class="form-row">
                            <label for="cost_price">Cost Price</label>
                            <input class="input" type="number" id="cost_price" name="cost_price" min="0" step="0.01" required>
                        </div>
                        <div class="form-row">
                            <label for="sell_price">Sell Price</label>
                            <input class="input" type="number" id="sell_price" name="sell_price" min="0" step="0.01" required>
                        </div>
                        <div class="form-row">
                            <label for="low_price">Low Price</label>
                            <input class="input" type="number" id="low_price" name="low_price" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="other_price">Other Price</label>
                            <input class="input" type="number" id="other_price" name="other_price" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="stock_shop_id">Stock Shop</label>
                            <select class="input" id="stock_shop_id" name="stock_shop_id" <?= (int) (($auth["shop_id"] ?? 0)) > 0 ? "disabled" : "" ?>>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int) ($shop["shopid"] ?? 0) ?>">
                                        <?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int) (($auth["shop_id"] ?? 0)) > 0): ?>
                                <input type="hidden" name="stock_shop_id" value="<?= (int) ($auth["shop_id"] ?? 0) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label for="warranty_span">Warranty Span</label>
                            <input class="input" type="number" id="warranty_span" name="warranty_span" min="0" step="1" value="0">
                        </div>
                        <div class="form-row">
                            <label for="warranty_type">Warranty Type</label>
                            <input class="input" id="warranty_type" name="warranty_type" placeholder="Days / Months / Years">
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:12px;">
                        <label><input type="checkbox" name="item_free" value="1"> Add as free item</label>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Add Line</button>
                        <span class="muted">Bulk IMEI staging is deferred to the next GRN slice.</span>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h2 class="section-title" style="margin:0;">Draft Lines</h2>
                    <form method="POST" action="/grns/draft/clear" onsubmit="return confirm('Clear the current GRN draft?');">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Clear Draft</button>
                    </form>
                </div>

                <?php if ($lines === []): ?>
                    <p class="muted" style="margin:0;">No GRN lines staged yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                    <th style="padding:12px;">Item</th>
                                    <th style="padding:12px;">Type</th>
                                    <th style="padding:12px;">IMEI</th>
                                    <th style="padding:12px;">Qty</th>
                                    <th style="padding:12px;">Cost</th>
                                    <th style="padding:12px;">Sell</th>
                                    <th style="padding:12px;">Shop</th>
                                    <th style="padding:12px;">Subtotal</th>
                                    <th style="padding:12px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lines as $index => $line): ?>
                                    <tr style="border-bottom:1px solid #edf1f4;">
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($line["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;"><?= match ((int) ($line["object_type"] ?? 0)) { 1 => "Barcode", 2 => "IMEI", 3 => "Recharge", default => "Unknown" } ?></td>
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($line["imei_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px; text-align:center;"><?= (int) ($line["item_qty"] ?? 0) ?></td>
                                        <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["item_costpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["item_sellpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($line["stock_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;">
                                            <form method="POST" action="/grns/draft/lines/<?= $index ?>/delete" onsubmit="return confirm('Remove this draft line?');">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <button class="btn" type="submit" style="background:#eef2f5; color:#163041;">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2 class="section-title">Submit GRN</h2>
                <form method="POST" action="/grns/submit" id="grn-submit-form">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="discount_amount">Discount Amount</label>
                            <input class="input" type="number" id="discount_amount" name="discount_amount" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="discount_percent">Discount Percent</label>
                            <input class="input" type="number" id="discount_percent" name="discount_percent" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="cash_amount">Cash Pay Amount</label>
                            <input class="input" type="number" id="cash_amount" name="cash_amount" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="cheque_amount">Cheque Pay Amount</label>
                            <input class="input" type="number" id="cheque_amount" name="cheque_amount" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-row">
                            <label for="cheque_number">Cheque Number</label>
                            <input class="input" id="cheque_number" name="cheque_number">
                        </div>
                        <div class="form-row">
                            <label for="cheque_date">Cheque Date</label>
                            <input class="input" type="date" id="cheque_date" name="cheque_date">
                        </div>
                        <div class="form-row">
                            <label for="cheque_reminder">Cheque Reminder</label>
                            <select class="input" id="cheque_reminder" name="cheque_reminder">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="cheque_reminder_date">Reminder Date</label>
                            <input class="input" type="date" id="cheque_reminder_date" name="cheque_reminder_date">
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top:18px;">
                        <div>
                            <div class="muted">Sub Total</div>
                            <strong id="grn_subtotal_text"><?= htmlspecialchars($formatMoney($subTotal), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Final GRN Value</div>
                            <strong id="grn_total_text"><?= htmlspecialchars($formatMoney($subTotal), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div>
                            <div class="muted">Remain To Pay</div>
                            <strong id="grn_due_text"><?= htmlspecialchars($formatMoney($subTotal), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                        <button class="btn btn-primary" type="submit">Submit GRN</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
const grnSubTotal = <?= json_encode($subTotal) ?>;

function loadGrnMath() {
    const discountAmount = Number(document.getElementById("discount_amount").value || 0);
    const discountPercent = Number(document.getElementById("discount_percent").value || 0);
    const cashAmount = Number(document.getElementById("cash_amount").value || 0);
    const chequeAmount = Number(document.getElementById("cheque_amount").value || 0);

    let appliedDiscount = discountAmount;
    if (discountPercent > 0 && discountAmount === 0) {
        appliedDiscount = grnSubTotal * (discountPercent / 100);
    }

    const finalTotal = Math.max(0, grnSubTotal - appliedDiscount);
    const due = Math.max(0, finalTotal - cashAmount - chequeAmount);

    document.getElementById("grn_total_text").textContent = "Rs. " + finalTotal.toFixed(2);
    document.getElementById("grn_due_text").textContent = "Rs. " + due.toFixed(2);
}

async function loadGrnCategoryItems(categoryName) {
    const dataList = document.getElementById("grn_items");
    dataList.innerHTML = "";
    document.getElementById("item_name").value = "";

    if (!categoryName) {
        return;
    }

    const response = await fetch("/api/items/by-category?category=" + encodeURIComponent(categoryName), {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });

    if (!response.ok) {
        return;
    }

    const items = await response.json();
    for (const itemName of items) {
        const option = document.createElement("option");
        option.value = itemName;
        dataList.appendChild(option);
    }
}

async function loadGrnItemDetails(itemName) {
    if (!itemName) {
        return;
    }

    const response = await fetch("/api/grns/items/details?name=" + encodeURIComponent(itemName), {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });

    if (!response.ok) {
        return;
    }

    const data = await response.json();
    if (!data.found) {
        return;
    }

    if (data.category_name) {
        document.getElementById("category_name").value = data.category_name;
    }

    document.getElementById("cost_price").value = data.cost_price || 0;
    document.getElementById("sell_price").value = data.sell_price || 0;
    document.getElementById("low_price").value = data.low_price || 0;
    document.getElementById("other_price").value = data.other_price || 0;

    const typeLabel = data.used_type === 1 ? "Barcode" : (data.used_type === 2 ? "IMEI" : "Recharge");
    document.getElementById("item_type_label").value = typeLabel;

    const qtyInput = document.getElementById("qty");
    const imeiInput = document.getElementById("imei_no");
    if (data.used_type === 2) {
        qtyInput.value = "1";
        qtyInput.readOnly = true;
        imeiInput.required = true;
    } else {
        qtyInput.readOnly = false;
        imeiInput.required = false;
        imeiInput.value = "";
    }
}

["discount_amount", "discount_percent", "cash_amount", "cheque_amount"].forEach(function (id) {
    document.getElementById(id).addEventListener("input", loadGrnMath);
});

document.getElementById("grn-submit-form").addEventListener("submit", function (event) {
    const discountAmount = Number(document.getElementById("discount_amount").value || 0);
    const discountPercent = Number(document.getElementById("discount_percent").value || 0);
    if (discountAmount > 0 && discountPercent > 0) {
        event.preventDefault();
        alert("Use either discount amount or discount percent, not both.");
    }
});

loadGrnMath();
</script>
