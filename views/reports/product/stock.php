<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Comprehensive Stock Report</div>
            <div class="muted" style="color: #b8c6cf;">Consolidates stock counts & values dynamically</div>
        </div>
    </header>

    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom: 24px;">
                <div class="grid" style="grid-template-columns: repeat(3, 1fr) auto; gap: 16px; align-items: end;">
                    <div class="form-row" style="margin: 0;">
                        <label for="shop_id">Select Shop</label>
                        <select class="input" id="shop_id">
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
                    <div class="form-row" style="margin: 0;">
                        <label for="availability">Stock Status</label>
                        <select class="input" id="availability">
                            <option value="all">All (Include Empty & Available)</option>
                            <option value="in_stock">Available Stock (> 0)</option>
                            <option value="empty">Out of Stock (0)</option>
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
                    <h2 style="margin-bottom: 4px;">Current Stock Analysis</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Item ID</th>
                            <th style="width: 40%;">Item Name</th>
                            <th style="width: 20%; text-align: right;">Stock Count</th>
                            <th style="width: 20%; text-align: right;">Stock Value (Rs)</th>
                        </tr>
                    </thead>
                    <tbody id="report-body">
                        <!-- Data injected via JS -->
                    </tbody>
                    <tfoot style="border-top: 2px solid #2c3e50; font-weight: bold;">
                        <tr>
                            <td colspan="3" style="text-align: right;">Total Stock Value:</td>
                            <td style="text-align: right;" id="report-total">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </main>
    </div>
</div>

<script>
async function loadReport() {
    const shopId = document.getElementById('shop_id').value;
    const categoryId = document.getElementById('category_id').value;
    const availability = document.getElementById('availability').value;

    try {
        const formData = new FormData();
        formData.append('type', 'product_stock');
        formData.append('shop_id', shopId);
        formData.append('category_id', categoryId);
        formData.append('availability', availability);
        formData.append('_token', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');

        const container = document.getElementById('report-container');
        const tbody = document.getElementById('report-body');
        
        container.style.display = 'block';
        tbody.innerHTML = '<tr><td colspan="4" class="text-center muted">Loading stock data, please wait...</td></tr>';
        document.getElementById('report-total').textContent = '0.00';

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
    const tbody = document.getElementById('report-body');
    const totalEl = document.getElementById('report-total');
    const selCat = document.getElementById('category_id');
    const selShop = document.getElementById('shop_id');
    const selAv = document.getElementById('availability');
    const subtitle = document.getElementById('report-subtitle');

    subtitle.textContent = `Shop: ${selShop.options[selShop.selectedIndex].text} | Category: ${selCat.options[selCat.selectedIndex].text} | Status: ${selAv.options[selAv.selectedIndex].text}`;
    
    tbody.innerHTML = '';
    let totalValue = 0;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center muted">No records found matching these criteria.</td></tr>';
        totalEl.textContent = '0.00';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        const count = parseFloat(row.stock_count) || 0;
        const val = parseFloat(row.stock_value) || 0;
        
        totalValue += val;

        tr.innerHTML = `
            <td>${escapeHtml(row.item_id)}</td>
            <td>${escapeHtml(row.item_name)}</td>
            <td style="text-align: right;">${count.toString()}</td>
            <td style="text-align: right;">${val.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });

    totalEl.textContent = totalValue.toFixed(2);
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
