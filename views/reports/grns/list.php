<?php
$company = is_array($company ?? null) ? $company : [];
$formatMoney = static fn (mixed $value): string => number_format((float) $value, 2, ".", ",");
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN List Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `reports/c_grn.php` list mode</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/reports/grns">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="from">From Date</label>
                            <input class="input" type="date" id="from" name="from" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="to">To Date</label>
                            <input class="input" type="date" id="to" name="to" value="<?= htmlspecialchars($toDate, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="shop">Shop</label>
                            <select class="input" id="shop" name="shop" <?= (int) ($auth["shop_id"] ?? 0) > 0 ? "disabled" : "" ?>>
                                <option value="-1">All Shops</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int) $shop["shopid"] ?>" <?= (int) $selectedShopId === (int) $shop["shopid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int) ($auth["shop_id"] ?? 0) > 0): ?>
                                <input type="hidden" name="shop" value="<?= (int) $auth["shop_id"] ?>">
                            <?php endif; ?>
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
                    <div style="margin-top:10px; text-decoration:underline;">GRN List Report</div>
                </div>

                <p style="margin:0 0 18px;">
                    GRN details from <?= htmlspecialchars($fromDate, ENT_QUOTES, "UTF-8") ?> to <?= htmlspecialchars($toDate, ENT_QUOTES, "UTF-8") ?>.
                </p>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                <th style="padding:12px;">GRN Number</th>
                                <th style="padding:12px;">Supplier</th>
                                <th style="padding:12px;">Time</th>
                                <th style="padding:12px;">Shop</th>
                                <th style="padding:12px; text-align:right;">GRN Amount</th>
                                <th style="padding:12px; text-align:right;">Payment</th>
                                <th style="padding:12px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr style="border-bottom:1px solid #edf1f4;">
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["suppler_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["operation_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars((string) ($row["grn_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($row["final_amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney(((float) ($row["cash_amount"] ?? 0)) + ((float) ($row["chq_amount"] ?? 0))), ENT_QUOTES, "UTF-8") ?></td>
                                    <td style="padding:12px;"><a class="btn btn-primary" href="/reports/grns/<?= rawurlencode((string) ($row["grn_refno"] ?? "")) ?>">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($rows === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No GRN records found for the selected period.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
