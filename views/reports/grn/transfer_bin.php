<?php // views/reports/grn/transfer_bin.php — rpt_grn_notreturnwhouse.php ?>
<div class="app-shell">
    <header class="topbar"><div><div class="brand">Transfer Bin Items Report</div><div class="muted" style="color:#b8c6cf;">Migrated from <code>rpt_grn_notreturnwhouse.php</code></div></div></header>
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
                <div style="text-align:center;margin-bottom:24px;"><h2>Transfer Bin Items</h2><div class="muted" id="report-subtitle"></div></div>
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Item Name</th><th>IMEI / Part</th><th>Supplier</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Cost (Rs)</th><th>Status</th></tr></thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport(){
    const shopId=document.getElementById('shop_id').value;if(shopId==0){alert('Please select a shop.');return;}
    const fd=new FormData();fd.append('type','grn_transfer_bin');fd.append('from_date',document.getElementById('from_date').value);fd.append('to_date',document.getElementById('to_date').value);fd.append('shop_id',shopId);fd.append('_token','<?=htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8')?>');
    const res=await fetch('/reports/api/fetch',{method:'POST',body:fd});const json=await res.json();if(json.status==='error'){alert(json.message);return;}
    const tbody=document.getElementById('report-body');tbody.innerHTML='';document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent=`From ${document.getElementById('from_date').value} to ${document.getElementById('to_date').value}`;
    if(!json.data.length){tbody.innerHTML='<tr><td colspan="7" class="text-center muted">No transfer bin items found.</td></tr>';return;}
    json.data.forEach(r=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${esc(r.record_date||'')}</td><td>${esc(r.item_name||'')}</td><td>${esc(r.imei_no||'—')}</td><td>${esc(r.suppler_name||'—')}</td><td style="text-align:center;">${r.qty||0}</td><td style="text-align:right;">${parseFloat(r.item_cost||0).toFixed(2)}</td><td>${esc(r.status_label||'Pending')}</td>`;tbody.appendChild(tr);});
}
function esc(s){return(s+'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
