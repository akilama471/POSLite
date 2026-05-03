<div class="app-shell">
    <header class="topbar">
        <div>
            <div class="brand">Item Transfer Log Check</div>
            <div class="muted" style="color: #b8c6cf;">Migrated from `rpt_stocktrans_logcheck.php`</div>
        </div>
    </header>
    <div class="shell-grid">
        <?php require BASE_PATH . "/views/reports/_nav.php"; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns: 1fr auto; gap:16px; align-items:end;">
                    <div class="form-row" style="margin:0;">
                        <label for="item_code">Item Barcode / Code</label>
                        <input type="text" class="input" id="item_code" placeholder="Enter item barcode or IMEI to trace all transfers">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-primary" onclick="loadReport()">Trace Item</button>
                        <button class="btn btn-secondary" onclick="window.print()">Print</button>
                    </div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none; min-height:400px;">
                <div style="text-align:center; margin-bottom:24px;">
                    <h2 style="margin-bottom:4px;">Transfer History for Item</h2>
                    <div class="muted" id="report-subtitle"></div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:18%;">Transfer ID</th>
                            <th style="width:28%;">Item Name</th>
                            <th style="width:12%;">Code</th>
                            <th style="width:18%;">Transferred From</th>
                            <th style="width:10%; text-align:center;">Qty</th>
                            <th style="width:18%; text-align:right;">Transferred To</th>
                            <th style="width:16%;">Time</th>
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
    const code = document.getElementById('item_code').value.trim();
    if (!code) { alert('Please enter an item code.'); return; }
    const fd = new FormData();
    fd.append('type','transfer_logcheck'); fd.append('item_code',code);
    fd.append('_token','<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>');
    const res = await fetch('/reports/api/fetch',{method:'POST',body:fd});
    const result = await res.json();
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent = `Code: ${code}`;
    const tbody = document.getElementById('report-body');
    tbody.innerHTML = '';
    if (!result.data || result.data.length===0) { tbody.innerHTML='<tr><td colspan="7" class="text-center muted">No transfer history found for this item.</td></tr>'; return; }
    result.data.forEach(r => {
        tbody.innerHTML += `<tr>
            <td>${e(r.trans_id)}</td>
            <td>${e(r.Item_name)}</td>
            <td>${e(r.code)}</td>
            <td>${e(r.from_shop_name||'—')}</td>
            <td style="text-align:center;">${e(r.stock_count)}</td>
            <td style="text-align:right;">${e(r.to_shop_name||'—')}</td>
            <td>${e(r.recorded_time)}</td>
        </tr>`;
    });
}
function e(v){if(v==null)return'';return(v+'').replace(/[&<"'>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
