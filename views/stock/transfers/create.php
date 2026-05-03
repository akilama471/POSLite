<?php
$draft = is_array($draft ?? null) ? $draft : ["target_shop_id" => 0, "target_shop_name" => "", "lines" => []];
$search = is_array($search ?? null) ? $search : ["mode" => "code", "query" => ""];
$candidates = is_array($candidates ?? null) ? $candidates : [];
?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Stock Transfer</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `transfer_product_new.php` and `c_trans_stock.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="margin-bottom:18px;">
                <form method="POST" action="/stock/transfers/create/target">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: minmax(280px, 360px) auto; align-items:end;">
                        <div class="form-row">
                            <label for="target_shop_id">Transfer To Shop</label>
                            <select class="input" id="target_shop_id" name="target_shop_id" required>
                                <option value="">Select target shop</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int) $shop["shopid"] ?>" <?= (int) ($draft["target_shop_id"] ?? 0) === (int) $shop["shopid"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars((string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Set Target Shop</button>
                    </div>
                </form>
            </section>

            <section class="card" style="margin-bottom:18px;">
                <form method="POST" action="/stock/transfers/create/search">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                    <div class="grid" style="grid-template-columns: 180px minmax(240px, 1fr) auto; align-items:end;">
                        <div class="form-row">
                            <label for="search_mode">Search Mode</label>
                            <select class="input" id="search_mode" name="search_mode">
                                <option value="code" <?= (string) ($search["mode"] ?? "") === "code" ? "selected" : "" ?>>Barcode / IMEI</option>
                                <option value="name" <?= (string) ($search["mode"] ?? "") === "name" ? "selected" : "" ?>>Item Name</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="search_query">Search Query</label>
                            <input class="input" id="search_query" name="search_query" value="<?= htmlspecialchars((string) ($search["query"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                        </div>
                        <button class="btn btn-primary" type="submit">Search Stock</button>
                    </div>
                </form>
            </section>

            <?php if ($candidates !== []): ?>
                <section class="card" style="margin-bottom:18px;">
                    <h3 style="margin-top:0;">Matched Stock</h3>
                    <div style="overflow:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Item Name</th>
                                    <th>Code</th>
                                    <th>Current Stock</th>
                                    <th>Cost</th>
                                    <th>Add</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?= (int) ($candidate["object_type"] ?? 0) === 1 ? "Barcode" : ((int) ($candidate["object_type"] ?? 0) === 2 ? "IMEI" : "Recharge") ?></td>
                                        <td><?= htmlspecialchars((string) ($candidate["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($candidate["item_code"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= (int) ($candidate["stock_current"] ?? 0) ?></td>
                                        <td>Rs. <?= number_format((float) ($candidate["part_cost"] ?? 0), 2, ".", ",") ?></td>
                                        <td>
                                            <form method="POST" action="/stock/transfers/create/lines" style="display:flex; gap:8px; align-items:center;">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <input type="hidden" name="object_type" value="<?= (int) ($candidate["object_type"] ?? 0) ?>">
                                                <input type="hidden" name="row_id" value="<?= (int) ($candidate["row_id"] ?? 0) ?>">
                                                <input class="input" type="number" name="trans_amount" min="1" max="<?= (int) ($candidate["stock_current"] ?? 0) ?>" value="<?= (int) ($candidate["object_type"] ?? 0) === 2 ? 1 : 1 ?>" style="width:88px;">
                                                <button class="btn btn-primary" type="submit">Add</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; margin-bottom:16px;">
                    <div>
                        <h3 style="margin:0;">Transfer Draft</h3>
                        <p class="muted" style="margin:8px 0 0;">
                            Target Shop:
                            <strong><?= htmlspecialchars((string) ($draft["target_shop_name"] ?? "Not selected"), ENT_QUOTES, "UTF-8") ?></strong>
                        </p>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <form method="POST" action="/stock/transfers/create/submit">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <button class="btn btn-primary" type="submit">Create Transfer</button>
                        </form>
                        <form method="POST" action="/stock/transfers/create/clear">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <button class="btn" type="submit" style="background:#eef2f5; color:#163041;">Clear Draft</button>
                        </form>
                    </div>
                </div>

                <div style="overflow:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Item Name</th>
                                <th>Code</th>
                                <th>Current Stock</th>
                                <th>Transfer Qty</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (($draft["lines"] ?? []) === []): ?>
                                <tr>
                                    <td colspan="7" class="muted">No stock lines are staged for transfer yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (($draft["lines"] ?? []) as $index => $line): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= (int) ($line["object_type"] ?? 0) === 1 ? "Barcode" : ((int) ($line["object_type"] ?? 0) === 2 ? "IMEI" : "Recharge") ?></td>
                                        <td><?= htmlspecialchars((string) ($line["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($line["item_code"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= (int) ($line["stock_current"] ?? 0) ?></td>
                                        <td>
                                            <form method="POST" action="/stock/transfers/create/lines/<?= $index ?>" style="display:flex; gap:8px; align-items:center;">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <input class="input" type="number" name="trans_amount" min="1" max="<?= (int) ($line["stock_current"] ?? 0) ?>" value="<?= (int) ($line["trans_amount"] ?? 0) ?>" style="width:88px;">
                                                <button class="btn" type="submit" style="background:#eef2f5; color:#163041;">Update</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" action="/stock/transfers/create/lines/<?= $index ?>/delete">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <button class="btn" type="submit" style="background:#ffe8e2; color:#8a2d13;">Remove</button>
                                            </form>
                                        </td>
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
