<?php
$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
    <h3>Repair Center</h3>
    <div class="nav-group">
        <?php if (can("p_3")): ?>
            <a class="nav-link <?= $path === '/repair/jobs/new' ? 'active' : '' ?>" href="/repair/jobs/new">New Job</a>
        <?php endif; ?>
        
        <?php if (can("p_4")): ?>
            <a class="nav-link <?= $path === '/repair/process' ? 'active' : '' ?>" href="/repair/process">Job Processing</a>
        <?php endif; ?>
        
        <?php if (can("p_5")): ?>
            <a class="nav-link <?= $path === '/repair/release' ? 'active' : '' ?>" href="/repair/release">Job Release / Bill</a>
        <?php endif; ?>

        <?php if (can("p_6")): ?>
            <a class="nav-link <?= $path === '/repair/handover' ? 'active' : '' ?>" href="/repair/handover">Payment & Handover</a>
        <?php endif; ?>
        
        <div style="margin: 12px 0 4px; font-weight: bold; color: #a1b0b8; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.05em;">Admin</div>
        <a class="nav-link <?= $path === '/repair/admin/faults' ? 'active' : '' ?>" href="/repair/admin/faults">Common Faults</a>
        <a class="nav-link <?= $path === '/repair/admin/belongs' ? 'active' : '' ?>" href="/repair/admin/belongs">Customer Belongs</a>
        
        <a class="nav-link" href="/dashboard" style="margin-top: 16px;">Back to Dashboard</a>
    </div>
</aside>
