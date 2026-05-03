<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Job Processing</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rep_job_process.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/repair/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <section class="card stack">
                    <h2>Job Operation Log</h2>
                    
                    <div class="form-row">
                        <label for="job_id">Select Job</label>
                        <select class="input" id="job_id" onchange="loadJobData()">
                            <option value="">Select a Job...</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?= htmlspecialchars((string) $job['job_number'], ENT_QUOTES, "UTF-8") ?>">
                                    <?= htmlspecialchars((string) $job['job_number'], ENT_QUOTES, "UTF-8") ?> - <?= htmlspecialchars((string) $job['job_cus_name'], ENT_QUOTES, "UTF-8") ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Operation History</label>
                        <select class="input" id="job_history" size="10" style="font-family: monospace; font-size: 0.9em; height: 200px;">
                        </select>
                    </div>

                    <form method="POST" action="/repair/process/finish" onsubmit="return confirm('Are you sure the repair process has ended? This cannot be undone.');">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="job_id" id="finish_job_id">
                        <button type="submit" class="btn btn-primary btn-block" id="btn-finish" disabled>Finish Repair Job</button>
                    </form>
                </section>

                <div class="stack" style="gap: 24px;">
                    <section class="card stack">
                        <h2>Parts Adding To Job</h2>
                        <form method="POST" action="/repair/process/add-part" class="form-row" style="margin-bottom: 0;">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="job_id" id="part_job_id">
                            
                            <label for="barcode">Scan Barcode / Enter Ref No</label>
                            <input class="input" id="barcode" name="barcode" required disabled>
                            
                            <div style="display:flex; justify-content:flex-end; margin-top: 16px;">
                                <button type="submit" class="btn btn-secondary" id="btn-add-part" disabled>Add Part to Job</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
async function loadJobData() {
    const jobId = document.getElementById('job_id').value;
    const historySelect = document.getElementById('job_history');
    const barcodeInput = document.getElementById('barcode');
    const btnFinish = document.getElementById('btn-finish');
    const btnAddPart = document.getElementById('btn-add-part');
    const finishJobId = document.getElementById('finish_job_id');
    const partJobId = document.getElementById('part_job_id');

    historySelect.innerHTML = '';
    
    if (!jobId) {
        barcodeInput.disabled = true;
        btnFinish.disabled = true;
        btnAddPart.disabled = true;
        return;
    }

    finishJobId.value = jobId;
    partJobId.value = jobId;

    barcodeInput.disabled = false;
    btnFinish.disabled = false;
    btnAddPart.disabled = false;
    barcodeInput.focus();

    try {
        const formData = new FormData();
        formData.append('job_id', jobId);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/repair/process/load', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const html = await response.text();
            historySelect.innerHTML = html;
        }
    } catch (e) {
        console.error('Failed to load job data', e);
    }
}
</script>
