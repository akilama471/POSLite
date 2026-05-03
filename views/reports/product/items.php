<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Product Master List</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_product_allprodut.php`</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom: 24px;">
                <div class="grid" style="grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
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
                    <h2 style="margin-bottom: 4px;">System Products List</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Item ID</th>
                            <th style="width: 40%;">Item Name</th>
                            <th style="width: 20%;">Category</th>
                            <th style="width: 20%;">Tracking Type</th>
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
    const categoryId = document.getElementById('category_id').value;

    try {
        const formData = new FormData();
        formData.append('type', 'product_list');
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

            renderTable(result.data);
        }
    } catch (e) {
        console.error('Failed to load report data', e);
    }
}

function renderTable(data) {
    const container = document.getElementById('report-container');
    const tbody = document.getElementById('report-body');
    const subtitle = document.getElementById('report-subtitle');
    const selCat = document.getElementById('category_id');

    container.style.display = 'block';
    subtitle.textContent = selCat.options[selCat.selectedIndex].text;
    
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center muted">No records found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        
        let trackingType = 'Unknown';
        if (row.used_type == 1) trackingType = 'Item Code / Barcode';
        else if (row.used_type == 2) trackingType = 'IMEI Tracked';
        else if (row.used_type == 3) trackingType = 'Recharge Card';

        tr.innerHTML = `
            <td>${escapeHtml(row.item_id)}</td>
            <td>${escapeHtml(row.item_name)}</td>
            <td>${escapeHtml(row.catname || 'Unknown')}</td>
            <td>${trackingType}</td>
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
