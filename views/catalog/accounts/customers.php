<?php
$formatMoney = static function (mixed $value): string {
    $amount = (float) $value;
    return "Rs. " . number_format($amount, 2, ".", ",");
};
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Customer Account Balance</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `customer_accounts.php` and `c_cus_accounts.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <p class="section-copy" style="margin:0 0 14px;">
                    Payment remaining from each customer is shown here. The default list matches the legacy page by showing only positive balances; selecting a specific customer will load that customer even if the balance is zero or negative.
                </p>

                <form method="GET" action="/customer-accounts">
                    <div class="grid" style="grid-template-columns: minmax(280px, 1fr) auto auto; align-items:end;">
                        <div class="form-row">
                            <label for="name">Select Customer Name</label>
                            <input class="input" id="name" name="name" list="customer_list" value="<?= htmlspecialchars((string) $selectedName, ENT_QUOTES, "UTF-8") ?>">
                            <datalist id="customer_list">
                                <?php foreach ($customerOptions as $customer): ?>
                                    <option value="<?= htmlspecialchars((string) $customer["cus_name"], ENT_QUOTES, "UTF-8") ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <button class="btn btn-primary" type="submit">Load Customer Data</button>
                        <a class="btn" href="/customer-accounts" style="background:#eef2f5; color:#163041;">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">ID No#</th>
                            <th style="padding:12px;">Customer Name</th>
                            <th style="padding:12px;">Customer Address</th>
                            <th style="padding:12px;">Contact No#</th>
                            <th style="padding:12px;">Account Balance</th>
                            <th style="padding:12px;">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= (int) $customer["recordid"] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $customer["cus_name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $customer["cus_addr"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $customer["cus_mobile"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px; text-align:right;"><?= htmlspecialchars($formatMoney($customer["accbalance"] ?? 0), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;">
                                    <?php if (can("p_41")): ?>
                                        <a class="btn btn-primary" href="/customer-payments?customer=<?= (int) $customer["recordid"] ?>">Pay</a>
                                    <?php else: ?>
                                        <span class="muted">No access</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($customers === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No matching customer balances found.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
