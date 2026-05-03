<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Transfer Received</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `transfer_received.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card">
                <?php if ($transfers === []): ?>
                    <p class="muted" style="margin:0;">No received stock is waiting for acceptance in this shop.</p>
                <?php else: ?>
                    <div class="stack">
                        <?php foreach ($transfers as $transfer): ?>
                            <section class="card" style="border:1px solid var(--border);">
                                <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                                    <div>
                                        <div class="tag">Transfer ID <?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                        <p style="margin:12px 0 0;">
                                            Status: <strong>Received Stock. Waiting Accept.</strong><br>
                                            From Shop: <strong><?= htmlspecialchars((string) ($transfer["from_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Processed By: <strong><?= htmlspecialchars((string) ($transfer["processed_operator_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Record Time: <strong><?= htmlspecialchars((string) ($transfer["record_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                                        </p>
                                    </div>
                                    <div>
                                        <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:flex-end;">
                                            <form method="POST" action="/stock/transfers/received/<?= rawurlencode((string) ($transfer["trans_id"] ?? "")) ?>/accept">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <button class="btn btn-primary" type="submit">Accept Received Items</button>
                                            </form>
                                            <form method="POST" action="/stock/transfers/received/<?= rawurlencode((string) ($transfer["trans_id"] ?? "")) ?>/complain" style="min-width:280px;">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <div class="form-row" style="margin:0;">
                                                    <label for="complaint_reason_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Complaint Reason</label>
                                                    <input class="input" id="complaint_reason_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>" name="complaint_reason" maxlength="400">
                                                </div>
                                                <button class="btn" type="submit" style="margin-top:8px; background:#ffe8e2; color:#8a2d13;">Raise Complaint</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div style="overflow:auto; margin-top:16px;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Item Code</th>
                                                <th>Transfer From</th>
                                                <th>Transfer To</th>
                                                <th>Qty</th>
                                                <th>Billed Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transfer["logs"] as $log): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) ($log["Item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                    <td><?= htmlspecialchars((string) ($log["code"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                    <td><?= htmlspecialchars((string) ($log["from_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                    <td><?= htmlspecialchars((string) ($log["to_shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                                    <td><?= (int) ($log["stock_count"] ?? 0) ?></td>
                                                    <td>Rs. <?= number_format((float) ($log["transfer_value"] ?? 0), 2, ".", ",") ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
