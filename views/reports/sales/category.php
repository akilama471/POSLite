<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Category Wise Sale Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_sale_catwisesale.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom: 24px;">
                <div class="grid" style="grid-template-columns: repeat(4, 1fr) auto; gap: 16px; align-items: end;">
                    <div class="form-row" style="margin: 0;">
                        <label for="from_date">From Date</label>
                        <input type="date" class="input" id="from_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label for="to_date">To Date</label>
                        <input type="date" class="input" id="to_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label for="shop_id">Select Shop</label>
                        <select class="input" id="shop_id">
                            <option value="0" disabled selected>Choose...</option>
                            <option value="-1">All Shops</option>
                            <?php foreach ($shops as $shop): ?>
                                <?php if ($shop['shopid'] > 0): ?>
                                    <option value="<?= (int) $shop['shopid'] ?>"><?= htmlspecialchars((string) $shop['shop_info_name'], ENT_QUOTES, "UTF-8") ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="margin: 0;">
                        <label for="category_id">Select Category</label>
                        <select class="input" id="category_id">
                            <option value="-1">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['catid'] ?>"><?= htmlspecialchars((string) $cat['catname'], ENT_QUOTES, "UTF-8") ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap: 8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Load Data</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>

            <section class="card stack print-area" id="report-container" style="display:none; min-height: 400px;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h2 style="margin-bottom: 4px;">Category Wise Sale Report</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Category Name</th>
                            <th style="width: 25%; text-align: center;">Items Sold</th>
                            <th style="width: 25%; text-align: right;">Sale Value (Rs)</th>
                        </tr>
                    </thead>
                    <tbody id="report-body">
                        <!-- Data injected via JS -->
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>

<script>
async function loadReport() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;
    const shopId = document.getElementById('shop_id').value;
    const categoryId = document.getElementById('category_id').value;

    if (shopId == 0) {
        alert("Please Select the Shop.");
        document.getElementById('shop_id').focus();
        return;
    }

    try {
        const formData = new FormData();
        formData.append('type', 'category_sale');
        formData.append('from_date', fromDate);
        formData.append('to_date', toDate);
        formData.append('shop_id', shopId);
        formData.append('category_id', categoryId);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const response = await fetch('/reports/api/fetch', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.status === "error") {
                alert(result.message);
                return;
            }

            renderTable(result.data, fromDate, toDate);
        }
    } catch (e) {
        console.error('Failed to load report data', e);
    }
}

function renderTable(data, from, to) {
    const container = document.getElementById('report-container');
    const tbody = document.getElementById('report-body');
    const subtitle = document.getElementById('report-subtitle');

    container.style.display = 'block';
    subtitle.textContent = `Sale details from ${from} to ${to}`;
    
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center muted">No records found for this period.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        const qty = parseFloat(row.total_qty) || 0;
        const amount = parseFloat(row.total_value) || 0;

        tr.innerHTML = `
            <td>${escapeHtml(row.catname || 'Unknown Category')}</td>
            <td style="text-align: center;">${qty}</td>
            <td style="text-align: right;">${amount.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return (unsafe + '').replace(/[&<"'>]/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}
</script>
