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
                <?php if (can("p_31")): ?>
                    <a class="nav-link" href="/pos/bills/today">Daily Bills</a>
                <?php endif; ?>
                <?php if (can("p_32")): ?>
                    <a class="nav-link" href="/pos/bills/search">Find Bill</a>
                <?php endif; ?>
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
                            | <?= $itemCount ?> item(s)
                            | <?= htmlspecialchars($formatMoney($slotState["total"]), ENT_QUOTES, "UTF-8") ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div id="pos_status" class="alert" hidden style="margin-bottom:18px;"></div>

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

                <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-top:12px;">
                    <button class="btn" type="button" onclick="useCashCustomerQuick()" style="background:#eef2f5; color:#163041;">Use Cash Customer</button>
                    <?php if (can("p_36")): ?>
                        <a class="btn" href="/customers/create" target="_blank" rel="noopener" style="background:#eef2f5; color:#163041; text-decoration:none;">Add New Customer</a>
                    <?php endif; ?>
                    <?php if (can("p_37")): ?>
                        <a class="btn" href="/customers" target="_blank" rel="noopener" style="background:#eef2f5; color:#163041; text-decoration:none;">Manage Customers</a>
                    <?php endif; ?>
                </div>

                <div class="muted" id="customer_search_hint" style="margin-top:12px;">Search by customer name or mobile, then select the matched record.</div>
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
                <div class="muted" style="margin-top:12px;">Press `F7` to jump here. Enter `0` to reset the seller back to the current cashier.</div>

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

                    <div class="muted" id="lookup_hint" style="margin-top:12px;">Select an item by name or code, then confirm quantity and price before adding it to the bill.</div>

                    <div style="margin-top:16px;">
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <button class="btn btn-primary" type="submit">Add To Bill</button>
                            <button class="btn" id="bulk_imei_button" type="button" onclick="openBulkImeiModal()" style="background:#eef2f5; color:#163041;" hidden>Add Bulk IMEIs</button>
                        </div>
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
                                    <td style="padding:12px;"><?= (int) $line["qty"] ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sale_price"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["discount"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $line["warranty"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; display:flex; gap:8px;">
                                        <button
                                            class="btn btn-primary"
                                            type="button"
                                            onclick='openEditModal(<?= json_encode([
                                                "index" => (int) $index,
                                                "item_name" => (string) $line["item_name"],
                                                "code" => (string) $line["code"],
                                                "qty" => (int) $line["qty"],
                                                "sale_price" => (string) $line["sale_price"],
                                                "discount" => (string) $line["discount"],
                                                "warranty" => (string) $line["warranty"],
                                                "type" => (string) $line["type"],
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'
                                        >
                                            Edit
                                        </button>
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
                <form method="POST" action="/pos/payment" id="payment_draft_form">
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

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:16px;">
                        <div class="card" style="margin:0; padding:14px 16px; background:#f7fafc;">
                            <div class="muted">Need To Pay</div>
                            <strong id="payment_total_live"><?= htmlspecialchars($formatMoney($summary["total"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div class="card" style="margin:0; padding:14px 16px; background:#f7fafc;">
                            <div class="muted">Paid Amount</div>
                            <strong id="payment_paid_live"><?= htmlspecialchars($formatMoney($summary["paid"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                        <div class="card" style="margin:0; padding:14px 16px; background:#f7fafc;">
                            <div class="muted" id="payment_balance_label"><?= (int) ($customer["id"] ?? 0) > 0 ? "Balance / Credit" : "Change / Due" ?></div>
                            <strong id="payment_balance_live"><?= htmlspecialchars($formatMoney($summary["balance"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                        </div>
                    </div>

                    <div style="display:flex; gap:18px; flex-wrap:wrap; align-items:center; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Stage Payment Details</button>
                        <button class="btn" type="button" id="exact_cash_button" style="background:#eef2f5; color:#163041;">Exact Cash</button>
                        <span class="muted" id="payment_flow_hint">Live payment totals update as the cashier types. Finish Bill now uses the current draft values directly.</span>
                    </div>
                </form>

                <p class="muted" style="margin:16px 0 0;">This draft writes committed bills transactionally and hands off to the new MVC receipt/print flow.</p>

                <form method="POST" action="/pos/checkout" style="margin-top:16px;">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="method" id="checkout_method" value="<?= htmlspecialchars((string) ($payment["method"] ?? "cash"), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="cash_amount" id="checkout_cash_amount" value="<?= htmlspecialchars((string) ($payment["cash_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="card_amount" id="checkout_card_amount" value="<?= htmlspecialchars((string) ($payment["card_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="card_number" id="checkout_card_number" value="<?= htmlspecialchars((string) ($payment["card_number"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                    <button class="btn btn-primary" id="finish_bill_button" type="submit" <?= $lines === [] ? "disabled" : "" ?>>Finish Bill</button>
                </form>
            </section>

            <div id="line_edit_modal" hidden style="position:fixed; inset:0; background:rgba(10, 16, 22, 0.58); display:grid; place-items:center; padding:24px; z-index:1000;">
                <div class="card" style="width:min(560px, 100%); max-height:90vh; overflow:auto;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:start; margin-bottom:18px;">
                        <div>
                            <h2 class="section-title" style="margin-bottom:4px;">Edit Current Line</h2>
                            <div class="muted" id="line_edit_item_name"></div>
                            <div class="muted" id="line_edit_item_code"></div>
                        </div>
                        <button class="btn" type="button" onclick="closeEditModal()" style="background:#eef2f5; color:#163041;">Close</button>
                    </div>

                    <form id="line_edit_form" method="POST" action="/pos/items/0">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                            <div class="form-row">
                                <label for="line_edit_qty">Qty</label>
                                <input class="input" id="line_edit_qty" name="qty" type="number" min="1" step="1">
                            </div>
                            <div class="form-row">
                                <label for="line_edit_sale_price">Sale Price</label>
                                <input class="input" id="line_edit_sale_price" name="sale_price" type="number" min="0" step="0.01">
                            </div>
                            <div class="form-row">
                                <label for="line_edit_discount">Discount</label>
                                <input class="input" id="line_edit_discount" name="discount" type="number" min="0" step="0.01">
                            </div>
                            <div class="form-row">
                                <label for="line_edit_warranty">Warranty</label>
                                <input class="input" id="line_edit_warranty" name="warranty">
                            </div>
                        </div>

                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                            <button class="btn btn-primary" type="submit">Update Bill Line</button>
                            <button class="btn" type="button" onclick="closeEditModal()" style="background:#fbe4de; color:#8f2d15;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="bulk_imei_modal" hidden style="position:fixed; inset:0; background:rgba(10, 16, 22, 0.58); display:grid; place-items:center; padding:24px; z-index:1000;">
                <div class="card" style="width:min(700px, 100%); max-height:90vh; overflow:auto;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:start; margin-bottom:18px;">
                        <div>
                            <h2 class="section-title" style="margin-bottom:4px;">Bulk IMEI Input</h2>
                            <div class="muted">Paste IMEIs one per line, or as a continuous 15-digit stream. Count must match the selected quantity.</div>
                        </div>
                        <button class="btn" type="button" onclick="closeBulkImeiModal()" style="background:#eef2f5; color:#163041;">Close</button>
                    </div>

                    <form method="POST" action="/pos/items/imei-bulk">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="lookup_payload" id="bulk_lookup_payload">
                        <input type="hidden" name="qty" id="bulk_qty">
                        <input type="hidden" name="sale_price" id="bulk_sale_price">
                        <input type="hidden" name="discount" id="bulk_discount">
                        <input type="hidden" name="warranty" id="bulk_warranty">

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:16px;">
                            <div>
                                <div class="muted">Selected Item</div>
                                <strong id="bulk_item_name">-</strong>
                            </div>
                            <div>
                                <div class="muted">Requested Qty</div>
                                <strong id="bulk_item_qty">0</strong>
                            </div>
                            <div>
                                <div class="muted">Current Price</div>
                                <strong id="bulk_item_price">Rs. 0.00</strong>
                            </div>
                        </div>

                        <div class="form-row">
                            <label for="imei_bulk_input">IMEI Numbers</label>
                            <textarea class="input" id="imei_bulk_input" name="imei_bulk_input" rows="10" style="resize:vertical;"></textarea>
                        </div>

                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                            <button class="btn btn-primary" type="submit">Add Bulk IMEIs To Bill</button>
                            <button class="btn" type="button" onclick="closeBulkImeiModal()" style="background:#fbe4de; color:#8f2d15;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
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
const editModal = document.getElementById("line_edit_modal");
const bulkImeiModal = document.getElementById("bulk_imei_modal");
const addItemForm = document.getElementById("add_item_form");
const posStatus = document.getElementById("pos_status");
const paymentDraftForm = document.getElementById("payment_draft_form");
const checkoutForm = document.querySelector('form[action="/pos/checkout"]');
const totalDue = <?= json_encode((float) ($summary["total"] ?? 0)) ?>;
const hasRegisteredCustomer = <?= (int) ($customer["id"] ?? 0) > 0 ? "true" : "false" ?>;
let customerResultButtons = [];

function formatMoney(value) {
    return "Rs. " + Number(value || 0).toFixed(2);
}

function paymentMethodValue() {
    return document.getElementById("method")?.value || "cash";
}

function paymentAmounts() {
    return {
        method: paymentMethodValue(),
        cash: Number(document.getElementById("cash_amount")?.value || 0),
        card: Number(document.getElementById("card_amount")?.value || 0),
        cardNumber: (document.getElementById("card_number")?.value || "").trim(),
    };
}

function setPosStatus(message, type = "info") {
    if (!posStatus) {
        return;
    }

    posStatus.hidden = false;
    posStatus.textContent = message;
    posStatus.className = "alert";

    if (type === "error") {
        posStatus.classList.add("alert-error");
        posStatus.style.background = "";
        posStatus.style.color = "";
        posStatus.style.border = "";
        return;
    }

    if (type === "success") {
        posStatus.style.background = "#e8f6ef";
        posStatus.style.color = "#146b4f";
        posStatus.style.border = "1px solid #bde4d0";
        return;
    }

    posStatus.style.background = "#eef5fb";
    posStatus.style.color = "#17415c";
    posStatus.style.border = "1px solid #c9deee";
}

function clearPosStatus() {
    if (!posStatus) {
        return;
    }

    posStatus.hidden = true;
    posStatus.textContent = "";
}

function selectCustomerResult(customerId, customerName) {
    document.getElementById("customer_id_input").value = customerId || 0;
    document.getElementById("customer_name_input").value = customerName || "Cash Customer";
    document.getElementById("customer_form").submit();
}

function customerSelectionContextActive() {
    const active = document.activeElement;
    const customerResults = document.getElementById("customer_results");

    if (!active) {
        return false;
    }

    return active.id === "customer_name_search"
        || active.id === "customer_mobile_search"
        || (customerResults ? customerResults.contains(active) : false);
}

function syncCheckoutPaymentInputs() {
    const payment = paymentAmounts();
    document.getElementById("checkout_method").value = payment.method;
    document.getElementById("checkout_cash_amount").value = String(payment.cash);
    document.getElementById("checkout_card_amount").value = String(payment.card);
    document.getElementById("checkout_card_number").value = payment.cardNumber;
}

function refreshPaymentDraftSummary() {
    const payment = paymentAmounts();
    const paid = payment.cash + payment.card;
    const balance = paid - totalDue;
    const label = hasRegisteredCustomer ? "Balance / Credit" : "Change / Due";
    const flowHint = document.getElementById("payment_flow_hint");

    document.getElementById("payment_total_live").textContent = formatMoney(totalDue);
    document.getElementById("payment_paid_live").textContent = formatMoney(paid);
    document.getElementById("payment_balance_label").textContent = label;
    document.getElementById("payment_balance_live").textContent = formatMoney(balance);

    if (!flowHint) {
        syncCheckoutPaymentInputs();
        return;
    }

    if (!hasRegisteredCustomer && paid < totalDue) {
        flowHint.textContent = "Cash customer bills must be fully paid before Finish Bill can proceed.";
    } else if (payment.method !== "cash" && payment.cardNumber === "") {
        flowHint.textContent = "Card and split payments need a card number before checkout.";
    } else {
        flowHint.textContent = "Live payment totals update as the cashier types. Finish Bill now uses the current draft values directly.";
    }

    syncCheckoutPaymentInputs();
}

function validateCheckoutDraft(showStatus = true) {
    const payment = paymentAmounts();
    const paid = payment.cash + payment.card;
    const balance = paid - totalDue;

    if (payment.method === "cash" && payment.cash <= 0) {
        if (showStatus) {
            setPosStatus("Cash amount is required before finishing the bill.", "error");
            document.getElementById("cash_amount")?.focus();
        }
        return false;
    }

    if (payment.method === "card" && (payment.card <= 0 || payment.cardNumber === "")) {
        if (showStatus) {
            setPosStatus("Card amount and card number are required before checkout.", "error");
            if (payment.card <= 0) {
                document.getElementById("card_amount")?.focus();
            } else {
                document.getElementById("card_number")?.focus();
            }
        }
        return false;
    }

    if (payment.method === "split" && (paid <= 0 || payment.cardNumber === "")) {
        if (showStatus) {
            setPosStatus("Split payment needs payment values and a card number before checkout.", "error");
            if (paid <= 0) {
                document.getElementById("cash_amount")?.focus();
            } else {
                document.getElementById("card_number")?.focus();
            }
        }
        return false;
    }

    if (!hasRegisteredCustomer && balance < 0) {
        if (showStatus) {
            setPosStatus("Cash customer bills need full payment before checkout.", "error");
            document.getElementById("cash_amount")?.focus();
        }
        return false;
    }

    if (showStatus) {
        if (hasRegisteredCustomer && balance < 0) {
            setPosStatus("Registered customer bill is ready. Remaining balance will stay on account.", "info");
        } else if (balance > 0) {
            setPosStatus("Payment exceeds the bill total. Change is ready to return.", "success");
        } else {
            setPosStatus("Payment draft is ready for checkout.", "success");
        }
    }

    return true;
}

function renderLookup(item) {
    lookupPayloadInput.value = JSON.stringify(item || {});
    document.getElementById("lookup_name").value = item?.name || "";
    document.getElementById("lookup_stock").value = item?.stock_total || "";
    document.getElementById("lookup_cost").value = item?.cost_price || "";
    document.getElementById("sale_price").value = item?.sell_price || "";
    document.getElementById("qty").value = item?.type === "2" ? "1" : "1";
    document.getElementById("discount").value = "0";
    document.getElementById("warranty").value = item?.warranty || "";
    document.getElementById("lookup_hint").textContent = item?.item_id
        ? "Selected stock is ready. Press Enter from qty or sale price to continue quickly."
        : (item?.name || "No stock match found for the current lookup.");
    if (item?.item_id) {
        setPosStatus("Stock item selected: " + (item.name || "Item") + ".", "success");
    } else if (item?.name) {
        setPosStatus(item.name, "error");
    }
    syncBulkImeiButton(item);
}

function clearLookupDraft(keepCode = false) {
    lookupPayloadInput.value = "{}";
    document.getElementById("lookup_name").value = "";
    document.getElementById("lookup_stock").value = "";
    document.getElementById("lookup_cost").value = "";
    document.getElementById("sale_price").value = "";
    document.getElementById("qty").value = "1";
    document.getElementById("discount").value = "0";
    document.getElementById("warranty").value = "";
    document.getElementById("lookup_hint").textContent = "Select an item by name or code, then confirm quantity and price before adding it to the bill.";
    if (!keepCode) {
        itemCodeInput.value = "";
    }
    syncBulkImeiButton();
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

    if (items.length > 0) {
        window.setTimeout(function () {
            itemNameInput?.focus();
        }, 0);
    }
}

async function lookupItemByName() {
    clearPosStatus();
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
    if (data.found) {
        document.getElementById("qty")?.focus();
        document.getElementById("qty")?.select();
    } else {
        itemNameInput.focus();
    }
}

async function lookupItemByCode() {
    clearPosStatus();
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
    if (data.found) {
        document.getElementById("sale_price")?.focus();
        document.getElementById("sale_price")?.select();
    } else {
        itemCodeInput.select();
    }
}

async function searchCustomers() {
    clearPosStatus();
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
    customerResultButtons = [];
    document.getElementById("customer_search_hint").textContent = "Select a matched customer or keep the bill under Cash Customer.";

    const ids = data[0] || [];
    const names = data[1] || [];
    const mobiles = data[2] || [];

    if (names.length === 0) {
        setPosStatus("No customer matched the current search.", "error");
        return;
    }

    setPosStatus("Customer matches loaded. Use Enter on a result or number keys 1-9 for the first matches.", "info");

    for (let i = 0; i < names.length; i++) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "btn";
        button.style.textAlign = "left";
        button.style.background = "#eef2f5";
        button.style.color = "#163041";
        button.textContent = (i < 9 ? "[" + (i + 1) + "] " : "") + names[i] + (mobiles[i] ? " (" + mobiles[i] + ")" : "");
        button.dataset.customerIndex = String(i + 1);
        button.onclick = function () {
            selectCustomerResult(ids[i] || 0, names[i] || "Cash Customer");
        };
        target.appendChild(button);
        customerResultButtons.push(button);
    }

    const cashButton = document.createElement("button");
    cashButton.type = "button";
    cashButton.className = "btn";
    cashButton.style.textAlign = "left";
    cashButton.style.background = "#eef2f5";
    cashButton.style.color = "#163041";
    cashButton.textContent = "Use Cash Customer";
    cashButton.onclick = function () {
        selectCustomerResult(0, "Cash Customer");
    };
    target.appendChild(cashButton);
    customerResultButtons.push(cashButton);

    if (customerResultButtons[0]) {
        window.setTimeout(function () {
            customerResultButtons[0]?.focus();
        }, 0);
    }
}

function useCashCustomerQuick() {
    setPosStatus("Bill was reset to Cash Customer.", "info");
    selectCustomerResult(0, "Cash Customer");
}

async function lookupSalesPerson(autoSubmit = true) {
    const sellerId = (sellerIdInput?.value || "").trim();
    if (!sellerId) {
        useCurrentCashier();
        return;
    }

    if (Number(sellerId) === 0) {
        useCurrentCashier();
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
        setPosStatus("Sale person ID was not matched to an active user.", "error");
        sellerIdInput.focus();
        return;
    }

    preview.value = data.seller.name || data.seller.username || "";
    hiddenId.value = data.seller.id || "";
    setPosStatus("Sale person matched: " + preview.value + ".", "success");

    if (autoSubmit) {
        document.getElementById("seller_form").submit();
    }
}

function useCurrentCashier() {
    if (sellerIdInput) {
        sellerIdInput.value = "0";
    }
    document.getElementById("seller_name_preview").value = "Current Cashier";
    document.getElementById("seller_id_input").value = "";
    setPosStatus("Sale person reset to the current cashier.", "info");
    document.getElementById("seller_form").submit();
}

function openEditModal(line) {
    document.getElementById("line_edit_form").action = "/pos/items/" + line.index;
    document.getElementById("line_edit_item_name").textContent = line.item_name || "";
    document.getElementById("line_edit_item_code").textContent = line.code || "";
    document.getElementById("line_edit_qty").value = line.qty ?? 1;
    document.getElementById("line_edit_sale_price").value = line.sale_price ?? "0.00";
    document.getElementById("line_edit_discount").value = line.discount ?? "0.00";
    document.getElementById("line_edit_warranty").value = line.warranty || "";
    document.getElementById("line_edit_qty").readOnly = String(line.type) === "2";
    editModal.hidden = false;
    document.body.style.overflow = "hidden";
    window.setTimeout(function () {
        document.getElementById("line_edit_sale_price")?.focus();
    }, 0);
}

function closeEditModal() {
    editModal.hidden = true;
    document.body.style.overflow = "";
}

function syncBulkImeiButton(item = null) {
    const button = document.getElementById("bulk_imei_button");
    const selectedItem = item || JSON.parse(lookupPayloadInput.value || "{}");
    const qty = Number(document.getElementById("qty")?.value || 0);
    button.hidden = !(selectedItem?.type === "2" && qty > 1);
}

function validateDraftBeforeAdd() {
    const item = JSON.parse(lookupPayloadInput.value || "{}");
    const qty = Number(document.getElementById("qty")?.value || 0);
    const stock = Number(item?.stock_total || 0);
    const salePrice = Number(document.getElementById("sale_price")?.value || 0);
    const discount = Number(document.getElementById("discount")?.value || 0);
    const cost = Number(item?.cost_price || 0);

    if (!item?.item_id) {
        setPosStatus("Select a valid stock item before adding it to the bill.", "error");
        if (itemCodeInput.value.trim() !== "") {
            itemCodeInput.focus();
        } else {
            itemNameInput.focus();
        }
        return false;
    }

    if (qty < 1) {
        setPosStatus("Selling quantity is required.", "error");
        document.getElementById("qty")?.focus();
        return false;
    }

    if (String(item.type) === "2" && qty > 1) {
        openBulkImeiModal();
        return false;
    }

    if (stock > 0 && qty > stock) {
        setPosStatus("You cannot add more than current stock count.", "error");
        document.getElementById("qty")?.focus();
        return false;
    }

    if (salePrice <= 0) {
        setPosStatus("Sale price is required.", "error");
        document.getElementById("sale_price")?.focus();
        return false;
    }

    if (discount < 0) {
        setPosStatus("Discount cannot be negative.", "error");
        document.getElementById("discount")?.focus();
        return false;
    }

    if (salePrice < cost) {
        setPosStatus("You are trying to sell this item under cost price. Press Enter again on sale price to continue.", "info");
        const alreadyWarned = document.getElementById("sale_price")?.dataset.underCostConfirmed === "1";
        document.getElementById("sale_price").dataset.underCostConfirmed = alreadyWarned ? "1" : "0";
        if (!alreadyWarned) {
            document.getElementById("sale_price").dataset.underCostConfirmed = "1";
            document.getElementById("sale_price")?.focus();
            return false;
        }
    }

    document.getElementById("sale_price").dataset.underCostConfirmed = "";
    setPosStatus("Item is ready to stage into the bill.", "success");
    return true;
}

function openBulkImeiModal() {
    const item = JSON.parse(lookupPayloadInput.value || "{}");
    const qty = Number(document.getElementById("qty")?.value || 0);

    if (!item?.item_id || item?.type !== "2" || qty < 2) {
        return;
    }

    document.getElementById("bulk_lookup_payload").value = lookupPayloadInput.value;
    document.getElementById("bulk_qty").value = qty;
    document.getElementById("bulk_sale_price").value = document.getElementById("sale_price")?.value || "";
    document.getElementById("bulk_discount").value = document.getElementById("discount")?.value || "0";
    document.getElementById("bulk_warranty").value = document.getElementById("warranty")?.value || "";
    document.getElementById("bulk_item_name").textContent = item.name || "";
    document.getElementById("bulk_item_qty").textContent = String(qty);
    document.getElementById("bulk_item_price").textContent = "Rs. " + Number(document.getElementById("sale_price")?.value || 0).toFixed(2);
    document.getElementById("imei_bulk_input").value = "";
    bulkImeiModal.hidden = false;
    document.body.style.overflow = "hidden";
    setPosStatus("Bulk IMEI input opened for the selected item.", "info");
    window.setTimeout(function () {
        document.getElementById("imei_bulk_input")?.focus();
    }, 0);
}

function closeBulkImeiModal() {
    bulkImeiModal.hidden = true;
    document.body.style.overflow = "";
}

function togglePaymentFields() {
    const method = document.getElementById("method").value;
    document.getElementById("cash_amount_row").hidden = method === "card";
    document.getElementById("card_amount_row").hidden = method === "cash";
    document.getElementById("card_number_row").hidden = method === "cash";
    refreshPaymentDraftSummary();
}

itemCategory?.addEventListener("change", function () {
    loadCategoryItems(this.value);
    clearLookupDraft(true);
});

document.getElementById("qty")?.addEventListener("input", function () {
    document.getElementById("sale_price").dataset.underCostConfirmed = "";
    syncBulkImeiButton();
});

document.getElementById("sale_price")?.addEventListener("input", function () {
    this.dataset.underCostConfirmed = "";
});

document.getElementById("discount")?.addEventListener("input", function () {
    document.getElementById("sale_price").dataset.underCostConfirmed = "";
});

itemNameInput?.addEventListener("change", function () {
    if (this.value.trim() !== "") {
        lookupItemByName();
    }
});

itemNameInput?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        lookupItemByName();
    }
});

itemCodeInput?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        lookupItemByCode();
    }
});

document.getElementById("customer_name_search")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        searchCustomers();
    }
});

document.getElementById("customer_mobile_search")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        searchCustomers();
    }
});

document.getElementById("qty")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        document.getElementById("sale_price")?.focus();
    }
});

document.getElementById("sale_price")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        if (validateDraftBeforeAdd()) {
            addItemForm?.requestSubmit();
        }
    }
});

document.getElementById("discount")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        if (validateDraftBeforeAdd()) {
            addItemForm?.requestSubmit();
        }
    }
});

addItemForm?.addEventListener("submit", function (event) {
    if (!validateDraftBeforeAdd()) {
        event.preventDefault();
    }
});

sellerIdInput?.addEventListener("change", function () {
    lookupSalesPerson();
});

sellerIdInput?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        lookupSalesPerson();
    }
});

sellerIdInput?.addEventListener("blur", function () {
    if ((this.value || "").trim() === "") {
        this.value = "0";
        useCurrentCashier();
    }
});

document.getElementById("card_number")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        setPosStatus("Staging payment details from the card field fast path.", "info");
        this.closest("form")?.requestSubmit();
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "F5") {
        event.preventDefault();
        setPosStatus("Use the Clear Bill button to reset the current bill slot.", "info");
        document.querySelector('form[action="/pos/slots/<?= $activeSlot ?>/clear"] button[type="submit"]')?.focus();
    }

    if (event.key === "F4") {
        event.preventDefault();
        document.getElementById("pos_category")?.focus();
    }

    if (event.key === "F6") {
        event.preventDefault();
        document.getElementById("customer_name_search")?.focus();
    }

    if (event.key === "F7") {
        event.preventDefault();
        document.getElementById("seller_id_search")?.focus();
        document.getElementById("seller_id_search")?.select();
    }

    if (event.key === "F8") {
        event.preventDefault();
        document.getElementById("pos_item_code")?.focus();
    }

    if (event.key === "F2") {
        event.preventDefault();
        document.getElementById("cash_amount")?.focus();
    }

    if (event.key === "F9") {
        event.preventDefault();
        document.getElementById("method")?.focus();
    }

    if (event.key === "Escape" && editModal && !editModal.hidden) {
        event.preventDefault();
        closeEditModal();
    }

    if (event.key === "Escape" && bulkImeiModal && !bulkImeiModal.hidden) {
        event.preventDefault();
        closeBulkImeiModal();
    }

    if (event.key === "Escape" && editModal.hidden && bulkImeiModal.hidden && lookupPayloadInput.value !== "{}") {
        event.preventDefault();
        clearLookupDraft();
        setPosStatus("Current item draft was cleared. Ready for a new lookup.", "info");
        itemCodeInput?.focus();
        itemCodeInput?.select();
    }

    if (/^[1-9]$/.test(event.key)
        && customerResultButtons.length > 0
        && editModal.hidden
        && bulkImeiModal.hidden
        && customerSelectionContextActive()) {
        const index = Number(event.key) - 1;
        const targetButton = customerResultButtons[index];
        if (targetButton) {
            event.preventDefault();
            targetButton.click();
        }
    }
});

document.getElementById("cash_amount")?.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        if (validateCheckoutDraft()) {
            setPosStatus("Submitting the current bill from the cash input fast path.", "info");
            checkoutForm?.requestSubmit();
        }
    }
});

document.getElementById("method")?.addEventListener("change", refreshPaymentDraftSummary);
document.getElementById("cash_amount")?.addEventListener("input", refreshPaymentDraftSummary);
document.getElementById("card_amount")?.addEventListener("input", refreshPaymentDraftSummary);
document.getElementById("card_number")?.addEventListener("input", refreshPaymentDraftSummary);

document.getElementById("exact_cash_button")?.addEventListener("click", function () {
    document.getElementById("method").value = "cash";
    togglePaymentFields();
    document.getElementById("cash_amount").value = Number(totalDue).toFixed(2);
    document.getElementById("card_amount").value = "0";
    document.getElementById("card_number").value = "";
    refreshPaymentDraftSummary();
    setPosStatus("Exact cash amount loaded for this bill.", "success");
    document.getElementById("cash_amount")?.focus();
    document.getElementById("cash_amount")?.select();
});

paymentDraftForm?.addEventListener("submit", function () {
    syncCheckoutPaymentInputs();
});

checkoutForm?.addEventListener("submit", function (event) {
    syncCheckoutPaymentInputs();
    if (!validateCheckoutDraft()) {
        event.preventDefault();
    }
});

editModal?.addEventListener("click", function (event) {
    if (event.target === editModal) {
        closeEditModal();
    }
});

bulkImeiModal?.addEventListener("click", function (event) {
    if (event.target === bulkImeiModal) {
        closeBulkImeiModal();
    }
});

togglePaymentFields();
syncBulkImeiButton();
refreshPaymentDraftSummary();
</script>

