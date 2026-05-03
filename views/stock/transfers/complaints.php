<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Stock Error Handling</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `transfer_error_correct.php` and `c_trans_errcorrect.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card">
                <?php if ($transfers === []): ?>
                    <p class="muted" style="margin:0;">No stock transfer complaints are waiting for administrator action.</p>
                <?php else: ?>
                    <div class="stack">
                        <?php foreach ($transfers as $transfer): ?>
                            <section class="card" style="border:1px solid var(--border);">
                                <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                                    <div>
                                        <div class="tag">Transfer ID <?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                        <p style="margin:12px 0 0;">
                                            Transfer Status: <strong><?= htmlspecialchars((string) ($transfer["status_label"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Complaint Status: <strong><?= htmlspecialchars((string) ($transfer["complaint_status_label"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Complaint User: <strong><?= htmlspecialchars((string) ($transfer["complain_user_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Complaint Time: <strong><?= htmlspecialchars((string) ($transfer["complaint_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong><br>
                                            Complaint Reason: <strong><?= htmlspecialchars((string) ($transfer["comp_reason"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                                        </p>
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

                                <form method="POST" action="/stock/transfers/complaints/<?= rawurlencode((string) ($transfer["trans_id"] ?? "")) ?>" style="margin-top:16px;">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <div class="grid" style="grid-template-columns: minmax(320px, 1fr) 220px auto; align-items:end;">
                                        <div class="form-row">
                                            <label for="recovery_note_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Recovery Note</label>
                                            <input class="input" id="recovery_note_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>" name="recovery_note" value="<?= htmlspecialchars((string) ($transfer["recover_note"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                                        </div>
                                        <div class="form-row">
                                            <label for="recovery_action_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Action</label>
                                            <select class="input" id="recovery_action_<?= htmlspecialchars((string) ($transfer["trans_id"] ?? ""), ENT_QUOTES, "UTF-8") ?>" name="recovery_action">
                                                <option value="release">Release Transfer Stock</option>
                                                <option value="discard">Discard Transfer Stock</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-primary" type="submit">Update Complaint</button>
                                    </div>
                                </form>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
