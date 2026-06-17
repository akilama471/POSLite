<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Job Release / Bill</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rep_job_release.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/repair/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <?php if (isset($_SESSION["released_job"])): ?>
                <section class="card" style="margin-bottom:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div class="tag" style="background:#e8f5e9; color:#2e7d32;">Bill Finalized</div>
                            <p style="margin:12px 0 0;">Repair bill for Job <strong><?= htmlspecialchars($_SESSION["released_job"], ENT_QUOTES, "UTF-8") ?></strong> has been successfully finalized.</p>
                        </div>
                        <a href="/print/repair-bill?docid=<?= urlencode($_SESSION["released_job"]) ?>" target="_blank" class="btn btn-primary">Print Repair Bill</a>
                    </div>
                </section>
                <?php unset($_SESSION["released_job"]); ?>
            <?php endif; ?>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <section class="card stack">
                    <h2>Make Bill for Repair Job</h2>
                    
                    <form method="POST" action="/repair/release" class="stack" id="release-form">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        
                        <div class="form-row">
                            <label for="job_id">Select Job</label>
                            <select class="input" id="job_id" name="job_id" onchange="loadJobData()" required>
                                <option value="">Select a Job...</option>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?= htmlspecialchars((string) $job['job_number'], ENT_QUOTES, "UTF-8") ?>">
                                        <?= htmlspecialchars((string) $job['job_number'], ENT_QUOTES, "UTF-8") ?> - <?= htmlspecialchars((string) $job['job_cus_name'], ENT_QUOTES, "UTF-8") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="part_cost">Total Cost for Items</label>
                            <input class="input" type="number" id="part_cost" name="part_cost" readonly tabindex="-1" style="background: #f8f9fa;">
                        </div>

                        <div class="form-row">
                            <label for="repair_cost">Repairing Charge <span style="color:red">*</span></label>
                            <input class="input" type="number" step="0.01" min="0" id="repair_cost" name="repair_cost" oninput="calculateTotal()" required disabled>
                        </div>

                        <div class="form-row">
                            <label for="adv_payment">Advanced Payment</label>
                            <input class="input" type="number" id="adv_payment" name="adv_payment" readonly tabindex="-1" style="background: #f8f9fa;">
                        </div>

                        <div class="form-row">
                            <label for="total">Balance to Pay For Repair Job</label>
                            <input class="input" type="number" id="total" name="total" readonly tabindex="-1" style="background: #f8f9fa; font-weight: bold;">
                        </div>

                        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px; align-items: end;">
                            <div class="form-row">
                                <label for="warranty_span">Repair Warranty (If Need)</label>
                                <input class="input" type="number" min="1" id="warranty_span" name="warranty_span" disabled>
                            </div>
                            <div class="form-row">
                                <select class="input" name="warranty_type" id="warranty_type" disabled>
                                    <option value=""></option>
                                    <option value="Day">Day</option>
                                    <option value="Week">Week</option>
                                    <option value="Month">Month</option>
                                    <option value="Year">Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <label style="display:flex; align-items:center; gap: 8px; cursor:pointer;">
                                <input type="checkbox" name="print_warranty" value="1" disabled id="print_warranty">
                                Print Warranty On Bill
                            </label>
                        </div>

                        <div style="display:flex; justify-content:flex-end; margin-top: 16px;">
                            <button type="submit" class="btn btn-primary" id="btn-submit" disabled>Submit Repair Bill Update</button>
                        </div>
                    </form>
                </section>

                <section class="card stack">
                    <h2>Job Info</h2>
                    <div id="warranty_info" style="color:red; font-weight:bold; margin-bottom: 12px;"></div>
                    <div class="muted">Select a job to load its final cost data.</div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
async function loadJobData() {
    const jobId = document.getElementById('job_id').value;
    const inputs = ['repair_cost', 'warranty_span', 'warranty_type', 'print_warranty', 'btn-submit'];
    
    if (!jobId) {
        inputs.forEach(id => document.getElementById(id).disabled = true);
        document.getElementById('part_cost').value = '';
        document.getElementById('adv_payment').value = '';
        document.getElementById('total').value = '';
        document.getElementById('warranty_info').textContent = '';
        return;
    }

    inputs.forEach(id => document.getElementById(id).disabled = false);

    try {
        const formData = new FormData();
        formData.append('job_id', jobId);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/repair/release/load', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('part_cost').value = data.partCost.toFixed(2);
            document.getElementById('adv_payment').value = data.advPayment.toFixed(2);
            
            if (data.warranty) {
                document.getElementById('warranty_info').textContent = 'This device repair is for warranty';
            } else {
                document.getElementById('warranty_info').textContent = '';
            }

            calculateTotal();
            document.getElementById('repair_cost').focus();
        }
    } catch (e) {
        console.error('Failed to load job data', e);
    }
}

function calculateTotal() {
    const partCost = parseFloat(document.getElementById('part_cost').value) || 0;
    const repairCost = parseFloat(document.getElementById('repair_cost').value) || 0;
    const advPayment = parseFloat(document.getElementById('adv_payment').value) || 0;
    
    const subtotal = partCost + repairCost;
    const total = subtotal - advPayment;
    
    document.getElementById('total').value = total.toFixed(2);
}
</script>
