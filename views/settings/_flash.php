<?php if (!empty($flash)): ?>
    <div class="alert <?= ($flash["type"] ?? "") === "success" ? "" : "alert-error" ?>" style="<?= ($flash["type"] ?? "") === "success" ? "background:#e8f6ef;color:#146b4f;border:1px solid #bde4d0;" : "" ?>">
        <?= htmlspecialchars((string) ($flash["message"] ?? ""), ENT_QUOTES, "UTF-8") ?>
    </div>
<?php endif; ?>
