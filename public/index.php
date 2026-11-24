<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/app/config/app.php';

$helpers = [
    '/app/helpers/sanitizer.php',
    '/app/helpers/csrf.php',
    '/app/helpers/formatter.php',
    '/app/helpers/rate_limiter.php',
    '/app/helpers/response.php',
    '/app/helpers/flash.php',
];
foreach ($helpers as $helper) {
    require $root . $helper;
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
$url = static fn (string $path): string => $basePath . '/' . ltrim($path, '/');
$assetUrl = $url;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$path = '/' . trim(rawurldecode($path), '/');
if ($path === '//') {
    $path = '/';
}

$customerRoutes = [
    '/' => 'Beranda',
    '/menu' => 'Menu',
    '/cart' => 'Keranjang',
    '/checkout' => 'Checkout',
    '/booking' => 'Reservasi',
    '/track-order' => 'Lacak Pesanan',
    '/track-booking' => 'Lacak Reservasi',
    '/history' => 'Riwayat',
];

$isAdminRoute = $path === '/admin' || str_starts_with($path, '/admin/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($isAdminRoute) {
    require $root . '/app/middleware/AuthMiddleware.php';
    require $root . '/app/middleware/CsrfMiddleware.php';
    require $root . '/app/controllers/AuthController.php';

    $loginUrl = $url('admin/login');
    $adminUrl = $url('admin');

    if ($path === '/admin/login') {
        if (isset($_SESSION['admin_id'])) {
            AuthMiddleware::requireAdmin($loginUrl);
            redirect($adminUrl);
        }

        if ($method === 'POST') {
            require $root . '/app/config/database.php';
            CsrfMiddleware::requireValidToken();
            (new AuthController(database(), $basePath))->login();
        }
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Metode tidak diizinkan.');
        }

        $pageTitle = 'Masuk Admin';
        $flash = consumeFlash();
        require $root . '/resources/views/admin/login.php';
        exit;
    }

    if ($path === '/admin/logout') {
        if ($method !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Metode tidak diizinkan.');
        }
        CsrfMiddleware::requireValidToken();
        AuthMiddleware::requireAdmin($loginUrl);
        require $root . '/app/config/database.php';
        (new AuthController(database(), $basePath))->logout();
    }

    AuthMiddleware::requireAdmin($loginUrl);
    if ($method === 'POST') {
        CsrfMiddleware::requireValidToken();
    } elseif ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET, POST');
        exit('Metode tidak diizinkan.');
    }

    require $root . '/app/config/database.php';
    $authController = new AuthController(database(), $basePath);
    $admin = $authController->currentAdmin((int) $_SESSION['admin_id']);
    $flash = consumeFlash();

    if ($path === '/admin' || $path === '/admin/') {
        $pageTitle = 'Dashboard';
        $usingDefaultPassword = password_verify('password', $admin['password_hash']);
        $view = $root . '/resources/views/admin/dashboard.php';
    } elseif ($path === '/admin/profile') {
        $pageTitle = 'Akun Admin';
        if ($method === 'POST') {
            $authController->updateProfile();
        }
        $view = $root . '/resources/views/admin/profile.php';
    } elseif ($path === '/admin/password') {
        $pageTitle = 'Ubah Password';
        if ($method === 'POST') {
            $authController->updatePassword();
        }
        $view = $root . '/resources/views/admin/password.php';
    } else {
        http_response_code(404);
        $pageTitle = 'Halaman Tidak Ditemukan';
        $view = $root . '/resources/views/admin/404.php';
    }

    require $view;
    exit;
}

if (array_key_exists($path, $customerRoutes)) {
    $pageTitle = $customerRoutes[$path];
    $view = $root . '/resources/views/customer/index.php';
} else {
    http_response_code(404);
    $pageTitle = 'Halaman Tidak Ditemukan';
    $view = $root . '/resources/views/customer/404.php';
}

require $view;
