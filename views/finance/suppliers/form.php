<?php
$receipt = is_array($receipt ?? null) ? $receipt : null;
$selectedSupplierId = (int) ($selectedSupplier["supplierid"] ?? 0);
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Supplier Payment</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `supplier_payment.php` and `c_supp_upd_details.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <?php if ($receipt !== null): ?>
                <section class="card" style="margin-bottom:18px;">
                    <div class="tag">Payment Recorded</div>
                    <p style="margin:14px 0 0;">
                        Supplier: <strong><?= htmlspecialchars((string) $receipt["name"], ENT_QUOTES, "UTF-8") ?></strong><br>
                        Method: <strong><?= htmlspecialchars(ucfirst((string) $receipt["method"]), ENT_QUOTES, "UTF-8") ?></strong><br>
                        Amount: <strong>Rs. <?= number_format((float) $receipt["amount"], 2, ".", ",") ?></strong><br>
                        Time: <strong><?= htmlspecialchars((string) $receipt["recordtime"], ENT_QUOTES, "UTF-8") ?></strong>
                        <?php if (!empty($receipt["reference"])): ?><br>Cheque No: <strong><?= htmlspecialchars((string) $receipt["reference"], ENT_QUOTES, "UTF-8") ?></strong><?php endif; ?>
                    </p>
                    <div style="margin-top: 14px;">
                        <?php 
                        $payTypeInt = $receipt["method"] === 'cash' ? 1 : ($receipt["method"] === 'cheque' ? 2 : 3);
                        $printUrl = '/print/supplier-payment?supid=' . $selectedSupplierId . '&paytype=' . $payTypeInt . '&amtpay=' . urlencode((string)$receipt["amount"]) . '&chqunmber=' . urlencode((string)($receipt["reference"] ?? ''));
                        ?>
                        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"></path></svg>
                            Print Receipt
                        </a>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card" style="margin-bottom:18px;">
                <p class="section-copy" style="margin:0;">
                    This migration records supplier payments into the legacy finance tables and keeps the write flow in-app. Cashier duty-on enforcement is now active. Bill-print handoff is still queued for a later reporting/printing slice.
                </p>
            </section>

            <section class="card">
                <form method="POST" action="/supplier-payments">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: minmax(280px, 1fr) auto; align-items:end;">
                        <div class="form-row">
                            <label for="supplier_id">Supplier Name</label>
                            <select class="input" id="supplier_id" name="supplier_id" onchange="loadSupplierDetails(this.value)" required>
                                <option value="">Select supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int) $supplier["supplierid"] ?>" <?= $selectedSupplierId === (int) $supplier["supplierid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a class="btn" href="/supplier-payments" style="background:#eef2f5; color:#163041;">Reset</a>
                    </div>

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top:16px;">
                        <div class="form-row">
                            <label>Supplier Address</label>
                            <input class="input" id="supplier_address" readonly value="<?= htmlspecialchars((string) ($selectedSupplier["supplier_address"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label>Supplier Telephone</label>
                            <input class="input" id="supplier_mobile" readonly value="<?= htmlspecialchars((string) ($selectedSupplier["supplier_mobile"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label>Supplier Balance</label>
                            <input class="input" id="supplier_balance" readonly value="<?= $selectedSupplier ? "Rs. " . number_format((float) ($selectedSupplier["accbalance"] ?? 0), 2, ".", ",") : "" ?>">
                        </div>
                    </div>

                    <div style="margin-top:16px;">
                        <label style="display:block; margin-bottom:8px;">Select Payment Method</label>
                        <div style="display:flex; gap:18px; flex-wrap:wrap;">
                            <label><input type="radio" name="payment_method" value="cash" checked onclick="togglePaymentMode()"> Cash</label>
                            <label><input type="radio" name="payment_method" value="cheque" onclick="togglePaymentMode()"> Cheque</label>
                            <label><input type="radio" name="payment_method" value="credit" onclick="togglePaymentMode()"> Cash Credit</label>
                        </div>
                    </div>

                    <div id="payment_mode_cash" style="margin-top:18px;">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                            <div class="form-row">
                                <label for="pay_cashamount">Cash Pay Amount (Rs.)</label>
                                <input class="input" id="pay_cashamount" name="pay_cashamount">
                            </div>
                            <div class="form-row" style="grid-column: 1 / -1;">
                                <label for="pay_cashreason">Payment Reason</label>
                                <input class="input" id="pay_cashreason" name="pay_cashreason">
                            </div>
                        </div>
                    </div>

                    <div id="payment_mode_cheque" style="margin-top:18px;" hidden>
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                            <div class="form-row">
                                <label for="pay_chequeamount">Cheque Pay Amount (Rs.)</label>
                                <input class="input" id="pay_chequeamount" name="pay_chequeamount">
                            </div>
                            <div class="form-row">
                                <label for="pay_chequenumber">Cheque Number</label>
                                <input class="input" id="pay_chequenumber" name="pay_chequenumber">
                            </div>
                            <div class="form-row">
                                <label for="pay_chequedate">Cheque Date</label>
                                <input class="input" id="pay_chequedate" name="pay_chequedate" type="date">
                            </div>
                            <div class="form-row">
                                <label for="cheque_reminder">Reminder</label>
                                <select class="input" id="cheque_reminder" name="cheque_reminder">
                                    <option value="0">Turn Off</option>
                                    <option value="1">Remind Me On</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="pay_chequereminderdate">Reminder Date</label>
                                <input class="input" id="pay_chequereminderdate" name="pay_chequereminderdate" type="date">
                            </div>
                            <div class="form-row" style="grid-column: 1 / -1;">
                                <label for="pay_chequereason">Payment Reason</label>
                                <input class="input" id="pay_chequereason" name="pay_chequereason">
                            </div>
                        </div>
                    </div>

                    <div id="payment_mode_credit" style="margin-top:18px;" hidden>
                        <p class="muted" style="margin-top:0;">Select the supplier cash-credit records that should be settled by this payment.</p>
                        <div id="supplier_credits" class="stack"></div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                        <button class="btn btn-primary" type="submit">Record Supplier Payment</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
function togglePaymentMode() {
    const mode = document.querySelector('input[name="payment_method"]:checked')?.value || "cash";
    document.getElementById("payment_mode_cash").hidden = mode !== "cash";
    document.getElementById("payment_mode_cheque").hidden = mode !== "cheque";
    document.getElementById("payment_mode_credit").hidden = mode !== "credit";
}

async function loadSupplierDetails(supplierId) {
    if (!supplierId) {
        document.getElementById("supplier_address").value = "";
        document.getElementById("supplier_mobile").value = "";
        document.getElementById("supplier_balance").value = "";
        document.getElementById("supplier_credits").innerHTML = "";
        return;
    }

    const response = await fetch("/api/supplier-payments/details?supplier_id=" + encodeURIComponent(supplierId));
    if (!response.ok) {
        return;
    }

    const data = await response.json();
    document.getElementById("supplier_address").value = data.address || "";
    document.getElementById("supplier_mobile").value = data.mobile || "";
    document.getElementById("supplier_balance").value = "Rs. " + Number(data.balance || 0).toFixed(2);

    const target = document.getElementById("supplier_credits");
    target.innerHTML = "";
    for (const credit of data.credits || []) {
        const row = document.createElement("label");
        row.className = "card";
        row.style.padding = "12px";
        row.innerHTML = '<input type="checkbox" name="cash_credits_rec[]" value="' + credit.logid + '"> Rs. ' + Number(credit.amount || 0).toFixed(2) + ' (' + (credit.remark || '') + ')';
        target.appendChild(row);
    }
}

togglePaymentMode();
<?php if ($selectedSupplierId > 0): ?>
loadSupplierDetails(<?= $selectedSupplierId ?>);
<?php endif; ?>
</script>
