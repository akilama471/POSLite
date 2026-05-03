<?php
$receipt = is_array($receipt ?? null) ? $receipt : null;
$isDue = (int) ($payment["payment_status"] ?? 0) === 1 && (float) ($payment["due_amount"] ?? 0) > 0;
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN Payment Detail</div>
            <div class="muted" style="color: #b8c6cf;"><?= htmlspecialchars((string) ($payment["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
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
                        Method: <strong><?= htmlspecialchars(ucfirst((string) $receipt["method"]), ENT_QUOTES, "UTF-8") ?></strong><br>
                        Amount: <strong>Rs. <?= number_format((float) $receipt["amount"], 2, ".", ",") ?></strong><br>
                        Remaining Due: <strong>Rs. <?= number_format((float) $receipt["due_amount"], 2, ".", ",") ?></strong>
                    </p>
                </section>
            <?php endif; ?>

            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div><strong>Supplier</strong><br><?= htmlspecialchars((string) ($payment["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Shop</strong><br><?= htmlspecialchars((string) ($payment["shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Invoice / GRN</strong><br><?= htmlspecialchars((string) (($payment["inv_number"] ?? "") . " / " . ($payment["grn_refno"] ?? "")), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Status</strong><br><?= (int) ($payment["payment_status"] ?? 0) === 0 ? "Paid" : "Due" ?></div>
                    <div><strong>Total</strong><br>Rs. <?= number_format((float) ($payment["grn_final_amount"] ?? 0), 2, ".", ",") ?></div>
                    <div><strong>Due</strong><br>Rs. <?= number_format((float) ($payment["due_amount"] ?? 0), 2, ".", ",") ?></div>
                </div>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                    <a class="btn" href="/grn-payments" style="background:#eef2f5; color:#163041;">Back to GRN Payments</a>
                </div>
            </section>

            <?php if ($isDue): ?>
                <section class="card" style="margin-bottom:18px;">
                    <div style="display:flex; gap:18px; flex-wrap:wrap; margin-bottom:16px;">
                        <label><input type="radio" name="grn_pay_mode" value="cash" checked onclick="toggleGrnPayMode()"> Cash</label>
                        <label><input type="radio" name="grn_pay_mode" value="cheque" onclick="toggleGrnPayMode()"> Cheque</label>
                        <label><input type="radio" name="grn_pay_mode" value="credit" onclick="toggleGrnPayMode()"> Cash Credit</label>
                    </div>

                    <form id="grn_mode_cash" method="POST" action="/grn-payments/<?= (int) $payment["record_id"] ?>/cash">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="grid" style="grid-template-columns: minmax(220px, 320px) auto;">
                            <div class="form-row">
                                <label for="pay_cashamount">Cash Pay Amount</label>
                                <input class="input" type="number" min="0" step="0.01" max="<?= htmlspecialchars((string) ($payment["due_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?>" id="pay_cashamount" name="pay_cashamount">
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <button class="btn btn-primary" type="submit">Record Cash Payment</button>
                        </div>
                    </form>

                    <form id="grn_mode_cheque" method="POST" action="/grn-payments/<?= (int) $payment["record_id"] ?>/cheque" hidden>
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                            <div class="form-row">
                                <label for="pay_chequeamount">Cheque Pay Amount</label>
                                <input class="input" type="number" min="0" step="0.01" max="<?= htmlspecialchars((string) ($payment["due_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?>" id="pay_chequeamount" name="pay_chequeamount">
                            </div>
                            <div class="form-row">
                                <label for="pay_chequenumber">Cheque Number</label>
                                <input class="input" id="pay_chequenumber" name="pay_chequenumber">
                            </div>
                            <div class="form-row">
                                <label for="pay_chequedate">Cheque Date</label>
                                <input class="input" type="date" id="pay_chequedate" name="pay_chequedate">
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
                                <input class="input" type="date" id="pay_chequereminderdate" name="pay_chequereminderdate">
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <button class="btn btn-primary" type="submit">Record Cheque Payment</button>
                        </div>
                    </form>

                    <form id="grn_mode_credit" method="POST" action="/grn-payments/<?= (int) $payment["record_id"] ?>/credit" hidden>
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <p class="muted">Only active supplier cash-credit records are shown. Select records whose total does not exceed the current GRN due amount.</p>
                        <div class="stack">
                            <?php if ($credits === []): ?>
                                <p class="muted">No active supplier cash-credit records were found.</p>
                            <?php else: ?>
                                <?php foreach ($credits as $credit): ?>
                                    <label class="card" style="padding:12px;">
                                        <input type="checkbox" name="cash_credits_rec[]" value="<?= (int) $credit["logid"] ?>">
                                        Rs. <?= number_format((float) ($credit["amount"] ?? 0), 2, ".", ",") ?> (<?= htmlspecialchars((string) ($credit["remark"] ?? ""), ENT_QUOTES, "UTF-8") ?>)
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:16px;">
                            <button class="btn btn-primary" type="submit">Settle With Cash Credit</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="card">
                <h3 style="margin-top:0;">Payment History</h3>
                <div style="overflow:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Cash</th>
                                <th>Cheque</th>
                                <th>Paid User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history === []): ?>
                                <tr>
                                    <td colspan="5" class="muted">No payment history found for this GRN.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row["record_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["pay_type_label"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>Rs. <?= number_format((float) ($row["cash_pay_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td>Rs. <?= number_format((float) ($row["chq_pay_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["paid_user_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
function toggleGrnPayMode() {
    const mode = document.querySelector('input[name="grn_pay_mode"]:checked')?.value || "cash";
    document.getElementById("grn_mode_cash").hidden = mode !== "cash";
    document.getElementById("grn_mode_cheque").hidden = mode !== "cheque";
    document.getElementById("grn_mode_credit").hidden = mode !== "credit";
}

toggleGrnPayMode();
</script>
