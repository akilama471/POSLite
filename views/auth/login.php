<div class="auth-shell">
    <div class="auth-card" style="width: min(440px, 100%); padding: 28px;">
        <div style="margin-bottom: 24px;">
            <div class="tag">Legacy auth migrated to MVC</div>
            <h1 style="margin: 16px 0 8px;">Nextgen Easy POS</h1>
            <p class="section-copy">This login form now runs through the new MVC layer while keeping the legacy `sys_user` authentication contract.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(app_url('/login'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-row">
                <label for="u-name">Username</label>
                <input class="input" id="u-name" name="u-name" type="text" autocomplete="username" required>
            </div>

            <div class="form-row">
                <label for="u-pass">Password</label>
                <input class="input" id="u-pass" name="u-pass" type="password" autocomplete="current-password" required>
            </div>

            <button class="btn btn-primary" type="submit" style="width: 100%;">Login</button>
        </form>
    </div>
</div>
