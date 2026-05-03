<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Reorder Alert Report</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_grn_reorder.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="shop_id">Select Shop</label>
                        <select class="input" id="shop_id">
                            <option value="-1">All Shops</option>
                            <?php foreach ($shops as $shop): ?>
                                <?php if ($shop['shopid'] > 0): ?>
                                    <option value="<?= (int)$shop['shopid'] ?>"><?= htmlspecialchars((string)$shop['shop_info_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="margin:0;">
                        <label for="category_id">Category</label>
                        <select class="input" id="category_id">
                            <option value="-1">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['catid'] ?>"><?= htmlspecialchars((string)$cat['catname'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Load Alerts</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none; min-height:400px;">
                <div style="text-align:center; margin-bottom:24px;">
                    <h2 style="margin-bottom:4px;">Items Below Reorder Level</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:10%; text-align:center;">Item ID</th>
                            <th style="width:45%;">Item Name</th>
                            <th style="width:20%;">Shop</th>
                            <th style="width:12%; text-align:center;">Alert Qty</th>
                            <th style="width:13%; text-align:center; color:#dc3545;">Current Qty</th>
                        </tr>
                    </thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport() {
    const shopId = document.getElementById('shop_id').value;
    const catId = document.getElementById('category_id').value;
    const fd = new FormData();
    fd.append('type','grn_reorder'); fd.append('shop_id',shopId); fd.append('category_id',catId);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    const selShop = document.getElementById('shop_id');
    document.getElementById('report-subtitle').textContent = `Shop: ${selShop.options[selShop.selectedIndex].text}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="5" class="text-center muted">No reorder alerts. All stock levels are adequate.</td></tr>'; return; }
    result.data.forEach(r => {
        tbody.innerHTML += `<tr><td style="text-align:center;">${e(r.item_id)}</td><td>${e(r.item_name)}</td><td>${e(r.shop_info_name||'—')}</td><td style="text-align:center;">${e(r.alert_qty)}</td><td style="text-align:center;color:#dc3545;font-weight:600;">${e(r.current_qty)}</td></tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
