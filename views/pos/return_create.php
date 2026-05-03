<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
$bill = is_array($bill ?? null) ? $bill : [];
$lines = is_array($lines ?? null) ? $lines : [];
$hasPending = (bool) ($hasPending ?? false);
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Create Bill Return</div>
            <div class="muted" style="color: #b8c6cf;">Stage a return request from a committed POS bill before the follow-up activity flow is processed.</div>
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
                <a class="nav-link active" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns/create">Create Return</a>
                <a class="nav-link" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns">Return History</a>
                <a class="nav-link" href="/pos/receipts/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>">Back To Receipt</a>
            </div>
        </aside>

        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

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
                    <div>
                        <div class="muted">Billed At</div>
                        <strong><?= htmlspecialchars((string) ($bill["billaddedtime"] ?? ""), ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </div>
            </section>

            <?php if ($hasPending): ?>
                <section class="card">
                    <p class="muted">This bill already has a pending return activity queue. Finish that workflow before creating another return request.</p>
                    <div style="margin-top:16px;">
                        <a class="btn btn-primary" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns">Open Return History</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="card">
                    <form method="POST" action="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <div style="overflow-x:auto; margin-bottom:18px;">
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Item</th>
                                        <th style="padding:12px;">Code</th>
                                        <th style="padding:12px;">Sold Qty</th>
                                        <th style="padding:12px;">Sale Price</th>
                                        <th style="padding:12px;">Return Qty</th>
                                        <th style="padding:12px;">Collect Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lines as $line): ?>
                                        <?php
                                        $rowId = (int) ($line["recordid"] ?? 0);
                                        $soldQty = (int) ($line["qty"] ?? 0);
                                        $isImei = (int) ($line["type"] ?? 0) === 2;
                                        ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($line["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($line["imei_part_no"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= $soldQty ?></td>
                                            <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($line["sale_price"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; width:140px;">
                                                <input type="hidden" name="entries[<?= $rowId ?>][row_id]" value="<?= $rowId ?>">
                                                <input
                                                    class="input"
                                                    type="number"
                                                    min="0"
                                                    max="<?= $soldQty ?>"
                                                    step="1"
                                                    name="entries[<?= $rowId ?>][return_qty]"
                                                    value="0"
                                                    <?= $isImei ? 'data-imei-line="1"' : "" ?>
                                                >
                                            </td>
                                            <td style="padding:12px; min-width:180px;">
                                                <select class="input" name="entries[<?= $rowId ?>][return_type]">
                                                    <option value="1">Re-Sell</option>
                                                    <option value="2">Discard</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-row" style="margin-bottom:18px;">
                            <label for="alter_reason">Return Reason</label>
                            <textarea class="input" id="alter_reason" name="alter_reason" rows="3" required></textarea>
                        </div>

                        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                            <button class="btn btn-primary" type="submit">Create Return Request</button>
                            <a class="btn" href="/pos/bills/<?= htmlspecialchars((string) ($bill["billnumber"] ?? ""), ENT_QUOTES, "UTF-8") ?>/returns" style="background:#eef2f5; color:#163041;">Back To Return History</a>
                        </div>
                    </form>
                </section>

                <script>
                    document.addEventListener("input", function (event) {
                        const target = event.target;
                        if (!(target instanceof HTMLInputElement) || target.dataset.imeiLine !== "1") {
                            return;
                        }

                        if (parseInt(target.value || "0", 10) > 1) {
                            target.value = "1";
                        }
                    });
                </script>
            <?php endif; ?>
        </main>
    </div>
</div>
