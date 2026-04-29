<?php $searchTerm = (string) $search; ?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Edit Items</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_item_e.php` and `c_manitem_e_getdata.php`</div>
        </div>
        <?php if (can("p_15")): ?>
            <a class="btn btn-ghost" href="/items/create">Add Item</a>
        <?php endif; ?>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <h1 style="margin-top:0;">Search item</h1>
                <form method="GET" action="/items">
                    <div class="grid" style="grid-template-columns: minmax(280px, 1fr) auto auto; align-items:end;">
                        <div class="form-row">
                            <label for="name">Item Name</label>
                            <input class="input" id="name" name="name" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <button class="btn btn-primary" type="submit">Search</button>
                        <a class="btn" href="/items" style="background:#eef2f5; color:#163041;">Reset</a>
                    </div>
                </form>
            </section>

            <section class="card" style="overflow-x:auto;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border);">
                            <th style="padding:12px;">Item ID</th>
                            <th style="padding:12px;">Item Name</th>
                            <th style="padding:12px;">Category</th>
                            <th style="padding:12px;">Control Type</th>
                            <th style="padding:12px;">Operator</th>
                            <th style="padding:12px;">Remark</th>
                            <th style="padding:12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom:1px solid #edf1f4;">
                                <td style="padding:12px;"><?= (int) $item["item_id"] ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) $item["item_name"], ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($item["category_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars(Item::typeLabel((int) $item["used_type"]), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($item["operator_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars((string) ($item["card_remark"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                <td style="padding:12px;">
                                    <a class="btn btn-primary" href="/items/<?= (int) $item["item_id"] ?>/edit">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($items === []): ?>
                    <p class="muted" style="margin:16px 0 0;">No matching items found.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
