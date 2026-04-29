<?php
$billNumber = (string) ($bill["billnumber"] ?? "");
$shopName = $shop["shop_info_name"] ?? $shop["shopname"] ?? "";
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Barcode Labels</div>
            <div class="muted" style="color: #b8c6cf;">Select sold lines and print counts for bill <?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?>.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">Current Bill</a>
                <a class="nav-link active" href="/pos/receipts/<?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?>/barcodes">Barcode Labels</a>
                <a class="nav-link" href="/pos/receipts/<?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?>">Back To Receipt</a>
            </div>
        </aside>

        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Bill ID</div>
                        <strong><?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Shop</div>
                        <strong><?= htmlspecialchars((string) $shopName, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>
            </section>

            <section class="card">
                <form method="POST" action="/pos/receipts/<?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?>/barcodes/print" target="_blank">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                    <th style="padding:12px;">Item Name</th>
                                    <th style="padding:12px;">Sale Qty</th>
                                    <th style="padding:12px;">Bill ID</th>
                                    <th style="padding:12px;">Part / IMEI Code</th>
                                    <th style="padding:12px;">Supplier ID</th>
                                    <th style="padding:12px;">Print Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($labelLines as $line): ?>
                                    <tr style="border-bottom:1px solid #edf1f4;">
                                        <td style="padding:12px;">
                                            <input type="hidden" name="item_name[]" value="<?= htmlspecialchars((string) $line["item_name"], ENT_QUOTES, "UTF-8") ?>">
                                            <?= htmlspecialchars((string) $line["item_name"], ENT_QUOTES, "UTF-8") ?>
                                        </td>
                                        <td style="padding:12px;"><?= (int) ($line["qty"] ?? 0) ?></td>
                                        <td style="padding:12px;"><?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;">
                                            <input type="hidden" name="code[]" value="<?= htmlspecialchars((string) $line["imei_part_no"], ENT_QUOTES, "UTF-8") ?>">
                                            <?= htmlspecialchars((string) $line["imei_part_no"], ENT_QUOTES, "UTF-8") ?>
                                        </td>
                                        <td style="padding:12px;">
                                            <input type="hidden" name="supplier_id[]" value="<?= htmlspecialchars((string) $line["supplier_id"], ENT_QUOTES, "UTF-8") ?>">
                                            <?= htmlspecialchars((string) $line["supplier_id"], ENT_QUOTES, "UTF-8") ?>
                                        </td>
                                        <td style="padding:12px;">
                                            <input class="input" type="number" name="print_count[]" min="0" step="1" value="0" style="max-width:96px; text-align:center;">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($labelLines === []): ?>
                        <p class="muted" style="margin:16px 0 0;">No sold lines were found for this bill.</p>
                    <?php else: ?>
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:18px;">
                            <button class="btn btn-primary" type="submit">Print Barcode Labels</button>
                            <a class="btn" href="/pos/receipts/<?= htmlspecialchars($billNumber, ENT_QUOTES, "UTF-8") ?>" style="background:#eef2f5; color:#163041;">Back To Receipt</a>
                        </div>
                    <?php endif; ?>
                </form>
            </section>
        </main>
    </div>
</div>
