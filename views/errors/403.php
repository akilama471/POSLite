<div class="auth-shell">
    <div class="auth-card" style="width: min(420px, 100%); padding: 28px; text-align: center;">
        <div class="tag">403</div>
        <h1 style="margin: 16px 0 8px;">Access denied</h1>
        <p class="section-copy"><?= htmlspecialchars($message ?? "You do not have permission to access this page.", ENT_QUOTES, "UTF-8") ?></p>
        <p style="margin-top: 20px;"><a class="btn btn-primary" href="<?= htmlspecialchars(app_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a></p>
    </div>
</div>
