<?php
$receipt = is_array($receipt ?? null) ? $receipt : null;
$currentPath = parse_url($_SERVER["REQUEST_URI"] ?? "/grn-payments", PHP_URL_PATH) ?: "/grn-payments";
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">GRN Payment</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `supplier_grn_payment.php` and `c_supplier_grn_payment.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <?php if ($receipt !== null): ?>
                <section class="card" style="margin-bottom:18px;">
                    <div class="tag">GRN Payment Recorded</div>
                    <p style="margin:14px 0 0;">
                        GRN: <strong><?= htmlspecialchars((string) $receipt["grn_refno"], ENT_QUOTES, "UTF-8") ?></strong><br>
                        Supplier: <strong><?= htmlspecialchars((string) $receipt["supplier_name"], ENT_QUOTES, "UTF-8") ?></strong><br>
                        Method: <strong><?= htmlspecialchars(ucfirst((string) $receipt["method"]), ENT_QUOTES, "UTF-8") ?></strong><br>
                        Amount: <strong>Rs. <?= number_format((float) $receipt["amount"], 2, ".", ",") ?></strong><br>
                        Remaining Due: <strong>Rs. <?= number_format((float) $receipt["due_amount"], 2, ".", ",") ?></strong>
                    </p>
                </section>
            <?php endif; ?>

            <section class="card" style="margin-bottom:18px;">
                <form method="GET" action="/grn-payments">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
                        <div class="form-row">
                            <label for="shop_id">Shop</label>
                            <select class="input" id="shop_id" name="shop_id" <?= (int) ($auth["shop_id"] ?? 0) > 0 ? "disabled" : "" ?>>
                                <option value="0">All Shops</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int) $shop["shopid"] ?>" <?= (int) $filters["shop_id"] === (int) $shop["shopid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ((int) ($auth["shop_id"] ?? 0) > 0): ?>
                                <input type="hidden" name="shop_id" value="<?= (int) $auth["shop_id"] ?>">
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label for="supplier_id">Supplier</label>
                            <select class="input" id="supplier_id" name="supplier_id">
                                <option value="0">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= (int) $supplier["supplierid"] ?>" <?= (int) $filters["supplier_id"] === (int) $supplier["supplierid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="grn_refno">GRN Ref</label>
                            <input class="input" id="grn_refno" name="grn_refno" value="<?= htmlspecialchars((string) $filters["grn_refno"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="payment_status">Status</label>
                            <select class="input" id="payment_status" name="payment_status">
                                <option value="due" <?= (string) $filters["payment_status"] === "due" ? "selected" : "" ?>>Due Only</option>
                                <option value="paid" <?= (string) $filters["payment_status"] === "paid" ? "selected" : "" ?>>Paid Only</option>
                                <option value="all" <?= (string) $filters["payment_status"] === "all" ? "selected" : "" ?>>All</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="from_date">From</label>
                            <input class="input" type="date" id="from_date" name="from_date" value="<?= htmlspecialchars((string) $filters["from_date"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="to_date">To</label>
                            <input class="input" type="date" id="to_date" name="to_date" value="<?= htmlspecialchars((string) $filters["to_date"], ENT_QUOTES, "UTF-8") ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Load GRN Payments</button>
                        <a class="btn" href="/grn-payments" style="background:#eef2f5; color:#163041;">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card">
                <div style="overflow:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Record Time</th>
                                <th>Shop</th>
                                <th>Supplier</th>
                                <th>Invoice / GRN</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Cash</th>
                                <th>Cheque</th>
                                <th>Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($results === []): ?>
                                <tr>
                                    <td colspan="10" class="muted">No GRN payment records matched the current filter.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row["record_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($row["supplier_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) (($row["inv_number"] ?? "") . " / " . ($row["grn_refno"] ?? "")), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>Rs. <?= number_format((float) ($row["grn_final_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td>
                                            <span class="tag" style="background:<?= (int) ($row["payment_status"] ?? 0) === 0 ? "#d7f5e8" : "#ffe0d5" ?>; color:#102a3d;">
                                                <?= (int) ($row["payment_status"] ?? 0) === 0 ? "Paid" : "Due" ?>
                                            </span>
                                        </td>
                                        <td>Rs. <?= number_format((float) ($row["cash_pay_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td>Rs. <?= number_format((float) ($row["chq_pay_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td>Rs. <?= number_format((float) ($row["due_amount"] ?? 0), 2, ".", ",") ?></td>
                                        <td><a class="btn btn-primary" href="/grn-payments/<?= (int) $row["record_id"] ?>">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
