<?php
$company = is_array($company ?? null) ? $company : [];
$selectedSupplierId = (int) ($selectedSupplier["supplierid"] ?? 0);
$formatMoney = static function (mixed $value): string {
    return number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Supplier Payment Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `reports/rpt_supply_payment.php` and `reports/c_supply.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/reports/supplier-payments">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="from">From Date</label>
                            <input class="input" id="from" name="from" type="date" value="<?= htmlspecialchars((string) $fromDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="to">To Date</label>
                            <input class="input" id="to" name="to" type="date" value="<?= htmlspecialchars((string) $toDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="supplier">Select Supplier</label>
                            <select class="input" id="supplier" name="supplier" required>
                                <option value="">Choose...</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int) $supplier["supplierid"] ?>" <?= $selectedSupplierId === (int) $supplier["supplierid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <button class="btn btn-primary" type="submit">Load Data</button>
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
                    <div style="margin-top:10px; text-decoration:underline;">Supplier Account Full Detail Report</div>
                </div>

                <?php if ($selectedSupplier !== null): ?>
                    <p style="margin:0 0 18px;">
                        <?= htmlspecialchars((string) $selectedSupplier["supplier_name"], ENT_QUOTES, "UTF-8") ?> supplier account details from
                        <?= htmlspecialchars((string) $fromDate, ENT_QUOTES, "UTF-8") ?> to
                        <?= htmlspecialchars((string) $toDate, ENT_QUOTES, "UTF-8") ?> time period.
                        <br>(In this report debit balance indicates the supplier paid amount.)
                    </p>
                <?php endif; ?>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">Record Time</th>
                                <th style="padding:12px;">Operation Type</th>
                                <th style="padding:12px;">Details</th>
                                <th style="padding:12px; text-align:right;">Debit</th>
                                <th style="padding:12px; text-align:right;">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $row["recordtime"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $row["op_type_label"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) $row["details"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($row["debit"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($row["credit"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($selectedSupplierId === 0): ?>
                    <p class="muted" style="margin:16px 0 0;">Select a supplier and date range to load report data.</p>
                <?php elseif ($rows === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No supplier payment records found for the selected period.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
