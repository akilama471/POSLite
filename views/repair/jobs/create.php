<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">New Repair Job</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rep_new_job.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/repair/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <form method="POST" action="/repair/jobs" class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                <section class="card stack">
                    <h2>Job Details</h2>
                    
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-row">
                            <label for="cus_name">Customer Name <span style="color:red">*</span></label>
                            <input class="input" id="cus_name" name="cus_name" required autofocus>
                        </div>
                        <div class="form-row">
                            <label for="cus_imei">Device IMEI / Serial</label>
                            <input class="input" id="cus_imei" name="cus_imei">
                        </div>
                    </div>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-row">
                            <label for="cus_contact">Contact Number <span style="color:red">*</span></label>
                            <input class="input" id="cus_contact" name="cus_contact" required>
                        </div>
                        <div class="form-row">
                            <label for="cus_model">Device Model</label>
                            <input class="input" id="cus_model" name="cus_model">
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="cus_addr">Customer Address</label>
                        <input class="input" id="cus_addr" name="cus_addr">
                    </div>

                    <div class="form-row">
                        <div style="display:flex; justify-content:space-between;">
                            <label for="fault">Fault Summary <span style="color:red">*</span></label>
                            <a href="#" onclick="document.getElementById('faults-modal').showModal(); return false;" style="font-size: 0.9em;">Add Common Fault</a>
                        </div>
                        <textarea class="input" id="fault" name="fault" rows="3" required></textarea>
                    </div>

                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px; align-items: center;">
                        <div class="form-row">
                            <label for="adv_payment">Advance Payment (Rs)</label>
                            <input class="input" type="number" step="0.01" min="0" id="adv_payment" name="adv_payment" value="0">
                        </div>
                        <div>
                            <label style="display:flex; align-items:center; gap: 8px; cursor:pointer;">
                                <input type="checkbox" name="warranty_device" value="1">
                                Repair this device for Warranty
                            </label>
                        </div>
                    </div>
                </section>

                <div class="stack" style="gap: 24px;">
                    <section class="card">
                        <h2>Items Collected from Customer</h2>
                        <div style="max-height: 400px; overflow-y: auto; padding-right: 12px; margin-top: 12px;" class="stack">
                            <?php foreach ($belongs as $blg): ?>
                                <label style="display:flex; align-items:center; gap: 8px; padding: 8px; border: 1px solid #e1e8ed; border-radius: 4px; cursor:pointer;">
                                    <input type="checkbox" name="belongs[]" value="<?= (int) $blg['recordid'] ?>">
                                    <?= htmlspecialchars((string) $blg['belong_name'], ENT_QUOTES, "UTF-8") ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary btn-lg">Submit Repair Job</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<dialog id="faults-modal" class="card" style="padding: 24px; max-width: 500px; width: 100%; margin: auto; border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <h2 style="margin-top: 0;">Common Faults</h2>
    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 16px;" class="stack">
        <?php foreach ($faults as $f): ?>
            <label style="display:flex; align-items:center; gap: 8px; cursor:pointer; padding: 4px;">
                <input type="checkbox" class="fault-checkbox" value="<?= htmlspecialchars((string) $f['fault_name'], ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars((string) $f['fault_name'], ENT_QUOTES, "UTF-8") ?>
            </label>
        <?php endforeach; ?>
    </div>
    <div style="display:flex; justify-content:flex-end; gap: 12px;">
        <button class="btn btn-secondary" type="button" onclick="document.getElementById('faults-modal').close()">Cancel</button>
        <button class="btn btn-primary" type="button" onclick="appendFaults()">Add Selected</button>
    </div>
</dialog>

<script>
function appendFaults() {
    const checkboxes = document.querySelectorAll('.fault-checkbox:checked');
    const faultInput = document.getElementById('fault');
    
    let currentVal = faultInput.value.trim();
    let additions = [];
    
    checkboxes.forEach(cb => {
        additions.push(cb.value);
        cb.checked = false; // reset
    });
    
    if (additions.length > 0) {
        if (currentVal) currentVal += ", ";
        currentVal += additions.join(", ");
        faultInput.value = currentVal;
    }
    
    document.getElementById('faults-modal').close();
}
</script>
