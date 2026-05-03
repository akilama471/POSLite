<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Stock Adjustment</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `stock_adjust.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid">
                <section class="card">
                    <h2>Search Stock to Adjust</h2>
                    <form method="POST" action="/stock/adjust/search" class="stack">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="form-row">
                            <label for="search_mode">Search By</label>
                            <select class="input" id="search_mode" name="search_mode">
                                <option value="code" <?= ($search["mode"] ?? "code") === "code" ? "selected" : "" ?>>Barcode</option>
                                <option value="name" <?= ($search["mode"] ?? "code") === "name" ? "selected" : "" ?>>Item Name</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="search_query">Query</label>
                            <input class="input" id="search_query" name="search_query" value="<?= htmlspecialchars((string) ($search["query"] ?? ""), ENT_QUOTES, "UTF-8") ?>" required autofocus autocomplete="off">
                        </div>
                        <div style="display:flex; justify-content:flex-end;">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </section>
            </div>

            <?php if (!empty($candidates)): ?>
                <section class="card" style="margin-top: 24px;">
                    <h2>Matching Stock Records</h2>
                    <div style="overflow:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Item Code</th>
                                    <th>Shop</th>
                                    <th>Current Qty</th>
                                    <th>Adjustment Diff (±)</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($candidate["item_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($candidate["item_code"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><?= htmlspecialchars((string) ($candidate["shop_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                                        <td><strong><?= (int) ($candidate["stock_current"] ?? 0) ?></strong></td>
                                        <td colspan="3">
                                            <form method="POST" action="/stock/adjust/submit" style="display:flex; gap:12px; align-items:center;">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <input type="hidden" name="object_type" value="<?= (int) ($candidate["object_type"] ?? 0) ?>">
                                                <input type="hidden" name="row_id" value="<?= (int) ($candidate["row_id"] ?? 0) ?>">
                                                
                                                <input type="number" class="input" name="qty_diff" placeholder="e.g. -1 or 2" required style="width: 120px;" step="1">
                                                <input type="text" class="input" name="reason" placeholder="Reason for adjustment" required style="flex-grow: 1;">
                                                <button class="btn btn-warning" type="submit">Apply Adjust</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>
</div>
