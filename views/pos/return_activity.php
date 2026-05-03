<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$event = is_array($event ?? null) ? $event : [];
$bill = is_array($bill ?? null) ? $bill : [];
$items = is_array($items ?? null) ? $items : [];
$customerId = (int) ($bill["customer_id"] ?? 0);
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Return Activity</div>
            <div class="muted" style="color: #b8c6cf;">Process pending bill-return items through replacement, cash settlement, or customer credit.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">Current Bill</a>
                <a class="nav-link active" href="/pos/returns/pending">Pending Returns</a>
                <a class="nav-link" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns">Return History</a>
                <a class="nav-link" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Back To Receipt</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Bill ID</div>
                        <strong><?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Alter Time</div>
                        <strong><?= (int) ($event["alter_times"] ?? 0) ?></strong>
                    </div>
                    <div>
                        <div class="muted">Customer</div>
                        <strong><?= htmlspecialchars((string) ($bill["customer_name"] ?? "Cash Customer"), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Reason</div>
                        <strong><?= htmlspecialchars((string) ($event["alter_reason"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>
            </section>

            <?php foreach ($items as $item): ?>
                <section class="card" style="margin-bottom:18px;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:start; margin-bottom:16px;">
                        <div>
                            <div><strong><?= htmlspecialchars((string) ($item["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></div>
                            <div class="muted">
                                Return Qty <?= (int) ($item["return_count"] ?? 0) ?> |
                                Return Value <?= htmlspecialchars($formatMoney($item["return_sale"] ?? 0), ENT_QUOTES, "UTF-8") ?>
                            </div>
                        </div>
                        <div class="muted">Code: <?= htmlspecialchars((string) ($item["imei_part_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    </div>

                    <?php if ((int) ($item["activity"] ?? 0) !== 0): ?>
                        <p class="muted">
                            <?= (int) ($item["activity"] ?? 0) === 2 ? "Customer credit created for this return item." : "This return item has already been processed." ?>
                        </p>
                    <?php else: ?>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:18px;">
                            <form method="POST" action="/pos/returns/items/<?= (int) ($item["recordid"] ?? 0) ?>/settle" data-return-settle-form style="border:1px solid var(--border); border-radius:14px; padding:18px;">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                <div style="font-weight:700; margin-bottom:12px;">Replacement / Cash Settlement</div>

                                <div class="form-row">
                                    <label for="code_lookup_<?= (int) ($item["recordid"] ?? 0) ?>">Lookup By Code / IMEI</label>
                                    <div style="display:flex; gap:8px;">
                                        <input class="input" id="code_lookup_<?= (int) ($item["recordid"] ?? 0) ?>" data-code-lookup type="text">
                                        <button class="btn" type="button" data-code-lookup-button style="background:#eef2f5; color:#163041;">Find</button>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <label for="name_lookup_<?= (int) ($item["recordid"] ?? 0) ?>">Lookup By Item Name</label>
                                    <div style="display:flex; gap:8px;">
                                        <input class="input" id="name_lookup_<?= (int) ($item["recordid"] ?? 0) ?>" data-name-lookup type="text">
                                        <button class="btn" type="button" data-name-lookup-button style="background:#eef2f5; color:#163041;">Find</button>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <label>Replacement Item</label>
                                    <input class="input" type="text" data-selected-name readonly>
                                </div>

                                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                                    <div class="form-row">
                                        <label>Stock</label>
                                        <input class="input" type="text" data-selected-stock readonly>
                                    </div>
                                    <div class="form-row">
                                        <label>Sell Price</label>
                                        <input class="input" type="number" name="replacement_price" data-price step="0.01" min="0" value="0">
                                    </div>
                                    <div class="form-row">
                                        <label>Replace Qty</label>
                                        <input class="input" type="number" name="replacement_qty" data-qty step="1" min="0" value="0">
                                    </div>
                                </div>

                                <input type="hidden" name="replacement_type" data-selected-type value="0">
                                <input type="hidden" name="replacement_row_id" data-selected-row value="0">

                                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                                    <div class="form-row">
                                        <label>Replace Value</label>
                                        <input class="input" type="text" data-replace-total readonly>
                                    </div>
                                    <div class="form-row">
                                        <label>Money To Customer</label>
                                        <input class="input" type="number" name="money_return" data-money-return step="0.01" min="0" value="0">
                                    </div>
                                    <div class="form-row">
                                        <label>Money From Customer</label>
                                        <input class="input" type="number" name="money_collect" data-money-collect step="0.01" min="0" value="0">
                                    </div>
                                    <div class="form-row">
                                        <label>Balance</label>
                                        <input class="input" type="text" data-balance readonly>
                                    </div>
                                </div>

                                <input type="hidden" data-return-value value="<?= htmlspecialchars((string) ($item["return_sale"] ?? 0), ENT_QUOTES, "UTF-8") ?>">

                                <div class="muted" data-inline-status style="margin-bottom:12px;"></div>
                                <button class="btn btn-primary" type="submit">Process Settlement</button>
                            </form>

                            <form method="POST" action="/pos/returns/items/<?= (int) ($item["recordid"] ?? 0) ?>/credit" style="border:1px solid var(--border); border-radius:14px; padding:18px;">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                <div style="font-weight:700; margin-bottom:12px;">Customer Credit</div>
                                <p class="muted" style="margin-bottom:16px;">
                                    Create a customer cash-credit record for the full return value of
                                    <?= htmlspecialchars($formatMoney($item["return_sale"] ?? 0), ENT_QUOTES, "UTF-8") ?>.
                                </p>
                                <?php if ($customerId < 1): ?>
                                    <p class="muted">This bill belongs to Cash Customer, so credit return is not allowed.</p>
                                    <button class="btn" type="button" disabled style="background:#eef2f5; color:#8aa0ad;">Credit Not Allowed</button>
                                <?php else: ?>
                                    <button class="btn" type="submit" style="background:#eef2f5; color:#163041;">Create Customer Credit</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-return-settle-form]").forEach(function (form) {
        const codeInput = form.querySelector("[data-code-lookup]");
        const nameInput = form.querySelector("[data-name-lookup]");
        const selectedName = form.querySelector("[data-selected-name]");
        const selectedStock = form.querySelector("[data-selected-stock]");
        const selectedType = form.querySelector("[data-selected-type]");
        const selectedRow = form.querySelector("[data-selected-row]");
        const priceInput = form.querySelector("[data-price]");
        const qtyInput = form.querySelector("[data-qty]");
        const moneyReturnInput = form.querySelector("[data-money-return]");
        const moneyCollectInput = form.querySelector("[data-money-collect]");
        const replaceTotal = form.querySelector("[data-replace-total]");
        const balance = form.querySelector("[data-balance]");
        const status = form.querySelector("[data-inline-status]");
        const returnValue = parseFloat(form.querySelector("[data-return-value]").value || "0");

        function setStatus(message, isError) {
            status.textContent = message;
            status.style.color = isError ? "#8f2d15" : "#50606d";
        }

        function updateMath() {
            const qty = parseFloat(qtyInput.value || "0");
            const price = parseFloat(priceInput.value || "0");
            const moneyReturn = parseFloat(moneyReturnInput.value || "0");
            const moneyCollect = parseFloat(moneyCollectInput.value || "0");
            const replacement = qty * price;
            const remaining = returnValue - replacement - moneyReturn + moneyCollect;

            replaceTotal.value = replacement.toFixed(2);
            balance.value = remaining.toFixed(2);
        }

        function applyResult(data) {
            if (!data || !data.itm_itmstype) {
                setStatus("Replacement item was not found.", true);
                return;
            }

            selectedType.value = data.itm_itmstype || "0";
            selectedRow.value = data.row_ids_data || "0";
            selectedName.value = data.itm_selctnme || "";
            selectedStock.value = data.itm_stktotal || "";
            priceInput.value = data.itm_sellpris || "0";
            qtyInput.value = data.itm_itmstype === "2" ? "1" : "0";
            setStatus("Replacement item loaded.", false);
            updateMath();
        }

        async function lookup(url, payload) {
            const body = new URLSearchParams(payload);
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString()
            });
            return response.json();
        }

        form.querySelector("[data-code-lookup-button]").addEventListener("click", async function () {
            setStatus("Looking up item by code...", false);
            applyResult(await lookup("/api/pos/items/by-code", { js_inp_itemcode: codeInput.value || "" }));
        });

        form.querySelector("[data-name-lookup-button]").addEventListener("click", async function () {
            setStatus("Looking up item by name...", false);
            applyResult(await lookup("/api/pos/items/by-name", { js_inp_item: nameInput.value || "" }));
        });

        [priceInput, qtyInput, moneyReturnInput, moneyCollectInput].forEach(function (input) {
            input.addEventListener("input", updateMath);
        });

        form.addEventListener("submit", function (event) {
            updateMath();
            if (Math.abs(parseFloat(balance.value || "0")) > 0.009) {
                event.preventDefault();
                setStatus("Return value is not fully settled.", true);
            }
        });

        updateMath();
    });
});
</script>
