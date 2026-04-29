<?php
$categoryFilter = (string) ($filters["category"] ?? "");
$nameFilter = (string) ($filters["name"] ?? "");
$codeFilter = (string) ($filters["code"] ?? "");
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Search Items</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `search_item.php` and `c_itm_serch_func.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
            <?php endforeach; ?>

            <section class="card" style="margin-bottom:18px;">
                <h1 style="margin-top:0;">Search item details</h1>
                <form method="GET" action="/items/search">
                    <div class="grid" style="grid-template-columns: minmax(280px, 1fr) minmax(280px, 1fr);">
                        <div class="stack" style="gap:16px;">
                            <div class="form-row">
                                <label for="category">Item Category</label>
                                <select class="input" id="category" name="category" onchange="loadCategoryItems(this.value)">
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>" <?= $categoryFilter === (string) $category["catname"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars((string) $category["catname"], ENT_QUOTES, "UTF-8") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label for="name">Item Name</label>
                                <input class="input" id="name" name="name" list="allitems" value="<?= htmlspecialchars($nameFilter, ENT_QUOTES, "UTF-8") ?>">
                                <datalist id="allitems"></datalist>
                            </div>
                        </div>

                        <div class="stack" style="gap:16px;">
                            <div class="form-row">
                                <label for="code">Barcode No#</label>
                                <input class="input" id="code" name="code" value="<?= htmlspecialchars($codeFilter, ENT_QUOTES, "UTF-8") ?>">
                            </div>

                            <p class="muted" style="margin:0;">Search by item name or barcode. IMEI-only lookup is still handled through stock rows when the code matches an IMEI value.</p>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
                        <button class="btn btn-primary" type="submit">Search Details</button>
                        <a class="btn" href="/items/search" style="background:#eef2f5; color:#163041;">Reset Details</a>
                    </div>
                </form>
            </section>

            <section class="stack">
                <?php foreach ($results as $result): ?>
                    <section class="card" style="overflow-x:auto;">
                        <h2 class="section-title"><?= htmlspecialchars((string) $result["title"], ENT_QUOTES, "UTF-8") ?></h2>

                        <?php if ($result["rows"] === []): ?>
                            <p class="muted" style="margin:0;">No stock details found.</p>
                        <?php elseif ($result["type"] === "barcode"): ?>
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Item Name</th>
                                        <th style="padding:12px;">Price</th>
                                        <th style="padding:12px;">Cost</th>
                                        <th style="padding:12px;">Other Price</th>
                                        <th style="padding:12px;">Supplier</th>
                                        <th style="padding:12px;">In Stock</th>
                                        <th style="padding:12px;">Barcode</th>
                                        <th style="padding:12px;">GRN ID</th>
                                        <th style="padding:12px;">Shop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result["rows"] as $row): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_name"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_sell_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_cost_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_other_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;" title="<?= htmlspecialchars((string) ($row["supplier_name"] ?? "Not Found"), ENT_QUOTES, "UTF-8") ?>"><?= (int) ($row["supplier_id"] ?? 0) ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["stock_current"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["gen_refno"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["grn_refno"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($row["shop_info_name"] ?? "Not Found"), ENT_QUOTES, "UTF-8") ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($result["type"] === "imei"): ?>
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Item Name</th>
                                        <th style="padding:12px;">Price</th>
                                        <th style="padding:12px;">Cost</th>
                                        <th style="padding:12px;">Other Price</th>
                                        <th style="padding:12px;">Supplier</th>
                                        <th style="padding:12px;">IMEI</th>
                                        <th style="padding:12px;">Color</th>
                                        <th style="padding:12px;">GRN ID</th>
                                        <th style="padding:12px;">Shop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result["rows"] as $row): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_name"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_sell_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_cost_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["item_other_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;" title="<?= htmlspecialchars((string) ($row["supplier_name"] ?? "Not Found"), ENT_QUOTES, "UTF-8") ?>"><?= (int) ($row["supplier_id"] ?? 0) ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["imei_no"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["item_color"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["grn_refno"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($row["shop_info_name"] ?? "Not Found"), ENT_QUOTES, "UTF-8") ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid var(--border);">
                                        <th style="padding:12px;">Item Name</th>
                                        <th style="padding:12px;">Price</th>
                                        <th style="padding:12px;">Cost</th>
                                        <th style="padding:12px;">Other Price</th>
                                        <th style="padding:12px;">In Stock</th>
                                        <th style="padding:12px;">GRN ID</th>
                                        <th style="padding:12px;">Shop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result["rows"] as $row): ?>
                                        <tr style="border-bottom:1px solid #edf1f4;">
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["card_name"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["sell_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["cost_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) $row["other_price"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["current_stock"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px; text-align:center;"><?= htmlspecialchars((string) $row["grn_refno"], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="padding:12px;"><?= htmlspecialchars((string) ($row["shop_info_name"] ?? "Not Found"), ENT_QUOTES, "UTF-8") ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <?php if ($results === [] && ($nameFilter !== "" || $codeFilter !== "")): ?>
                    <section class="card">
                        <p class="muted" style="margin:0;">No stock details found.</p>
                    </section>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
async function loadCategoryItems(categoryName) {
    const dataList = document.getElementById("allitems");
    const nameInput = document.getElementById("name");

    dataList.innerHTML = "";
    nameInput.value = "";

    if (!categoryName) {
        return;
    }

    const response = await fetch("/api/items/by-category?category=" + encodeURIComponent(categoryName), {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    });

    if (!response.ok) {
        return;
    }

    const items = await response.json();
    for (const itemName of items) {
        const option = document.createElement("option");
        option.value = itemName;
        dataList.appendChild(option);
    }
}

<?php if ($categoryFilter !== ""): ?>
loadCategoryItems(<?= json_encode($categoryFilter) ?>);
<?php endif; ?>
</script>
