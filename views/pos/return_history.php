<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$history = is_array($history ?? null) ? $history : [];
$bill = is_array($bill ?? null) ? $bill : [];
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Bill Return History</div>
            <div class="muted" style="color: #b8c6cf;">Legacy alter-bill records for this POS bill are now visible from committed MVC bill data.</div>
        </div>
    </header>

    <div class="shell-grid">
        <aside class="sidebar">
            <h3>POS</h3>
            <div class="nav-group">
                <a class="nav-link" href="/pos">Current Bill</a>
                <?php if (can("p_31")): ?>
                    <a class="nav-link" href="/pos/bills/today">Daily Bills</a>
                <?php endif; ?>
                <?php if (can("p_32")): ?>
                    <a class="nav-link" href="/pos/bills/search">Find Bill</a>
                <?php endif; ?>
                <?php if (can("p_34")): ?>
                    <a class="nav-link" href="/pos/returns/pending">Pending Returns</a>
                <?php endif; ?>
                <a class="nav-link active" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns">Return History</a>
                <a class="nav-link" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Back To Receipt</a>
            </div>
        </aside>

        <main class="page">
            <section class="card" style="margin-bottom:18px;">
                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <div class="muted">Bill ID</div>
                        <strong><?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Customer</div>
                        <strong><?= htmlspecialchars((string) ($bill["customer_name"] ?? "Cash Customer"), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                    <div>
                        <div class="muted">Bill Total</div>
                        <strong><?= htmlspecialchars($formatMoney($bill["totalbill"] ?? 0), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>
            </section>

            <?php if ($history === []): ?>
                <section class="card">
                    <p class="muted">No return history is recorded for this bill yet.</p>
                </section>
            <?php else: ?>
                <?php foreach ($history as $event): ?>
                    <section class="card" style="margin-bottom:18px;">
                        <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:start; margin-bottom:16px;">
                            <div>
                                <div><strong>Alter Event #<?= (int) ($event["alter_times"] ?? 0) ?></strong></div>
                                <div class="muted"><?= htmlspecialchars((string) ($event["record_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <div class="muted">Reason: <?= htmlspecialchars((string) ($event["alter_reason"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                        </div>

                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Return Item</th>
                                        <th style="padding:12px;">Code</th>
                                        <th style="padding:12px;">Return Qty</th>
                                        <th style="padding:12px;">Return Value</th>
                                        <th style="padding:12px;">Status</th>
                                        <th style="padding:12px;">Activity Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($event["items"] ?? []) as $item): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($item["imei_part_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= (int) ($item["return_count"] ?? 0) ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["return_sale"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;">
                                                <?php
                                                $activity = (int) ($item["activity"] ?? 0);
                                                echo match ($activity) {
                                                    0 => "Pending Activity",
                                                    1 => "Processed",
                                                    2 => "Credit Issued",
                                                    default => "Unknown",
                                                };
                                                ?>
                                            </td>
                                            <td style="padding:12px;">
                                                <?php if (is_array($item["activity_info"] ?? null)): ?>
                                                    <?php $info = $item["activity_info"]; ?>
                                                    <?php if ((int) ($info["type"] ?? -1) === 0): ?>
                                                        Money Return: <?= htmlspecialchars($formatMoney($info["return_money"] ?? 0), ENT_QUOTES, "UTF-8") ?>
                                                    <?php elseif ((int) ($info["type"] ?? -1) === 3): ?>
                                                        Credit Return: <?= htmlspecialchars($formatMoney($info["return_money"] ?? 0), ENT_QUOTES, "UTF-8") ?>
                                                    <?php else: ?>
                                                        Replace Item: <?= htmlspecialchars((string) ($info["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                                        <?php if (!empty($info["imei_part_no"])): ?>
                                                            (<?= htmlspecialchars((string) $info["imei_part_no"], ENT_QUOTES, "UTF-8") ?>)
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="muted">Not processed yet</span>
                                                <?php endif; ?>
                                            </td>
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
