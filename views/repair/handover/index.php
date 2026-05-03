<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Repair Job Payment & Handover</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rep_job_handover.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/repair/_nav.php"; ?>
        <main class="page">
            <?php require BASE_PATH . "/views/settings/_flash.php"; ?>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
                <section class="card stack">
                    <h2>Select Repair Job</h2>
                    
                    <form method="POST" action="/repair/handover" class="stack" id="handover-form">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        
                        <div class="form-row">
                            <label for="job_id">Job ID</label>
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
                            <label for="total_cost">Total Charge For Repair Job</label>
                            <input class="input" type="number" id="total_cost" readonly tabindex="-1" style="background: #f8f9fa;">
                        </div>

                        <div class="form-row">
                            <label for="adv_payment">Advanced Payment</label>
                            <input class="input" type="number" id="adv_payment" readonly tabindex="-1" style="background: #f8f9fa;">
                        </div>
                        
                        <div class="form-row">
                            <label for="balance_to_pay">Remaining Balance To Pay</label>
                            <input class="input" type="number" id="balance_to_pay" readonly tabindex="-1" style="background: #f8f9fa; font-weight: bold;">
                        </div>

                        <div class="form-row">
                            <label for="cash_pay">Customer Cash Pay Amount</label>
                            <input class="input" type="number" step="0.01" min="0" id="cash_pay" name="cash_pay" oninput="calculateBalance()" required disabled>
                        </div>

                        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-row">
                                <label for="card_pay">Customer Card Pay Amount</label>
                                <input class="input" type="number" step="0.01" min="0" id="card_pay" name="card_pay" oninput="calculateBalance()" disabled>
                            </div>
                            <div class="form-row">
                                <label for="card_number">Card Number</label>
                                <input class="input" id="card_number" name="card_number" disabled>
                            </div>
                        </div>

                        <div class="form-row">
                            <label for="balance">Balance To Customer</label>
                            <input class="input" type="number" id="balance" name="balance" readonly tabindex="-1" style="background: #f8f9fa;">
                        </div>

                        <div style="display:flex; justify-content:flex-end; margin-top: 16px;">
                            <button type="submit" class="btn btn-primary" id="btn-submit" disabled>Finish Repair Bill</button>
                        </div>
                    </form>
                </section>

                <section class="card stack">
                    <h2>Notes & Operations</h2>
                    <div id="log_info" style="border: 1px solid #e1e8ed; border-radius: 4px; padding: 12px; background: #fafafa; min-height: 200px; font-family: monospace;">
                        <span class="muted">Select a job to view repair logs...</span>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
async function loadJobData() {
    const jobId = document.getElementById('job_id').value;
    const inputs = ['cash_pay', 'card_pay', 'card_number', 'btn-submit'];
    
    if (!jobId) {
        inputs.forEach(id => document.getElementById(id).disabled = true);
        document.getElementById('total_cost').value = '';
        document.getElementById('adv_payment').value = '';
        document.getElementById('balance_to_pay').value = '';
        document.getElementById('cash_pay').value = '';
        document.getElementById('card_pay').value = '';
        document.getElementById('card_number').value = '';
        document.getElementById('balance').value = '';
        document.getElementById('log_info').innerHTML = '<span class="muted">Select a job to view repair logs...</span>';
        return;
    }

    inputs.forEach(id => document.getElementById(id).disabled = false);

    try {
        const formData = new FormData();
        formData.append('job_id', jobId);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/repair/handover/load', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('total_cost').value = data.totalCost.toFixed(2);
            document.getElementById('adv_payment').value = data.advPayment.toFixed(2);
            document.getElementById('balance_to_pay').value = data.balanceToPay.toFixed(2);
            document.getElementById('log_info').innerHTML = data.logsHtml;

            calculateBalance();
            document.getElementById('cash_pay').focus();
        }
    } catch (e) {
        console.error('Failed to load job data', e);
    }
}

function calculateBalance() {
    const balanceToPay = parseFloat(document.getElementById('balance_to_pay').value) || 0;
    const cashPay = parseFloat(document.getElementById('cash_pay').value) || 0;
    const cardPay = parseFloat(document.getElementById('card_pay').value) || 0;
    
    const totalGiven = cashPay + cardPay;
    const balance = totalGiven - balanceToPay;
    
    document.getElementById('balance').value = balance.toFixed(2);
}
</script>
