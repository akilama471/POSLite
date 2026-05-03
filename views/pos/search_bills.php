<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$filters = is_array($filters ?? null) ? $filters : [];
$bills = is_array($bills ?? null) ? $bills : [];
$searched = (bool) ($searched ?? false);
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Find Bill</div>
            <div class="muted" style="color: #b8c6cf;">Search committed POS bills by bill data or by sold item data, then jump into MVC receipt and reprint pages.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">Current Bill</a>
                <a class="nav-link" href="/pos/bills/today">Daily Bills</a>
                <a class="nav-link active" href="/pos/bills/search">Find Bill</a>
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
                <form method="GET" action="/pos/bills/search">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="billnumber">Bill ID</label>
                            <input class="input" id="billnumber" name="billnumber" value="<?= htmlspecialchars((string) ($filters["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="customer_name">Customer Name</label>
                            <input class="input" id="customer_name" name="customer_name" value="<?= htmlspecialchars((string) ($filters["customer_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="item_name">Item Name</label>
                            <input class="input" id="item_name" name="item_name" value="<?= htmlspecialchars((string) ($filters["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="item_code">Item Code / IMEI</label>
                            <input class="input" id="item_code" name="item_code" value="<?= htmlspecialchars((string) ($filters["item_code"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="from_date">From Date</label>
                            <input class="input" type="date" id="from_date" name="from_date" value="<?= htmlspecialchars((string) ($filters["from_date"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="to_date">To Date</label>
                            <input class="input" type="date" id="to_date" name="to_date" value="<?= htmlspecialchars((string) ($filters["to_date"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Search Bills</button>
                        <a class="btn" href="/pos/bills/search" style="background:#eef2f5; color:#163041;">Clear</a>
                    </div>
                </form>
            </section>

            <?php if (!$searched): ?>
                <section class="card">
                    <p class="muted">Provide bill details or item details before running a bill search.</p>
                </section>
            <?php elseif ($bills === []): ?>
                <section class="card">
                    <p class="muted">No completed POS bills matched the current search filters.</p>
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
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
