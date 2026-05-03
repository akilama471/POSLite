<?php
$company = is_array($company ?? null) ? $company : [];
$items = $detail["items"] ?? [];
$formatMoney = static fn (mixed $value): string => number_format((float) $value, 2, ".", ",");
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN Detail Report</div>
            <div class="muted" style="color: #b8c6cf;"><?= htmlspecialchars((string) ($detail["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <section class="card" style="margin-bottom:18px; display:flex; gap:12px; flex-wrap:wrap;">
                <a class="btn" href="/reports/grns" style="background:#eef2f5; color:#163041;">Back to GRN List</a>
                <button class="btn" type="button" onclick="window.print()" style="background:#eef2f5; color:#163041;">Print Document</button>
            </section>

            <section class="card" id="report_sheet">
                <div style="text-align:center; margin-bottom:22px;">
                    <div><?= htmlspecialchars((string) ($company["companyname"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><?= htmlspecialchars((string) ($company["companyaddress"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><?= htmlspecialchars((string) ($company["company_tel_1"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div style="margin-top:10px; text-decoration:underline;">GRN Detail Report</div>
                </div>

                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:18px;">
                    <div><strong>GRN ID</strong><br><?= htmlspecialchars((string) ($detail["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>GRN Time</strong><br><?= htmlspecialchars((string) ($detail["operation_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Supplier</strong><br><?= htmlspecialchars((string) ($detail["suppler_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Processed By</strong><br><?= htmlspecialchars((string) ($detail["operator_name"] ?? $detail["operator"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>GRN Amount</strong><br><?= htmlspecialchars($formatMoney($detail["amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Discount</strong><br><?= htmlspecialchars($formatMoney($detail["discount_mny"] ?? 0), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Net Amount</strong><br><?= htmlspecialchars($formatMoney($detail["final_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Cash Pay</strong><br><?= htmlspecialchars($formatMoney($detail["cash_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Cheque Pay</strong><br><?= htmlspecialchars($formatMoney($detail["chq_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></div>
                    <div><strong>Cheque Number</strong><br><?= htmlspecialchars((string) ($detail["chq_number"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">Item Category</th>
                                <th style="padding:12px;">Item Name</th>
                                <th style="padding:12px;">IMEI</th>
                                <th style="padding:12px; text-align:right;">Qty</th>
                                <th style="padding:12px; text-align:right;">Cost</th>
                                <th style="padding:12px; text-align:right;">Low</th>
                                <th style="padding:12px; text-align:right;">Other</th>
                                <th style="padding:12px; text-align:right;">Sale</th>
                                <th style="padding:12px;">Stock Shop</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($item["item_category"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($item["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($item["imei_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= (int) ($item["item_qty"] ?? 0) ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_costpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_lowpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_otherpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_sellpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($item["stock_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
