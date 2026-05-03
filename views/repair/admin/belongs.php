<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Manage Customer Belongs</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `manage_customer_belongs.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/repair/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <section class="card stack">
                    <h2>Add New Belonging</h2>
                    <form method="POST" action="/repair/admin/belongs" class="stack" style="align-items: flex-end;">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        
                        <div class="form-row" style="width: 100%;">
                            <label for="name">Belong Name (e.g. Battery, Charger)</label>
                            <input class="input" id="name" name="name" required autofocus autocomplete="off">
                        </div>
                        
                        <button class="btn btn-primary" type="submit">Add Belonging</button>
                    </form>
                </section>

                <section class="card stack">
                    <h2>Existing Belongings</h2>
                    <div style="overflow-y: auto; max-height: 400px;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Belong Name</th>
                                    <th style="width: 140px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($belongs)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center muted">No belongings found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($belongs as $b): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $b['belong_name'], ENT_QUOTES, "UTF-8") ?></td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="editBelong(<?= (int) $b['recordid'] ?>, '<?= htmlspecialchars((string) $b['belong_name'], ENT_QUOTES, "UTF-8") ?>')">Edit</button>
                                                <form method="POST" action="/repair/admin/belongs/<?= (int) $b['recordid'] ?>/delete" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this belonging?');">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                    <button type="submit" class="btn btn-sm" style="background:#fff0f0; color:#dc3545; border:1px solid #dc3545;">Del</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<dialog id="edit-modal" class="card" style="padding: 24px; max-width: 400px; width: 100%; margin: auto; border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <h2 style="margin-top: 0;">Edit Belonging</h2>
    <form method="POST" id="edit-form" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
        <div class="form-row">
            <label for="edit_name">Belong Name</label>
            <input class="input" id="edit_name" name="name" required autocomplete="off">
        </div>
        <div style="display:flex; justify-content:flex-end; gap: 12px; margin-top: 16px;">
            <button class="btn btn-secondary" type="button" onclick="document.getElementById('edit-modal').close()">Cancel</button>
            <button class="btn btn-primary" type="submit">Update</button>
        </div>
    </form>
</dialog>

<script>
function editBelong(id, name) {
    const modal = document.getElementById('edit-modal');
    const form = document.getElementById('edit-form');
    const input = document.getElementById('edit_name');
    
    form.action = `/repair/admin/belongs/${id}/update`;
    input.value = name;
    
    modal.showModal();
}
</script>
