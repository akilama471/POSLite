<?php
$nameFilter = (string) ($filters["name"] ?? "");
$mobileFilter = (string) ($filters["mobile"] ?? "");
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Customers</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_customer.php`, `edit_customer.php`, `c_mancus_delete.php`, and `c_mancus_recover.php`</div>
        </div>
        <?php if (can("p_36")): ?>
            <a class="btn btn-ghost" href="/customers/create">Add Customer</a>
        <?php endif; ?>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <h1 style="margin-top:0;">Search customers</h1>
                <form method="GET" action="/customers">
                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
                        <div class="form-row">
                            <label for="name">Customer Name</label>
                            <input class="input" id="name" name="name" value="<?= htmlspecialchars($nameFilter, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div class="form-row">
                            <label for="mobile">Mobile Number</label>
                            <input class="input" id="mobile" name="mobile" value="<?= htmlspecialchars($mobileFilter, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <button class="btn btn-primary" type="submit">Search</button>
                            <a class="btn" href="/customers" style="background:#eef2f5; color:#163041;">Reset</a>
                        </div>
                    </div>
                </form>
            </section>

            <section class="stack">
                <?php foreach ($customers as $customer): ?>
                    <?php $isActive = (int) ($customer["status"] ?? 0) === 1; ?>
                    <article class="card">
                        <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start;">
                            <div class="stack" style="gap:8px;">
                                <div style="font-size:1.1rem; font-weight:700;">
                                    <?= htmlspecialchars((string) $customer["cus_name"], ENT_QUOTES, "UTF-8") ?>
                                </div>
                                <div class="muted">Customer ID: <?= (int) $customer["recordid"] ?> | Mobile: <?= htmlspecialchars((string) $customer["cus_mobile"], ENT_QUOTES, "UTF-8") ?></div>
                                <div>Status: <strong><?= $isActive ? "Active Customer" : "Deleted Customer" ?></strong></div>
                                <div>Address: <?= htmlspecialchars((string) ($customer["cus_addr"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>Email: <?= htmlspecialchars((string) ($customer["cus_emai"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>NIC: <?= htmlspecialchars((string) ($customer["cus_nic"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>Tel: <?= htmlspecialchars((string) ($customer["cus_tpno"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>Birthday: <?= htmlspecialchars((string) ($customer["cus_bday"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>Remark: <?= htmlspecialchars((string) ($customer["cus_remark"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <div>Registered: <?= htmlspecialchars((string) ($customer["add_time"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                            </div>

                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <a class="btn btn-primary" href="/customers/<?= (int) $customer["recordid"] ?>/edit">Edit</a>
                                <form method="POST" action="/customers/<?= (int) $customer["recordid"] ?>/status" onsubmit="return confirm('Update customer status?');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <input type="hidden" name="action" value="<?= $isActive ? "delete" : "recover" ?>">
                                    <button
                                        class="btn"
                                        type="submit"
                                        style="<?= $isActive ? "background:#fbe4de; color:#8f2d15;" : "background:#e8f6ef; color:#146b4f;" ?>"
                                    >
                                        <?= $isActive ? "Delete Customer" : "Recover Customer" ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if ($customers === []): ?>
                    <section class="card">
                        <p class="muted" style="margin:0;">No matching customers found.</p>
                    </section>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
