<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Shop List</div>
            <div class="muted" style="color: #b8c6cf;">Legacy shop list and edit navigation migrated to MVC</div>
        </div>
        <?php if (can("p_70")): ?>
            <a class="btn btn-ghost" href="/settings/shops/create">Add Shop</a>
        <?php endif; ?>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/settings/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Shop ID</th>
                            <th style="padding:12px;">Shop Name</th>
                            <th style="padding:12px;">Info Name</th>
                            <th style="padding:12px;">Address</th>
                            <th style="padding:12px;">Telephone</th>
                            <th style="padding:12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shops as $shop): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= (int) $shop["shopid"] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $shop["shopname"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $shop["shop_info_name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $shop["shopaddress"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $shop["shop_tel_1"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;">
                                    <a class="btn btn-primary" href="/settings/shops/<?= (int) $shop["shopid"] ?>/edit">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
