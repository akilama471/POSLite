<?php
$filters = is_array($filters ?? null) ? $filters : [];
$results = is_array($results ?? null) ? $results : [];
$shops = is_array($shops ?? null) ? $shops : [];
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Find GRN</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `findgrn.php` as the first GRN read-side MVC slice.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>Purchases</h3>
            <div class="nav-group">
                <?php if (can("p_43")): ?>
                    <a class="nav-link" href="/grns/create">Add GRN</a>
                <?php endif; ?>
                <a class="nav-link active" href="/grns">Find GRN</a>
                <?php if (can("p_24")): ?>
                    <a class="nav-link" href="/suppliers">Suppliers</a>
                <?php endif; ?>
                <?php if (can("p_29")): ?>
                    <a class="nav-link" href="/grn-payments">GRN Payments</a>
                <?php endif; ?>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/grns">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="grn_id">GRN ID</label>
                            <input class="input" id="grn_id" name="grn_id" value="<?= htmlspecialchars((string) ($filters["grn_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="shop_id">Adding Shop</label>
                            <select class="input" id="shop_id" name="shop_id" <?= (int) (($auth["shop_id"] ?? 0)) > 0 ? "disabled" : "" ?>>
                                <?php if ((int) (($auth["shop_id"] ?? 0)) === 0): ?>
                                    <option value="-1" <?= (int) ($filters["shop_id"] ?? -1) < 0 ? "selected" : "" ?>>Any Shop</option>
                                <?php endif; ?>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int) ($shop["shopid"] ?? 0) ?>" <?= (int) ($filters["shop_id"] ?? -1) === (int) ($shop["shopid"] ?? 0) ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int) (($auth["shop_id"] ?? 0)) > 0): ?>
                                <input type="hidden" name="shop_id" value="<?= (int) ($filters["shop_id"] ?? 0) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label for="supplier">Supplier Name</label>
                            <input class="input" id="supplier" name="supplier" value="<?= htmlspecialchars((string) ($filters["supplier"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="item_name">Item Name</label>
                            <input class="input" id="item_name" name="item_name" value="<?= htmlspecialchars((string) ($filters["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="imei">IMEI</label>
                            <input class="input" id="imei" name="imei" value="<?= htmlspecialchars((string) ($filters["imei"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="start_date">Start Date</label>
                            <input class="input" type="date" id="start_date" name="start_date" value="<?= htmlspecialchars((string) ($filters["start_date"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="end_date">End Date</label>
                            <input class="input" type="date" id="end_date" name="end_date" value="<?= htmlspecialchars((string) ($filters["end_date"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Find Matched Results</button>
                        <a class="btn" href="/grns" style="background:#eef2f5; color:#163041;">Reset Form</a>
                    </div>
                </form>
            </section>

            <?php if ($results === []): ?>
                <section class="card">
                    <p class="muted" style="margin:0;">No matched GRN records were found for the current filters.</p>
                </section>
            <?php else: ?>
                <?php foreach ($results as $index => $grn): ?>
                    <section class="card" style="margin-bottom:18px;">
                        <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:start; margin-bottom:16px;">
                            <div>
                                <div><strong><?= htmlspecialchars((string) ($grn["grn_refno"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong></div>
                                <div class="muted"><?= htmlspecialchars((string) ($grn["operation_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div class="muted">Result #<?= $index + 1 ?></div>
                                <a href="/print/grn-label?docid=<?= urlencode((string) ($grn["grn_refno"] ?? "")) ?>" target="_blank" class="btn" style="margin-top:8px; padding:4px 8px; font-size:12px; background:#eef2f5; color:#163041;">
                                    Print GRN Label
                                </a>
                            </div>
                        </div>

                        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom:16px;">
                            <div>
                                <div class="muted">Supplier</div>
                                <strong><?= htmlspecialchars((string) ($grn["suppler_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">GRN Total</div>
                                <strong><?= htmlspecialchars($formatMoney($grn["amount"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Adding Shop</div>
                                <strong><?= htmlspecialchars((string) ($grn["grn_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                            <div>
                                <div class="muted">Operator</div>
                                <strong><?= htmlspecialchars((string) (($grn["operator_name"] ?? "") !== "" ? $grn["operator_name"] : ($grn["operator_username"] ?? "")), ENT_QUOTES, "UTF-8") ?></strong>
                            </div>
                        </div>

                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Category</th>
                                        <th style="padding:12px;">Item Name</th>
                                        <th style="padding:12px;">IMEI</th>
                                        <th style="padding:12px;">Qty</th>
                                        <th style="padding:12px;">Cost Price</th>
                                        <th style="padding:12px;">Sell Price</th>
                                        <th style="padding:12px;">Low Price</th>
                                        <th style="padding:12px;">Other Price</th>
                                        <th style="padding:12px;">Shop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($grn["items"] ?? []) as $item): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["item_category"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["imei_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) ($item["item_qty"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_costpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_sellpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_lowpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["item_otherpri"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["stock_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
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
