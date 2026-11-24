<?php
ob_start();
?>
<section class="dashboard-welcome">
    <div class="welcome-copy">
        <span class="section-kicker">RINGKASAN</span>
        <h2>Selamat datang, <?= e($admin['username']) ?>.</h2>
        <p>Kelola operasional CozyCafe dari satu tempat.</p>
    </div>
    <div class="welcome-mark" aria-hidden="true">C</div>
</section>

<?php if ($usingDefaultPassword): ?>
    <div class="alert alert-warning security-alert" role="alert">
        <strong>Keamanan akun:</strong> Anda masih menggunakan password awal. Segera
        <a href="<?= e($url('admin/password')) ?>" class="alert-link">ganti password</a>
        sebelum akun digunakan lebih lanjut.
    </div>
<?php endif; ?>

<section class="dashboard-placeholder">
    <div class="section-heading">
        <div>
            <span class="section-kicker">TAHAP 3</span>
            <h2>Dasbor operasional</h2>
        </div>
        <span class="status-pill">Fondasi aktif</span>
    </div>
    <p>Dashboard, katalog, inventaris, laporan, dan audit aktivitas tersedia. Modul operasional lain akan diaktifkan bertahap.</p>
    <a class="btn btn-cozy-primary" href="<?= e($url('admin/profile')) ?>">Kelola Akun Admin</a>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
