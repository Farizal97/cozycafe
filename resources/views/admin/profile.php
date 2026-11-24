<?php
ob_start();
?>
<section class="admin-form-card">
    <div class="section-heading">
        <div>
            <span class="section-kicker">PENGATURAN AKUN</span>
            <h2>Perbarui username</h2>
        </div>
    </div>
    <p class="form-help">Konfirmasi perubahan dengan memasukkan password saat ini.</p>
    <form method="post" action="<?= e($url('admin/profile')) ?>" class="admin-form">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" type="text" minlength="3" maxlength="80" pattern="[A-Za-z0-9._-]{3,80}" value="<?= e($admin['username']) ?>" autocomplete="username" required>
            <div class="form-text">3–80 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div>
        </div>
        <div class="mb-4">
            <label class="form-label" for="current_password">Password saat ini</label>
            <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-cozy-primary" type="submit">Simpan Username</button>
        <a class="btn btn-cozy-outline ms-sm-2 mt-2 mt-sm-0" href="<?= e($url('admin/password')) ?>">Ubah Password</a>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
