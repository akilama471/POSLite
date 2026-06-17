<div class="auth-shell">
    <div class="auth-card" style="width: min(440px, 100%); padding: 36px 32px;">
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: var(--accent); border-radius: 16px; margin-bottom: 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2"/><path d="M16 7V5a4 4 0 0 0-8 0v2"/></svg>
            </div>
            <h1 style="margin: 0 0 6px; font-size: 1.5rem;">Nextgen Easy POS</h1>
            <p class="section-copy" style="margin: 0;">Sign in to your account to continue.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(app_url('/login'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-row">
                <label for="u-name">Username</label>
                <input class="input" id="u-name" name="u-name" type="text" autocomplete="username" required autofocus>
            </div>

            <div class="form-row">
                <label for="u-pass">Password</label>
                <input class="input" id="u-pass" name="u-pass" type="password" autocomplete="current-password" required>
            </div>

            <button class="btn btn-primary" type="submit" style="width: 100%; margin-top: 4px; padding: 14px;">Sign In</button>
        </form>
    </div>
</div>
