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

if (preg_match('/\A\/media\/products\/([^\/]+)\z/', $path, $imageMatch)) {
    require $root . '/app/controllers/ProductController.php';
    ProductController::serveImage($imageMatch[1]);
}

$productDetailMatch = preg_match('/\A\/product\/([1-9][0-9]*)\z/', $path, $productMatch) === 1;
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

    if ($path === '/admin/categories'
        || $path === '/admin/categories/create'
        || $path === '/admin/categories/edit'
        || $path === '/admin/categories/store'
        || $path === '/admin/categories/update'
        || $path === '/admin/categories/delete') {
        require $root . '/app/controllers/CategoryController.php';
        $categoryController = new CategoryController(database(), $basePath);
        if ($path === '/admin/categories') {
            $categories = $categoryController->all();
            $pageTitle = 'Kategori';
            $view = $root . '/resources/views/admin/categories.php';
        } elseif ($path === '/admin/categories/create' && $method === 'GET') {
            $category = null;
            $pageTitle = 'Tambah Kategori';
            $view = $root . '/resources/views/admin/category_form.php';
        } elseif ($path === '/admin/categories/edit' && $method === 'GET') {
            $categoryId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $category = $categoryId === false ? null : $categoryController->find((int) $categoryId);
            if ($category === null) {
                http_response_code(404);
                $pageTitle = 'Kategori Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $pageTitle = 'Ubah Kategori';
                $view = $root . '/resources/views/admin/category_form.php';
            }
        } elseif ($path === '/admin/categories/store' && $method === 'POST') {
            $categoryController->store();
        } elseif ($path === '/admin/categories/update' && $method === 'POST') {
            $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $categoryController->update($categoryId === false ? 0 : (int) $categoryId);
        } elseif ($path === '/admin/categories/delete' && $method === 'POST') {
            $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $categoryController->delete($categoryId === false ? 0 : (int) $categoryId);
        } else {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Metode tidak diizinkan.');
        }
    } elseif ($path === '/admin/products'
        || $path === '/admin/products/create'
        || $path === '/admin/products/edit'
        || $path === '/admin/products/store'
        || $path === '/admin/products/update'
        || $path === '/admin/products/delete') {
        require $root . '/app/controllers/ProductController.php';
        require $root . '/app/controllers/CategoryController.php';
        $productController = new ProductController(database(), $basePath);
        $categoryController = new CategoryController(database(), $basePath);
        if ($path === '/admin/products') {
            $products = $productController->adminProducts();
            $pageTitle = 'Produk';
            $view = $root . '/resources/views/admin/products.php';
        } elseif ($path === '/admin/products/create' && $method === 'GET') {
            $categories = array_values(array_filter(
                $categoryController->all(),
                static fn (array $category): bool => (int) $category['is_active'] === 1
            ));
            $product = null;
            $pageTitle = 'Tambah Produk';
            $view = $root . '/resources/views/admin/product_form.php';
        } elseif ($path === '/admin/products/edit' && $method === 'GET') {
            $productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $product = $productId === false ? null : $productController->adminProduct((int) $productId);
            if ($product === null) {
                http_response_code(404);
                $pageTitle = 'Produk Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $categories = $categoryController->all();
                $pageTitle = 'Ubah Produk';
                $view = $root . '/resources/views/admin/product_form.php';
            }
        } elseif ($path === '/admin/products/store' && $method === 'POST') {
            $productController->store();
        } elseif ($path === '/admin/products/update' && $method === 'POST') {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $productController->update($productId === false ? 0 : (int) $productId);
        } elseif ($path === '/admin/products/delete' && $method === 'POST') {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $productController->delete($productId === false ? 0 : (int) $productId);
        } else {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Metode tidak diizinkan.');
        }
    } elseif ($path === '/admin/stock'
        || $path === '/admin/stock/add'
        || $path === '/admin/stock/status'
        || $path === '/admin/stock/bulk-status') {
        require $root . '/app/controllers/StockController.php';
        $stockController = new StockController(database(), $basePath);
        if ($path === '/admin/stock' && $method === 'GET') {
            $products = $stockController->products();
            $pageTitle = 'Stok';
            $view = $root . '/resources/views/admin/stock.php';
        } elseif ($path === '/admin/stock/add' && $method === 'POST') {
            $stockController->addStock();
        } elseif ($path === '/admin/stock/status' && $method === 'POST') {
            $stockController->updateStatus();
        } elseif ($path === '/admin/stock/bulk-status' && $method === 'POST') {
            $stockController->bulkUpdateStatus();
        } else {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Metode tidak diizinkan.');
        }
    } elseif (in_array($path, [
        '/admin/orders', '/admin/orders/detail', '/admin/orders/status', '/admin/orders/cancellation',
        '/admin/payments', '/admin/payments/verify', '/admin/payments/cash', '/admin/payments/refund', '/admin/payments/proof',
        '/admin/bookings', '/admin/bookings/status', '/admin/bookings/cancellation',
        '/admin/areas', '/admin/areas/create', '/admin/areas/edit', '/admin/areas/store',
        '/admin/areas/update', '/admin/areas/delete',
        '/admin/tables', '/admin/tables/create', '/admin/tables/edit', '/admin/tables/store',
        '/admin/tables/update',
        '/admin/customers', '/admin/customers/detail', '/admin/customers/anonymize',
        '/admin/promotions', '/admin/promotions/create', '/admin/promotions/edit',
        '/admin/promotions/store', '/admin/promotions/update', '/admin/promotions/delete',
        '/admin/vouchers/create', '/admin/vouchers/edit', '/admin/vouchers/store',
        '/admin/vouchers/update', '/admin/vouchers/delete',
        '/admin/reviews', '/admin/reviews/moderate', '/admin/reviews/delete',
        '/admin/testimonials', '/admin/testimonials/create', '/admin/testimonials/status',
        '/admin/testimonials/delete',
        '/admin/settings', '/admin/settings/save', '/admin/settings/hours',
        '/admin/settings/closures/create', '/admin/settings/closures/delete',
        '/admin/backups', '/admin/backups/create', '/admin/backups/download',
        '/admin/backups/restore',
    ], true)) {
        require $root . '/app/controllers/AdminOperationsController.php';
        $operations = new AdminOperationsController(database(), $basePath);
        if ($path === '/admin/orders' && $method === 'GET') {
            try {
                $orderData = $operations->orders($_GET);
            } catch (DomainException $exception) {
                setFlash('danger', $exception->getMessage());
                redirect($url('admin/orders'));
            }
            $pageTitle = 'Pesanan';
            $view = $root . '/resources/views/admin/orders.php';
        } elseif ($path === '/admin/orders/detail' && $method === 'GET') {
            $orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $order = $orderId === false || $orderId < 1 ? null : $operations->orderDetail((int) $orderId);
            if ($order === null) {
                http_response_code(404);
                $pageTitle = 'Pesanan Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $pageTitle = 'Detail Pesanan';
                $view = $root . '/resources/views/admin/order_detail.php';
            }
        } elseif ($path === '/admin/orders/status' && $method === 'POST') {
            $operations->updateOrderStatus();
        } elseif ($path === '/admin/orders/cancellation' && $method === 'POST') {
            $operations->decideOrderCancellation();
        } elseif ($path === '/admin/payments' && $method === 'GET') {
            $payments = $operations->payments();
            $pageTitle = 'Pembayaran';
            $view = $root . '/resources/views/admin/payments.php';
        } elseif ($path === '/admin/payments/verify' && $method === 'POST') {
            $operations->verifyPayment();
        } elseif ($path === '/admin/payments/cash' && $method === 'POST') {
            $operations->confirmCashPayment();
        } elseif ($path === '/admin/payments/refund' && $method === 'POST') {
            $operations->createRefund();
        } elseif ($path === '/admin/payments/proof' && $method === 'GET') {
            $proofId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $proof = $proofId === false || $proofId < 1 ? null : $operations->paymentProof((int) $proofId);
            $storageRoot = realpath($root . DIRECTORY_SEPARATOR . 'storage');
            $relativePath = is_array($proof) ? $proof['file_path'] : '';
            if ($storageRoot === false || !is_string($relativePath) || $relativePath === ''
                || str_contains($relativePath, "\0") || $relativePath[0] === '/' || $relativePath[0] === '\\'
                || preg_match('/\A[A-Za-z]:/', $relativePath) === 1) {
                http_response_code(404);
                exit('Bukti pembayaran tidak ditemukan.');
            }
            $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
            $proofPath = realpath($storageRoot . DIRECTORY_SEPARATOR . $relativePath);
            $storagePrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($proofPath === false
                || strncasecmp($proofPath, $storagePrefix, strlen($storagePrefix)) !== 0
                || !is_file($proofPath) || !is_readable($proofPath)) {
                http_response_code(404);
                exit('Bukti pembayaran tidak ditemukan.');
            }
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($proofPath);
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                http_response_code(415);
                exit('Format bukti pembayaran tidak didukung.');
            }
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . (string) filesize($proofPath));
            header('Content-Disposition: inline; filename="bukti-pembayaran"');
            header('Cache-Control: private, no-store, max-age=0');
            header('X-Content-Type-Options: nosniff');
            readfile($proofPath);
            exit;
        } elseif ($path === '/admin/bookings' && $method === 'GET') {
            try {
                $bookingData = $operations->bookings($_GET);
            } catch (DomainException $exception) {
                setFlash('danger', $exception->getMessage());
                redirect($url('admin/bookings'));
            }
            $pageTitle = 'Reservasi';
            $view = $root . '/resources/views/admin/bookings.php';
        } elseif ($path === '/admin/bookings/status' && $method === 'POST') {
            $operations->updateBookingStatus();
        } elseif ($path === '/admin/bookings/cancellation' && $method === 'POST') {
            $operations->decideBookingCancellation();
        } elseif ($path === '/admin/areas' && $method === 'GET') {
            $areas = $operations->areas();
            $pageTitle = 'Area Reservasi';
            $view = $root . '/resources/views/admin/areas.php';
        } elseif ($path === '/admin/areas/create' && $method === 'GET') {
            $area = null;
            $pageTitle = 'Tambah Area';
            $view = $root . '/resources/views/admin/area_form.php';
        } elseif ($path === '/admin/areas/edit' && $method === 'GET') {
            $areaId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $area = $areaId === false || $areaId < 1 ? null : $operations->area((int) $areaId);
            if ($area === null) {
                http_response_code(404);
                $pageTitle = 'Area Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $pageTitle = 'Ubah Area';
                $view = $root . '/resources/views/admin/area_form.php';
            }
        } elseif ($path === '/admin/areas/store' && $method === 'POST') {
            $operations->saveArea(null);
        } elseif ($path === '/admin/areas/update' && $method === 'POST') {
            $areaId = filter_var($_POST['area_id'] ?? null, FILTER_VALIDATE_INT);
            $operations->saveArea($areaId === false || $areaId < 1 ? 0 : (int) $areaId);
        } elseif ($path === '/admin/areas/delete' && $method === 'POST') {
            $areaId = filter_var($_POST['area_id'] ?? null, FILTER_VALIDATE_INT);
            $operations->deleteArea($areaId === false || $areaId < 1 ? 0 : (int) $areaId);
        } elseif ($path === '/admin/tables' && $method === 'GET') {
            $tables = $operations->tables();
            $pageTitle = 'Meja';
            $view = $root . '/resources/views/admin/tables.php';
        } elseif ($path === '/admin/tables/create' && $method === 'GET') {
            $table = null;
            $areas = $operations->areasForSelect();
            $pageTitle = 'Tambah Meja';
            $view = $root . '/resources/views/admin/table_form.php';
        } elseif ($path === '/admin/tables/edit' && $method === 'GET') {
            $tableId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $table = $tableId === false || $tableId < 1 ? null : $operations->table((int) $tableId);
            if ($table === null) {
                http_response_code(404);
                $pageTitle = 'Meja Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $areas = $operations->areasForSelect();
                $pageTitle = 'Ubah Meja';
                $view = $root . '/resources/views/admin/table_form.php';
            }
        } elseif ($path === '/admin/tables/store' && $method === 'POST') {
            $operations->saveTable(null);
        } elseif ($path === '/admin/tables/update' && $method === 'POST') {
            $tableId = filter_var($_POST['table_id'] ?? null, FILTER_VALIDATE_INT);
            $operations->saveTable($tableId === false || $tableId < 1 ? 0 : (int) $tableId);
        } elseif ($path === '/admin/customers' && $method === 'GET') {
            $search = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
            $customers = $operations->customers($search);
            $search = trim(mb_substr($search, 0, 80));
            $pageTitle = 'Pelanggan';
            $view = $root . '/resources/views/admin/customers.php';
        } elseif ($path === '/admin/customers/detail' && $method === 'GET') {
            $customerId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            $customer = $customerId === false || $customerId < 1 ? null : $operations->customer((int) $customerId);
            if ($customer === null) {
                http_response_code(404);
                $pageTitle = 'Pelanggan Tidak Ditemukan';
                $view = $root . '/resources/views/admin/404.php';
            } else {
                $pageTitle = 'Detail Pelanggan';
                $view = $root . '/resources/views/admin/customer_detail.php';
            }
        } elseif ($path === '/admin/customers/anonymize' && $method === 'POST') {
            $customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT);
            $operations->anonymizeCustomer($customerId === false || $customerId < 1 ? 0 : (int) $customerId);
        } elseif (str_starts_with($path, '/admin/promotions') || str_starts_with($path, '/admin/vouchers')
            || str_starts_with($path, '/admin/reviews') || str_starts_with($path, '/admin/testimonials')
            || str_starts_with($path, '/admin/settings') || str_starts_with($path, '/admin/backups')) {
            require $root . '/app/controllers/AdminManagementController.php';
            $management = new AdminManagementController(database(), $basePath, $root);
            if ($path === '/admin/promotions' && $method === 'GET') {
                $promotionData = $management->promotions();
                $pageTitle = 'Voucher dan Promosi';
                $view = $root . '/resources/views/admin/promotions.php';
            } elseif (($path === '/admin/promotions/create' || $path === '/admin/vouchers/create') && $method === 'GET') {
                $discount = null;
                $discountType = $path === '/admin/vouchers/create' ? 'voucher' : 'promotion';
                $pageTitle = $discountType === 'voucher' ? 'Buat Voucher' : 'Buat Promosi';
                $view = $root . '/resources/views/admin/discount_form.php';
            } elseif (($path === '/admin/promotions/edit' || $path === '/admin/vouchers/edit') && $method === 'GET') {
                $discountId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
                $discountType = str_starts_with($path, '/admin/vouchers') ? 'voucher' : 'promotion';
                $discount = $discountId === false || $discountId < 1
                    ? null
                    : ($discountType === 'voucher'
                        ? $management->voucher((int) $discountId)
                        : $management->promotion((int) $discountId));
                if ($discount === null) {
                    http_response_code(404);
                    $pageTitle = 'Diskon Tidak Ditemukan';
                    $view = $root . '/resources/views/admin/404.php';
                } else {
                    $pageTitle = $discountType === 'voucher' ? 'Ubah Voucher' : 'Ubah Promosi';
                    $view = $root . '/resources/views/admin/discount_form.php';
                }
            } elseif ($path === '/admin/vouchers/store' && $method === 'POST') {
                $management->saveVoucher(null);
            } elseif ($path === '/admin/vouchers/update' && $method === 'POST') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $management->saveVoucher($id === false || $id < 1 ? 0 : (int) $id);
            } elseif ($path === '/admin/promotions/store' && $method === 'POST') {
                $management->savePromotion(null);
            } elseif ($path === '/admin/promotions/update' && $method === 'POST') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $management->savePromotion($id === false || $id < 1 ? 0 : (int) $id);
            } elseif ($path === '/admin/vouchers/delete' && $method === 'POST') {
                $management->deleteDiscount('vouchers', 'voucher');
            } elseif ($path === '/admin/promotions/delete' && $method === 'POST') {
                $management->deleteDiscount('promotions', 'promotion');
            } elseif ($path === '/admin/reviews' && $method === 'GET') {
                $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
                try {
                    $reviewData = $management->reviews($status);
                } catch (DomainException $exception) {
                    setFlash('danger', $exception->getMessage());
                    redirect($url('admin/reviews'));
                }
                $pageTitle = 'Ulasan';
                $view = $root . '/resources/views/admin/reviews.php';
            } elseif ($path === '/admin/reviews/moderate' && $method === 'POST') {
                $management->moderateReview();
            } elseif ($path === '/admin/reviews/delete' && $method === 'POST') {
                $management->deleteReview();
            } elseif ($path === '/admin/testimonials' && $method === 'GET') {
                $testimonials = $management->testimonials();
                $availableReviews = $management->availableTestimonialReviews();
                $pageTitle = 'Testimonial';
                $view = $root . '/resources/views/admin/testimonials.php';
            } elseif ($path === '/admin/testimonials/create' && $method === 'POST') {
                $management->createTestimonial();
            } elseif ($path === '/admin/testimonials/status' && $method === 'POST') {
                $management->updateTestimonial();
            } elseif ($path === '/admin/testimonials/delete' && $method === 'POST') {
                $management->deleteTestimonial();
            } elseif ($path === '/admin/settings' && $method === 'GET') {
                $settingsData = $management->settings();
                $pageTitle = 'Pengaturan Kafe';
                $view = $root . '/resources/views/admin/settings.php';
            } elseif ($path === '/admin/settings/save' && $method === 'POST') {
                $management->saveSettings();
            } elseif ($path === '/admin/settings/hours' && $method === 'POST') {
                $management->saveOperatingHours();
            } elseif ($path === '/admin/settings/closures/create' && $method === 'POST') {
                $management->addSpecialClosure();
            } elseif ($path === '/admin/settings/closures/delete' && $method === 'POST') {
                $management->deleteSpecialClosure();
            } elseif ($path === '/admin/backups' && $method === 'GET') {
                $backupLogs = $management->backups();
                $pageTitle = 'Backup dan Restore';
                $view = $root . '/resources/views/admin/backups.php';
            } elseif ($path === '/admin/backups/create' && $method === 'POST') {
                try {
                    $management->createBackup();
                } catch (RuntimeException|PDOException $exception) {
                    setFlash('danger', 'Backup gagal dibuat. Periksa ruang penyimpanan dan koneksi database.');
                    redirect($url('admin/backups'));
                }
            } elseif ($path === '/admin/backups/download' && $method === 'GET') {
                $filename = is_string($_GET['file'] ?? null) ? $_GET['file'] : '';
                $backupPath = $management->downloadBackup($filename);
                if ($backupPath === null) {
                    http_response_code(404);
                    exit('Backup tidak ditemukan.');
                }
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Length: ' . (string) filesize($backupPath));
                header('Content-Disposition: attachment; filename="' . basename($backupPath) . '"');
                header('Cache-Control: private, no-store, max-age=0');
                header('X-Content-Type-Options: nosniff');
                readfile($backupPath);
                exit;
            } elseif ($path === '/admin/backups/restore' && $method === 'POST') {
                try {
                    $management->restoreBackup();
                } catch (PDOException|RuntimeException $exception) {
                    setFlash('danger', 'Restore gagal. Database tidak dapat dipulihkan; periksa log aplikasi.');
                    redirect($url('admin/backups'));
                }
            } else {
                http_response_code(405);
                header('Allow: GET, POST');
                exit('Metode tidak diizinkan.');
            }
        } else {
            http_response_code(405);
            header('Allow: GET, POST');
            exit('Metode tidak diizinkan.');
        }
    } elseif ($path === '/admin' || $path === '/admin/') {
        $pageTitle = 'Dashboard';
        $usingDefaultPassword = password_verify('password', $admin['password_hash']);
        $view = $root . '/resources/views/admin/dashboard.php';
    } elseif ($path === '/admin/reports' || $path === '/admin/reports/export') {
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            exit('Metode tidak diizinkan.');
        }
        require $root . '/app/controllers/AdminReportsController.php';
        $reportsController = new AdminReportsController(database());
        try {
            $reportFilters = $reportsController->filters($_GET);
        } catch (DomainException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($url('admin/reports'));
        }
        if ($path === '/admin/reports/export') {
            $reportsController->exportCsv($reportFilters);
        }
        $requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
        if ($requestedPage === false || $requestedPage < 1) {
            $requestedPage = 1;
        }
        $reportData = $reportsController->report($reportFilters, (int) $requestedPage);
        $paymentMethods = $reportsController->paymentMethods();
        $pageTitle = 'Laporan';
        $view = $root . '/resources/views/admin/reports.php';
    } elseif ($path === '/admin/activity-logs') {
        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            exit('Metode tidak diizinkan.');
        }
        require $root . '/app/controllers/AdminReportsController.php';
        $reportsController = new AdminReportsController(database());
        $requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
        if ($requestedPage === false || $requestedPage < 1) {
            $requestedPage = 1;
        }
        $activityLogData = $reportsController->activityLogs((int) $requestedPage);
        $pageTitle = 'Activity Log';
        $view = $root . '/resources/views/admin/activity_logs.php';
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

if ($path === '/') {
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/ProductController.php';
    $homeData = (new ProductController(database(), $basePath))->homepageData();
    $pageTitle = $customerRoutes[$path];
    $view = $root . '/resources/views/customer/index.php';
} elseif ($path === '/menu') {
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/ProductController.php';
    $productController = new ProductController(database(), $basePath);
    $categories = $productController->categories();
    $search = $_GET['q'] ?? '';
    $search = is_string($search) ? trim(substr($search, 0, 100)) : '';
    $categoryId = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT);
    $selectedCategory = $categoryId === false || $categoryId === null || $categoryId < 1
        ? null
        : (int) $categoryId;
    if ($selectedCategory !== null
        && !in_array($selectedCategory, array_map(static fn (array $category): int => (int) $category['id'], $categories), true)) {
        $selectedCategory = null;
    }
    $products = $productController->catalog($search, $selectedCategory);
    $pageTitle = $customerRoutes[$path];
    $view = $root . '/resources/views/customer/menu.php';
} elseif ($productDetailMatch) {
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/ProductController.php';
    $product = (new ProductController(database(), $basePath))->productDetail((int) $productMatch[1]);
    if ($product === null) {
        http_response_code(404);
    }
    $pageTitle = $product === null ? 'Produk Tidak Ditemukan' : $product['name'];
    $view = $root . ($product === null
        ? '/resources/views/customer/product_not_found.php'
        : '/resources/views/customer/product.php');
} elseif ($path === '/cart') {
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('Metode tidak diizinkan.');
    }
    $pageTitle = 'Keranjang';
    $view = $root . '/resources/views/customer/cart.php';
} elseif ($path === '/checkout') {
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/CheckoutController.php';
    $checkoutController = new CheckoutController(database());
    $paymentMethods = $checkoutController->paymentMethods();
    $formValues = $checkoutController->consumeDraft();
    $pageTitle = 'Checkout';
    $view = $root . '/resources/views/customer/checkout.php';
} elseif ($path === '/checkout/review' || $path === '/checkout/confirm') {
    if ($method !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/middleware/CsrfMiddleware.php';
    CsrfMiddleware::requireValidToken();
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/CheckoutController.php';
    $checkoutController = new CheckoutController(database());

    if ($path === '/checkout/review') {
        try {
            $checkout = $checkoutController->review($_POST);
            $pageTitle = 'Tinjau Pesanan';
            $view = $root . '/resources/views/customer/checkout_review.php';
        } catch (DomainException $exception) {
            $checkoutController->saveDraft($_POST);
            setFlash('danger', $exception->getMessage());
            redirect($url('checkout'));
        }
    } else {
        try {
            $createdOrder = $checkoutController->createOrder($_POST);
            redirect($url('order-created?code=' . rawurlencode($createdOrder['order_code'])));
        } catch (DomainException $exception) {
            $checkoutController->saveDraft($_POST);
            setFlash('danger', $exception->getMessage());
            redirect($url('checkout'));
        }
    }
} elseif ($path === '/order-created') {
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/CheckoutController.php';
    $checkoutController = new CheckoutController(database());
    $orderCode = $_GET['code'] ?? '';
    $order = is_string($orderCode) ? $checkoutController->orderCreated($orderCode) : null;
    if ($order === null) {
        http_response_code(404);
        $pageTitle = 'Pesanan Tidak Ditemukan';
        $view = $root . '/resources/views/customer/404.php';
    } else {
        $clearCart = $checkoutController->consumeCartClearFlag($order['order_code']);
        $pageTitle = 'Pesanan Dibuat';
        $view = $root . '/resources/views/customer/order_created.php';
    }
} elseif ($path === '/booking') {
    if (!in_array($method, ['GET', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/BookingController.php';
    $bookingController = new BookingController(database());
    if ($method === 'POST') {
        require $root . '/app/middleware/CsrfMiddleware.php';
        CsrfMiddleware::requireValidToken();
        try {
            $_SESSION['booking_confirmation'] = $bookingController->create($_POST);
            unset($_SESSION['booking_draft']);
            redirect($url('booking'));
        } catch (DomainException $exception) {
            $bookingController->saveDraft($_POST);
            setFlash('danger', $exception->getMessage());
            $query = http_build_query([
                'show_tables' => 1,
                'area' => (int) ($_POST['area_id'] ?? 0),
                'slot' => is_string($_POST['slot_start'] ?? null) ? $_POST['slot_start'] : '',
                'guests' => (int) ($_POST['guest_count'] ?? 0),
            ]);
            redirect($url('booking?' . $query));
        }
    }

    $areas = $bookingController->areas();
    $bookingSlots = $bookingController->slotsForToday();
    $bookingDraft = $bookingController->consumeDraft();
    $bookingConfirmation = $bookingController->consumeConfirmation();
    $today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
    $areaId = filter_var($_GET['area'] ?? $bookingDraft['area_id'] ?? null, FILTER_VALIDATE_INT);
    $areaId = $areaId === false || $areaId === null ? 0 : (int) $areaId;
    if (!in_array($areaId, array_map(static fn (array $area): int => (int) $area['id'], $areas), true)) {
        $areaId = isset($areas[0]) ? (int) $areas[0]['id'] : 0;
    }
    $slotStart = $_GET['slot'] ?? $bookingDraft['slot_start'] ?? '';
    $slotStart = is_string($slotStart) ? $slotStart : '';
    $selectedSlot = $bookingController->slot($slotStart);
    if ($selectedSlot !== null
        && !in_array($selectedSlot['start'], array_column($bookingSlots, 'start'), true)) {
        $selectedSlot = null;
    }
    if ($selectedSlot === null && isset($bookingSlots[0])) {
        $selectedSlot = $bookingSlots[0];
    }
    $guestCount = filter_var($_GET['guests'] ?? $bookingDraft['guest_count'] ?? 2, FILTER_VALIDATE_INT);
    $guestCount = $guestCount === false || $guestCount < 1 || $guestCount > 255 ? 2 : (int) $guestCount;
    $bookingTables = [];
    $showTables = ($_GET['show_tables'] ?? '') === '1';
    if ($showTables && $selectedSlot !== null && $areaId > 0) {
        $bookingTables = $bookingController->tables($areaId, $guestCount, $selectedSlot);
    }
    $pageTitle = 'Reservasi';
    $view = $root . '/resources/views/customer/booking.php';
} elseif ($path === '/track-order/status') {
    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/OrderTrackingController.php';
    $trackingController = new OrderTrackingController(database());
    $order = $trackingController->trackedOrder();
    if ($order === null) {
        jsonResponse(['error' => 'Sesi pelacakan berakhir. Masukkan kode pesanan dan nomor telepon kembali.'], 401);
    }
    header('Cache-Control: no-store, private');
    jsonResponse(['order' => $trackingController->payload($order)]);
} elseif ($path === '/track-order') {
    if (!in_array($method, ['GET', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        exit('Metode tidak diizinkan.');
    }
    require $root . '/app/config/database.php';
    require $root . '/app/controllers/OrderTrackingController.php';
    $trackingController = new OrderTrackingController(database());
    if ($method === 'POST') {
        require $root . '/app/middleware/CsrfMiddleware.php';
        CsrfMiddleware::requireValidToken();
        $orderCode = is_string($_POST['order_code'] ?? null) ? $_POST['order_code'] : '';
        $phone = is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '';
        try {
            if (!$trackingController->verify($orderCode, $phone)) {
                setFlash('danger', 'Kode pesanan atau nomor telepon tidak cocok.');
                redirect($url('track-order?code=' . rawurlencode(strtoupper(trim($orderCode)))));
            }
            redirect($url('track-order'));
        } catch (DomainException $exception) {
            setFlash('warning', $exception->getMessage());
            redirect($url('track-order?code=' . rawurlencode(strtoupper(trim($orderCode)))));
        }
    }
    if (($_GET['reset'] ?? '') === '1') {
        $trackingController->clearTracking();
        redirect($url('track-order'));
    }
    $order = $trackingController->trackedOrder();
    $pageTitle = 'Lacak Pesanan';
    $view = $root . '/resources/views/customer/track_order.php';
} elseif (array_key_exists($path, $customerRoutes)) {
    $pageTitle = $customerRoutes[$path];
    $view = $root . '/resources/views/customer/placeholder.php';
} else {
    http_response_code(404);
    $pageTitle = 'Halaman Tidak Ditemukan';
    $view = $root . '/resources/views/customer/404.php';
}

$flash = consumeFlash();
require $view;
