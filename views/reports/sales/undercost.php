<?php // views/reports/sales/undercost.php — rpt_sale_undercost.php ?>
<div class="app-shell">
    <header class="topbar"><div><div class="brand">Under-Cost Sales Report</div><div class="muted" style="color:#b8c6cf;">Migrated from <code>rpt_sale_undercost.php</code></div></div></header>
    <div class="shell-grid">
        <?php require BASE_PATH . '/views/reports/_nav.php'; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns:repeat(3,1fr) auto;gap:16px;align-items:end;">
                    <div class="form-row" style="margin:0;"><label for="from_date">From Date</label><input type="date" class="input" id="from_date" value="<?=date('Y-m-d')?>"></div>
                    <div class="form-row" style="margin:0;"><label for="to_date">To Date</label><input type="date" class="input" id="to_date" value="<?=date('Y-m-d')?>"></div>
                    <div class="form-row" style="margin:0;">
                        <label for="shop_id">Shop</label>
                        <select class="input" id="shop_id">
                            <option value="0" disabled selected>Choose...</option><option value="-1">All Shops</option>
                            <?php foreach($shops as $s):?><?php if($s['shopid']>0):?><option value="<?=(int)$s['shopid']?>"><?=htmlspecialchars((string)$s['shop_info_name'],ENT_QUOTES,'UTF-8')?></option><?php endif;?><?php endforeach;?>
                        </select>
                    </div>
                    <div style="display:flex;gap:8px;"><button class="btn btn-primary" onclick="loadReport()">Load Data</button><button class="btn btn-secondary" onclick="window.print()">Print</button></div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div style="text-align:center;margin-bottom:24px;"><h2>Under-Cost Sales</h2><div class="muted" id="report-subtitle"></div></div>
                <table class="data-table">
                    <thead><tr><th>Bill No</th><th>Date</th><th>Item Name</th><th style="text-align:right;">Cost (Rs)</th><th style="text-align:right;">Sale Price (Rs)</th><th style="text-align:right;">Loss (Rs)</th></tr></thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport(){
    const shopId=document.getElementById('shop_id').value;if(shopId==0){alert('Please select a shop.');return;}
    const fd=new FormData();fd.append('type','under_cost_sale');fd.append('from_date',document.getElementById('from_date').value);fd.append('to_date',document.getElementById('to_date').value);fd.append('shop_id',shopId);fd.append('_token','<?=htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8')?>');
    const res=await fetch('/reports/api/fetch',{method:'POST',body:fd});const json=await res.json();if(json.status==='error'){alert(json.message);return;}
    const tbody=document.getElementById('report-body');tbody.innerHTML='';document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent=`From ${document.getElementById('from_date').value} to ${document.getElementById('to_date').value}`;
    if(!json.data.length){tbody.innerHTML='<tr><td colspan="6" class="text-center muted">No under-cost records found.</td></tr>';return;}
    json.data.forEach(r=>{const diff=parseFloat(r.cost_price||0)-parseFloat(r.sale_price||0);const tr=document.createElement('tr');tr.innerHTML=`<td>${esc(r.billnumber)}</td><td>${esc(r.sale_date||'')}</td><td>${esc(r.item_name)}</td><td style="text-align:right;">${parseFloat(r.cost_price||0).toFixed(2)}</td><td style="text-align:right;">${parseFloat(r.sale_price||0).toFixed(2)}</td><td style="text-align:right;color:#f87171;">${diff.toFixed(2)}</td>`;tbody.appendChild(tr);});
}
function esc(s){return(s+'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
