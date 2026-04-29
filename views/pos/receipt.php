<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$customerName = $customer["cus_name"] ?? ($bill["customer_name"] ?? "Cash Customer");
$cashierName = $cashier["visibledata"] ?? $cashier["ankaya"] ?? "";
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Bill Completed</div>
            <div class="muted" style="color: #b8c6cf;">Receipt view replaces the old `printmybill.php` handoff for now. Browser/printer integration is still pending.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">New Bill</a>
                <a class="nav-link" href="/cashier">Cashier Duty</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Bill ID</div>
                        <strong><?= htmlspecialchars((string) $bill["billnumber"], ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Date</div>
                        <strong><?= htmlspecialchars((string) ($bill["billaddedtime"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Cashier</div>
                        <strong><?= htmlspecialchars((string) $cashierName, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Customer</div>
                        <strong><?= htmlspecialchars((string) $customerName, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <div style="text-align:center; margin-bottom:18px;">
                    <div><strong><?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></div>
                    <div><?= htmlspecialchars((string) ($shop["shopaddress"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><?= htmlspecialchars((string) ($shop["shop_tel_1"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">Item</th>
                                <th style="padding:12px;">Code</th>
                                <th style="padding:12px;">Qty</th>
                                <th style="padding:12px;">Price</th>
                                <th style="padding:12px;">Discount</th>
                                <th style="padding:12px;">Subtotal</th>
                                <th style="padding:12px;">Warranty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $line["item_name"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $line["imei_part_no"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= (int) $line["qty"] ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sale_price"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["discount"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sub_total"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($line["waranty"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Net Total</div>
                        <strong><?= htmlspecialchars($formatMoney($bill["totalbill"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Cash Payment</div>
                        <strong><?= htmlspecialchars($formatMoney($bill["cash_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Card Payment</div>
                        <strong><?= htmlspecialchars($formatMoney($bill["card_pay"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Balance / Change</div>
                        <strong><?= htmlspecialchars($formatMoney($bill["balance"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>

                <p class="muted" style="margin:18px 0 0;">
                    Browser-side receipt printer integration and barcode/warranty print actions are still pending. This page confirms that the sale was committed successfully.
                </p>
            </section>
        </main>
    </div>
</div>
