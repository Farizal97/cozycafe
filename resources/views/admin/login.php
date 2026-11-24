<?php
$flashType = is_array($flash ?? null) && in_array($flash['type'] ?? '', ['success', 'danger', 'warning', 'info'], true)
    ? $flash['type']
    : 'info';
$isAuthPage = true;
ob_start();
?>
<main class="login-page">
    <div class="login-card">
        <a class="cozy-brand login-brand" href="<?= e($url('')) ?>">CozyCafe</a>
        <span class="section-kicker">AREA ADMIN</span>
        <h1>Masuk ke dashboard</h1>
        <p class="login-description">Gunakan akun admin CozyCafe untuk melanjutkan.</p>

        <?php if (is_array($flash ?? null)): ?>
            <div class="alert alert-<?= e($flashType) ?>" role="alert"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e($url('admin/login')) ?>" autocomplete="on">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="form-control form-control-lg" id="username" name="username" type="text" maxlength="80" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-cozy-primary btn-lg w-100" type="submit">Masuk</button>
        </form>
        <a class="login-back-link" href="<?= e($url('')) ?>">Kembali ke website</a>
    </div>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
