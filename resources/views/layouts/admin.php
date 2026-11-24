<?php
$adminModuleGroups = [
    ['title' => 'Ringkasan', 'items' => [
        ['label' => 'Dashboard', 'path' => 'admin', 'active' => $path === '/admin'],
    ]],
    ['title' => 'Operasional', 'items' => [
        ['label' => 'Pesanan', 'path' => 'admin/orders', 'active' => str_starts_with($path, '/admin/orders')],
        ['label' => 'Pembayaran', 'path' => 'admin/payments', 'active' => str_starts_with($path, '/admin/payments')],
    ]],
    ['title' => 'Menu & Inventaris', 'items' => [
        ['label' => 'Kategori', 'path' => 'admin/categories', 'active' => str_starts_with($path, '/admin/categories')],
        ['label' => 'Produk', 'path' => 'admin/products', 'active' => str_starts_with($path, '/admin/products')],
        ['label' => 'Stok', 'path' => 'admin/stock', 'active' => str_starts_with($path, '/admin/stock')],
    ]],
    ['title' => 'Reservasi', 'items' => [
        ['label' => 'Reservasi', 'path' => 'admin/bookings', 'active' => str_starts_with($path, '/admin/bookings')],
        ['label' => 'Meja', 'path' => 'admin/tables', 'active' => str_starts_with($path, '/admin/tables')],
        ['label' => 'Area Reservasi', 'path' => 'admin/areas', 'active' => str_starts_with($path, '/admin/areas')],
    ]],
    ['title' => 'Pelanggan & Promosi', 'items' => [
        ['label' => 'Pelanggan', 'path' => 'admin/customers', 'active' => str_starts_with($path, '/admin/customers')],
        ['label' => 'Voucher dan Promosi', 'path' => 'admin/promotions', 'active' => str_starts_with($path, '/admin/promotions') || str_starts_with($path, '/admin/vouchers')],
        ['label' => 'Ulasan', 'path' => 'admin/reviews', 'active' => str_starts_with($path, '/admin/reviews')],
        ['label' => 'Testimonial', 'path' => 'admin/testimonials', 'active' => str_starts_with($path, '/admin/testimonials')],
    ]],
    ['title' => 'Analisis', 'items' => [
        ['label' => 'Laporan', 'path' => 'admin/reports', 'active' => $path === '/admin/reports'],
        ['label' => 'Activity Log', 'path' => 'admin/activity-logs', 'active' => $path === '/admin/activity-logs'],
    ]],
    ['title' => 'Sistem', 'items' => [
        ['label' => 'Pengaturan Kafe', 'path' => 'admin/settings', 'active' => str_starts_with($path, '/admin/settings')],
        ['label' => 'Akun Admin', 'path' => 'admin/profile', 'active' => $path === '/admin/profile'],
        ['label' => 'Backup dan Restore', 'path' => 'admin/backups', 'active' => str_starts_with($path, '/admin/backups')],
    ]],
];
$flashType = is_array($flash ?? null) && in_array($flash['type'] ?? '', ['success', 'danger', 'warning', 'info'], true)
    ? $flash['type']
    : 'info';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2E7D52">
    <title><?= e($pageTitle ?? 'Admin') ?> - CozyCafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($assetUrl('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="admin-page">
    <div class="admin-shell">
        <aside class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
            <div class="admin-sidebar-header">
                <a class="cozy-brand" href="<?= e($url('admin')) ?>" id="adminSidebarLabel">CozyCafe</a>
                <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Tutup menu"></button>
            </div>
            <div class="offcanvas-body admin-sidebar-body">
                <div class="sidebar-caption">MENU UTAMA</div>
                <nav class="admin-nav" aria-label="Navigasi admin">
                    <?php foreach ($adminModuleGroups as $group): ?>
                        <div class="admin-nav-group">
                            <span class="admin-nav-group-title"><?= e($group['title']) ?></span>
                            <?php foreach ($group['items'] as $module): ?>
                                <?php if (isset($module['path'])): ?>
                                    <a class="admin-nav-link<?= $module['active'] ? ' active' : '' ?>" href="<?= e($url($module['path'])) ?>"<?= $module['active'] ? ' aria-current="page"' : '' ?>>
                                        <span><?= e($module['label']) ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="admin-nav-link disabled" aria-disabled="true" title="Modul tersedia pada tahap berikutnya">
                                        <span><?= e($module['label']) ?></span>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <span>CozyCafe Admin</span>
                    <small>Zona waktu Asia/Jakarta</small>
                </div>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Buka menu admin">
                    <span class="menu-icon" aria-hidden="true">☰</span>
                </button>
                <div class="topbar-title">
                    <span class="eyebrow">PANEL ADMIN</span>
                    <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
                </div>
                <div class="topbar-account">
                    <a class="admin-account-link" href="<?= e($url('admin/profile')) ?>">
                        <span class="admin-avatar" aria-hidden="true"><?= e(strtoupper(substr((string) ($admin['username'] ?? 'A'), 0, 1))) ?></span>
                        <span class="d-none d-sm-inline"><?= e($admin['username'] ?? 'Admin') ?></span>
                    </a>
                    <form method="post" action="<?= e($url('admin/logout')) ?>">
                        <?= csrfField() ?>
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="admin-content">
                <?php if (is_array($flash ?? null)): ?>
                    <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                        <?= e($flash['message'] ?? '') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
                    </div>
                <?php endif; ?>
                <?= $content ?? '' ?>
            </main>
            <footer class="admin-footer">CozyCafe · Dasbor operasional</footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e($assetUrl('assets/js/app.js')) ?>"></script>
</body>
</html>
