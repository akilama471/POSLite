<?php
$formatMoney = static function (mixed $value): string {
    return "Rs. " . number_format((float) $value, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Supplier Cash Credit Info</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `supplier_chashcredit_list.php`, `c_supp_cashcredit_list.php`, and `c_supp_cashcredit_upd.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <p class="section-copy" style="margin:0 0 14px;">
                    When stock is returned to a supplier, the amount pending reimbursement is shown here. This page is informational; use the supplier payment page to settle balances.
                </p>
                <form method="POST" action="/supplier-credit-balances/refresh">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <button class="btn btn-primary" type="submit">Refresh Credit Balances</button>
                </form>
            </section>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Supplier ID</th>
                            <th style="padding:12px;">Supplier Name</th>
                            <th style="padding:12px;">Supplier Address</th>
                            <th style="padding:12px;">Supplier Tp</th>
                            <th style="padding:12px;">Cash Credit Balance</th>
                            <th style="padding:12px;">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= (int) $supplier["supplierid"] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_address"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_mobile"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($supplier["cash_credit_balance"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;">
                                    <?php if (can("p_29")): ?>
                                        <a class="btn btn-primary" href="/supplier-payments?supplier=<?= (int) $supplier["supplierid"] ?>">Pay</a>
                                    <?php else: ?>
                                        <span class="muted">No access</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
