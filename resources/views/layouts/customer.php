<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2E7D52">
    <title><?= e($pageTitle ?? 'CozyCafe') ?> - CozyCafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($assetUrl('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="<?= !empty($isAuthPage) ? 'auth-page' : 'customer-page' ?>">
    <?php if (empty($isAuthPage)): ?>
    <nav class="navbar navbar-expand-lg customer-navbar">
        <div class="container">
            <a class="navbar-brand cozy-brand" href="<?= e($url('')) ?>">CozyCafe</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavigation" aria-controls="customerNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="customerNavigation">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="<?= e($url('menu')) ?>">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e($url('booking')) ?>">Reservasi</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e($url('track-order')) ?>">Lacak Pesanan</a></li>
                    <li class="nav-item">
                        <a class="nav-link cart-nav-link" href="<?= e($url('cart')) ?>">
                            Keranjang
                            <span class="badge rounded-pill cart-count-badge" data-cart-count hidden>0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <?= $content ?? '' ?>

    <?php if (empty($isAuthPage)): ?>
    <footer class="customer-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <a class="footer-brand" href="<?= e($url('')) ?>">CozyCafe</a>
                <p class="mb-0 mt-2">Tempat nyaman untuk menikmati kopi dan makanan.</p>
            </div>
            <div class="footer-info">
                <div>Setiap hari, 09.00–22.00 WIB</div>
                <div>Informasi alamat dan telepon tersedia di lokasi kafe.</div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e($assetUrl('assets/js/app.js')) ?>"></script>
</body>
</html>
