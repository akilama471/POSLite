<?php
// views/print/_jspm.php — shared JSPrintManager CDN loader
// Include this partial in every print view that needs JSPM
?>
<script src="https://jsprintmanager.azurewebsites.net/scripts/JSESCPOSBuilder.js"></script>
<script src="https://jsprintmanager.azurewebsites.net/scripts/JSPrintManager.js"></script>
<script src="https://jsprintmanager.azurewebsites.net/scripts/zip.js"></script>
<script src="https://jsprintmanager.azurewebsites.net/scripts/zip-ext.js"></script>
<script src="https://jsprintmanager.azurewebsites.net/scripts/deflate.js"></script>
<script>
JSPM.JSPrintManager.auto_reconnect = true;
JSPM.JSPrintManager.start();

function jspmStatus() {
    const s = JSPM.JSPrintManager.websocket_status;
    const el = document.getElementById('jspm-status');
    if (s === JSPM.WSStatus.Open)   { if(el) el.textContent=''; return true; }
    if (s === JSPM.WSStatus.Closed) { if(el) el.textContent='⚠ JSPrintManager client app is not running. Please start it.'; return false; }
    if (s === JSPM.WSStatus.Blocked){ if(el) el.textContent='⚠ JSPrintManager is blocked for this site. Please enable it.'; return false; }
    return false;
}

function sendEscposJob(escposCommands, printerName, onDone) {
    var cpj = new JSPM.ClientPrintJob();
    cpj.clientPrinter = new JSPM.InstalledPrinter(printerName);
    cpj.binaryPrinterCommands = escposCommands;
    cpj.sendToClient();
    if (onDone) setTimeout(onDone, 1200);
}
</script>
