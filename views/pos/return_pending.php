<?php
$items = is_array($items ?? null) ? $items : [];
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Pending Return Activities</div>
            <div class="muted" style="color: #b8c6cf;">Return requests created from bill return are listed here until the follow-up activity flow is completed.</div>
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
                <a class="nav-link active" href="/pos/returns/pending">Pending Returns</a>
                <a class="nav-link" href="/dashboard">Back to Dashboard</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <?php if ($items === []): ?>
                <section class="card">
                    <p class="muted">No pending return activities were found for the current shop.</p>
                </section>
            <?php else: ?>
                <section class="card">
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                    <th style="padding:12px;">Bill ID</th>
                                    <th style="padding:12px;">Alter Time</th>
                                    <th style="padding:12px;">Reason</th>
                                    <th style="padding:12px;">Items Pending</th>
                                    <th style="padding:12px;">Return Value</th>
                                    <th style="padding:12px;">Recorded</th>
                                    <th style="padding:12px;">Open</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr style="border-bottom:1px solid #edf1f4;">
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($item["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;"><?= (int) ($item["alter_times"] ?? 0) ?></td>
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($item["alter_reason"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;"><?= (int) ($item["item_count"] ?? 0) ?></td>
                                        <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($item["total_return_value"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px;"><?= htmlspecialchars((string) ($item["record_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td style="padding:12px; display:flex; gap:8px; flex-wrap:wrap;">
                                            <a class="btn btn-primary" href="/pos/returns/pending/<?= htmlspecialchars((string) ($item["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/<?= (int) ($item["alter_times"] ?? 0) ?>">Process</a>
                                            <a class="btn" href="/pos/bills/<?= htmlspecialchars((string) ($item["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns" style="background:#eef2f5; color:#163041;">History</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>
