<?php $isRecharge = (int) $item["used_type"] === 3; ?>

<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Edit Item</div>
            <div class="muted" style="color: #b8c6cf;">Category and control mode stay read-only, matching the legacy edit rules</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/catalog/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <section class="card" style="max-width: 960px;">
                <h1 style="margin-top:0;">Item #<?= (int) $item["item_id"] ?></h1>
                <form method="POST" action="/items/<?= (int) $item["item_id"] ?>">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                        <div class="form-row">
                            <label for="item_name">Item Name</label>
                            <input class="input" id="item_name" name="item_name" value="<?= htmlspecialchars((string) $item["item_name"], ENT_QUOTES, "UTF-8") ?>" required>
                        </div>

                        <div class="form-row">
                            <label>Item Category</label>
                            <input class="input" value="<?= htmlspecialchars((string) ($item["category_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>" readonly>
                        </div>

                        <div class="form-row">
                            <label>System Controlled By</label>
                            <input class="input" value="<?= htmlspecialchars($typeLabel, ENT_QUOTES, "UTF-8") ?>" readonly>
                        </div>

                        <?php if ($isRecharge): ?>
                            <div class="form-row">
                                <label>Operator</label>
                                <input class="input" value="<?= htmlspecialchars((string) ($item["operator_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>" readonly>
                            </div>

                            <div class="form-row">
                                <label for="card_remark">Remark</label>
                                <input class="input" id="card_remark" name="card_remark" value="<?= htmlspecialchars((string) ($item["card_remark"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="muted" style="margin:18px 0;">
                        The migrated edit flow only changes item name and recharge remark. Category and stock control mode remain fixed after creation.
                    </p>

                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a class="btn" href="/items" style="background:#eef2f5; color:#163041;">Cancel</a>
                        <button class="btn btn-primary" type="submit">Update Item</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
