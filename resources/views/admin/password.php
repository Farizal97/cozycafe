<?php
ob_start();
?>
<section class="admin-form-card">
    <div class="section-heading">
        <div>
            <span class="section-kicker">PENGATURAN KEAMANAN</span>
            <h2>Ubah password</h2>
        </div>
    </div>
    <p class="form-help">Gunakan password unik minimal 12 karakter.</p>
    <form method="post" action="<?= e($url('admin/password')) ?>" class="admin-form">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label" for="current_password">Password saat ini</label>
            <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="new_password">Password baru</label>
            <input class="form-control" id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Ulangi password baru</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
        </div>
        <button class="btn btn-cozy-primary" type="submit">Simpan Password</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
