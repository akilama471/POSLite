<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Add Cash In</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `shop_openbalance.php` & `cashin_account.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/cashier/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <section class="card">
                    <h2>Record New Cash In</h2>
                    <form method="POST" action="/cashier/cash-in" class="stack">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <div class="form-row">
                            <label for="amount">Amount <span style="color:red">*</span></label>
                            <input class="input" type="number" step="0.01" min="0.01" id="amount" name="amount" required autofocus autocomplete="off">
                        </div>
                        <div class="form-row">
                            <label for="account_id">Account <span style="color:red">*</span></label>
                            <select class="input" id="account_id" name="account_id" required>
                                <option value="" disabled selected>Please Select...</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= (int) $acc['recordid'] ?>"><?= htmlspecialchars((string) $acc['acc_name'], ENT_QUOTES, "UTF-8") ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="reason">Reason <span style="color:red">*</span></label>
                            <input class="input" id="reason" name="reason" required autocomplete="off">
                        </div>
                        <div style="display:flex; justify-content:flex-end;">
                            <button class="btn btn-primary" type="submit">Submit Cash In</button>
                        </div>
                        <div class="muted" style="margin-top: 12px; font-size: 0.9em;">
                            Please add all non-system indicated cash in here to avoid unbalancing the cash flow.
                        </div>
                    </form>
                </section>

                <div class="stack" style="gap: 24px;">
                    <?php if (can("p_61")): ?>
                        <section class="card">
                            <h2>Add Cash In Account</h2>
                            <form method="POST" action="/cashier/cash-in/accounts" class="stack" style="flex-direction: row; align-items: flex-end; gap: 12px;">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                <div style="flex: 1;">
                                    <label for="name" style="display:block; margin-bottom: 4px;">Account Name</label>
                                    <input class="input" style="width: 100%;" id="name" name="name" required autocomplete="off">
                                </div>
                                <button class="btn btn-secondary" type="submit">Add</button>
                            </form>
                        </section>

                        <section class="card">
                            <h2>Manage Cash In Accounts</h2>
                            <div style="overflow:auto; max-height: 400px;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Account Name</th>
                                            <th style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accounts)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center muted">No cash in accounts found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accounts as $acc): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $acc['acc_name'], ENT_QUOTES, "UTF-8") ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="editAccount(<?= (int) $acc['recordid'] ?>, '<?= htmlspecialchars((string) $acc['acc_name'], ENT_QUOTES, "UTF-8") ?>')">Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<dialog id="edit-account-modal" class="card" style="padding: 24px; max-width: 400px; width: 100%; margin: auto; border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <h2 style="margin-top: 0;">Edit Account</h2>
    <form method="POST" id="edit-account-form" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
        <div class="form-row">
            <label for="edit_name">Account Name</label>
            <input class="input" id="edit_name" name="name" required autocomplete="off">
        </div>
        <div style="display:flex; justify-content:flex-end; gap: 12px; margin-top: 16px;">
            <button class="btn btn-secondary" type="button" onclick="document.getElementById('edit-account-modal').close()">Cancel</button>
            <button class="btn btn-primary" type="submit">Update</button>
        </div>
    </form>
</dialog>

<script>
function editAccount(id, name) {
    const modal = document.getElementById('edit-account-modal');
    const form = document.getElementById('edit-account-form');
    const input = document.getElementById('edit_name');
    
    form.action = `/cashier/cash-in/accounts/${id}/update`;
    input.value = name;
    
    modal.showModal();
}
</script>
