<?php // views/reports/grn/return_detail.php — rpt_grn_returndata.php ?>
<div class="app-shell">
    <header class="topbar"><div><div class="brand">Return Document Detail</div><div class="muted" style="color:#b8c6cf;">Migrated from <code>rpt_grn_returndata.php</code></div></div></header>
    <div class="shell-grid">
        <?php require BASE_PATH . '/views/reports/_nav.php'; ?>
        <main class="page">
            <section class="card stack" style="margin-bottom:24px;">
                <div class="grid" style="grid-template-columns:1fr auto;gap:16px;align-items:end;">
                    <div class="form-row" style="margin:0;"><label for="return_ref">Return Reference No</label><input type="text" class="input" id="return_ref" placeholder="Enter return ref..."></div>
                    <div style="display:flex;gap:8px;"><button class="btn btn-primary" onclick="loadReport()">Load Detail</button><button class="btn btn-secondary" onclick="window.print()">Print</button></div>
                </div>
            </section>
            <section class="card stack print-area" id="report-container" style="display:none;">
                <div style="text-align:center;margin-bottom:24px;"><h2>Return Document Detail</h2><div class="muted" id="report-subtitle"></div></div>
                <div id="header-info" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;"></div>
                <table class="data-table">
                    <thead><tr><th>Item Name</th><th>IMEI / Part</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Cost (Rs)</th><th style="text-align:right;">Value (Rs)</th></tr></thead>
                    <tbody id="report-body"></tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
async function loadReport(){
    const ref=document.getElementById('return_ref').value.trim();if(!ref){alert('Please enter a return reference.');return;}
    const fd=new FormData();fd.append('type','grn_return_detail');fd.append('return_ref',ref);fd.append('_token','<?=htmlspecialchars(csrf_token(),ENT_QUOTES,'UTF-8')?>');
    const res=await fetch('/reports/api/fetch',{method:'POST',body:fd});const json=await res.json();if(json.status==='error'){alert(json.message);return;}
    document.getElementById('report-container').style.display='block';
    document.getElementById('report-subtitle').textContent=`Return Ref: ${ref}`;
    const tbody=document.getElementById('report-body');tbody.innerHTML='';
    if(!json.data.length){tbody.innerHTML='<tr><td colspan="5" class="text-center muted">No items found.</td></tr>';return;}
    json.data.forEach(r=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${esc(r.item_name||'')}</td><td>${esc(r.imei_no||'—')}</td><td style="text-align:center;">${r.item_qty||0}</td><td style="text-align:right;">${parseFloat(r.item_cost||0).toFixed(2)}</td><td style="text-align:right;">${parseFloat(r.return_value||0).toFixed(2)}</td>`;tbody.appendChild(tr);});
}
function esc(s){return(s+'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
