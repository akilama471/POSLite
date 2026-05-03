<?php
$company = is_array($company ?? null) ? $company : [];
$selectedSupplierId = (int) ($selectedSupplier["supplierid"] ?? 0);
$formatMoney = static fn (mixed $value): string => number_format((float) $value, 2, ".", ",");
$sumAmount = 0.0;
$sumDue = 0.0;
foreach ($rows as $row) {
    $sumAmount += (float) ($row["grn_final_amount"] ?? 0);
    $sumDue += (float) ($row["due_amount"] ?? 0);
}
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `reports/c_grn.php` supplier summary mode</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/reports/grns/summary">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="from">From Date</label>
                            <input class="input" id="from" name="from" type="date" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="to">To Date</label>
                            <input class="input" id="to" name="to" type="date" value="<?= htmlspecialchars($toDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="supplier">Select Supplier</label>
                            <select class="input" id="supplier" name="supplier">
                                <option value="-1">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int) $supplier["supplierid"] ?>" <?= $selectedSupplierId === (int) $supplier["supplierid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <button class="btn btn-primary" type="submit">Load Report</button>
                            <button class="btn" type="button" onclick="window.print()" style="background:#eef2f5; color:#163041;">Print Document</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="card" id="report_sheet">
                <div style="text-align:center; margin-bottom:22px;">
                    <div><?= htmlspecialchars((string) ($company["companyname"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><?= htmlspecialchars((string) ($company["companyaddress"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div><?= htmlspecialchars((string) ($company["company_tel_1"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                    <div style="margin-top:10px; text-decoration:underline;">GRN Supplier Summary Report</div>
                </div>

                <p style="margin:0 0 18px;">
                    GRN payment details from <?= htmlspecialchars($fromDate, ENT_QUOTES, "UTF-8") ?> to <?= htmlspecialchars($toDate, ENT_QUOTES, "UTF-8") ?>
                    for <?= $selectedSupplierId > 0 ? htmlspecialchars((string) ($selectedSupplier["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") : "all suppliers" ?>.
                </p>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">Supplier</th>
                                <th style="padding:12px;">GRN Number</th>
                                <th style="padding:12px;">Invoice No</th>
                                <th style="padding:12px;">GRN Shop</th>
                                <th style="padding:12px; text-align:right;">Amount</th>
                                <th style="padding:12px;">Status</th>
                                <th style="padding:12px; text-align:right;">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["inv_number"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($row["grn_final_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; color:<?= (int) ($row["payment_status"] ?? 0) === 0 ? "green" : "red" ?>;"><?= (int) ($row["payment_status"] ?? 0) === 0 ? "Complete" : "Due" ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($row["due_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($rows !== []): ?>
                                <tr style="border-top:1px solid var(--border); font-weight:bold;">
                                    <td style="padding:12px; text-align:right;">Total</td>
                                    <td style="padding:12px;"></td>
                                    <td style="padding:12px;"></td>
                                    <td style="padding:12px;"></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($sumAmount), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($sumDue), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($rows === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No GRN payment records found for the selected period.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
