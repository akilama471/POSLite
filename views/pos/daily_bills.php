<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$date = (string) ($date ?? date("Y-m-d"));
$bills = is_array($bills ?? null) ? $bills : [];
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Daily Bills</div>
            <div class="muted" style="color: #b8c6cf;">Operator-scoped recent POS bills with committed receipt and reprint links.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">Current Bill</a>
                <a class="nav-link active" href="/pos/bills/today">Daily Bills</a>
                <a class="nav-link" href="/pos/bills/search">Find Bill</a>
                <?php if (can("p_34")): ?>
                    <a class="nav-link" href="/pos/returns/pending">Pending Returns</a>
                <?php endif; ?>
                <a class="nav-link" href="/cashier">Cashier Duty</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/pos/bills/today" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                    <div class="form-row">
                        <label for="bill_date">Bill Date</label>
                        <input class="input" type="date" id="bill_date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, "UTF-8") ?>">
                    </div>
                    <div>
                        <button class="btn btn-primary" type="submit">Load Bills</button>
                    </div>
                </form>
            </section>

            <?php if ($bills === []): ?>
                <section class="card">
                    <p class="muted">No completed POS bills were found for the selected date under the current cashier.</p>
                </section>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <section class="card" style="margin-bottom:18px;">
                        <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:start; margin-bottom:16px;">
                            <div>
                                <div><strong><?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></div>
                                <div class="muted"><?= htmlspecialchars((string) ($bill["billaddedtime"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <a class="btn btn-primary" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Receipt</a>
                                <a class="btn" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/print" target="_blank" rel="noopener" style="background:#eef2f5; color:#163041;">Re-Print Bill</a>
                                <a class="btn" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/barcodes" target="_blank" rel="noopener" style="background:#eef2f5; color:#163041;">Re-Print Barcodes</a>
                                <?php if (can("p_33")): ?>
                                    <a class="btn" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns/create" style="background:#eef2f5; color:#163041;">Create Return</a>
                                    <a class="btn" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns" style="background:#eef2f5; color:#163041;">Return History</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:16px;">
                            <div>
                                <div class="muted">Cashier</div>
                                <strong><?= htmlspecialchars((string) ($bill["cashier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Customer</div>
                                <strong><?= htmlspecialchars((string) ($bill["customer_name"] ?? "Cash Customer"), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Sale Person</div>
                                <strong><?= htmlspecialchars((string) ($bill["seller_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Total</div>
                                <strong><?= htmlspecialchars($formatMoney($bill["totalbill"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Cash Paid</div>
                                <strong><?= htmlspecialchars($formatMoney($bill["cash_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Card Paid</div>
                                <strong><?= htmlspecialchars($formatMoney($bill["card_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                        </div>

                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Item</th>
                                        <th style="padding:12px;">Code</th>
                                        <th style="padding:12px;">Warranty</th>
                                        <th style="padding:12px;">Qty</th>
                                        <th style="padding:12px;">Price</th>
                                        <th style="padding:12px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($bill["lines"] ?? []) as $line): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($line["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($line["imei_part_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($line["waranty"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= (int) ($line["qty"] ?? 0) ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sale_price"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/cancel" onsubmit="return confirm('Cancel this completed bill and restore its stock?');" style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <div class="form-row" style="min-width:min(320px, 100%); flex:1;">
                                <label for="cancel_reason_<?= (int) ($bill["recordid"] ?? 0) ?>">Cancel Reason</label>
                                <input class="input" id="cancel_reason_<?= (int) ($bill["recordid"] ?? 0) ?>" name="reason" required>
                            </div>
                            <div>
                                <button class="btn" type="submit" style="background:#fbe4de; color:#8f2d15;">Cancel Bill</button>
                            </div>
                        </form>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
