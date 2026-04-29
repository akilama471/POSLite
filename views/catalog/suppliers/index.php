<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Suppliers</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `add_supplier.php`, `supplier_list.php`, `c_supp_shw.php`, and `c_upd_supp_this.php`</div>
        </div>
        <?php if (can("p_25")): ?>
            <a class="btn btn-ghost" href="/suppliers/create">Add Supplier</a>
        <?php endif; ?>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Supplier ID</th>
                            <th style="padding:12px;">Name</th>
                            <th style="padding:12px;">Address</th>
                            <th style="padding:12px;">Mobile</th>
                            <th style="padding:12px;">Effective Date</th>
                            <th style="padding:12px;">Status</th>
                            <th style="padding:12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= (int) $supplier["supplierid"] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_address"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["supplier_mobile"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $supplier["eff_date"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= (int) ($supplier["supplier_status"] ?? 0) === 1 ? "Active" : "Inactive" ?></td>
                                <td style="padding:12px;">
                                    <a class="btn btn-primary" href="/suppliers/<?= (int) $supplier["supplierid"] ?>/edit">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
