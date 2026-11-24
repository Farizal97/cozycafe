<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/LogService.php';

final class ProductController
{
    private const MAX_IMAGE_BYTES = 5242880;
    private const ALLOWED_IMAGES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    private readonly LogService $logs;
    private readonly string $storageDirectory;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath
    ) {
        $this->logs = new LogService($pdo);
        $this->storageDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
    }

    public function categories(): array
    {
        return $this->pdo->query(
            'SELECT id, name, description
             FROM categories
             WHERE is_active = 1
             ORDER BY name ASC'
        )->fetchAll();
    }

    public function adminProducts(): array
    {
        return $this->pdo->query(
            'SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                    p.stock, p.min_stock, p.min_order_quantity, p.max_order_quantity,
                    p.availability_status, p.is_active, p.has_order_history,
                    c.name AS category_name,
                    CASE
                        WHEN p.has_order_history = 1 OR EXISTS (
                            SELECT 1 FROM order_items oi WHERE oi.product_id = p.id
                        ) THEN 1 ELSE 0
                    END AS is_historical
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             ORDER BY p.name ASC'
        )->fetchAll();
    }

    public function adminProduct(int $productId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                    p.stock, p.min_stock, p.min_order_quantity, p.max_order_quantity,
                    p.availability_status, p.is_active, p.has_order_history,
                    CASE
                        WHEN p.has_order_history = 1 OR EXISTS (
                            SELECT 1 FROM order_items oi WHERE oi.product_id = p.id
                        ) THEN 1 ELSE 0
                    END AS is_historical
             FROM products p
             WHERE p.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function catalog(string $search = '', ?int $categoryId = null): array
    {
        $where = ['p.is_active = 1', 'c.is_active = 1'];
        $parameters = [];
        if ($search !== '') {
            $where[] = 'p.name LIKE :search';
            $parameters['search'] = '%' . $search . '%';
        }
        if ($categoryId !== null && $categoryId > 0) {
            $where[] = 'p.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }

        $statement = $this->pdo->prepare(
            'SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                    p.stock, p.min_order_quantity, p.max_order_quantity,
                    p.availability_status, c.name AS category_name
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.name ASC'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function productDetail(int $productId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                    p.stock, p.min_order_quantity, p.max_order_quantity,
                    p.availability_status, c.name AS category_name
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.is_active = 1 AND c.is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function homepageData(): array
    {
        $categories = $this->pdo->query(
            'SELECT c.id, c.name, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             WHERE c.is_active = 1
             GROUP BY c.id, c.name
             ORDER BY c.name ASC'
        )->fetchAll();

        $featured = $this->catalog('', null);
        $featured = array_slice($featured, 0, 4);

        $popular = $this->pdo->query(
            "SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                    p.stock, p.min_order_quantity, p.max_order_quantity,
                    p.availability_status, c.name AS category_name,
                    SUM(oi.quantity) AS units_sold
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id AND c.is_active = 1
             INNER JOIN order_items oi ON oi.product_id = p.id
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE p.is_active = 1
               AND o.order_status NOT IN ('cancelled', 'expired')
             GROUP BY p.id, p.category_id, p.name, p.description, p.image, p.price,
                      p.stock, p.min_order_quantity, p.max_order_quantity,
                      p.availability_status, c.name
             ORDER BY units_sold DESC, p.name ASC
             LIMIT 4"
        )->fetchAll();
        $testimonials = $this->pdo->query(
            "SELECT r.comment, COALESCE(c.name, 'Pelanggan CozyCafe') AS customer_name
             FROM testimonials t
             INNER JOIN reviews r ON r.id = t.review_id
             LEFT JOIN customers c ON c.id = r.customer_id
             WHERE t.is_approved = 1
               AND t.is_visible = 1
               AND r.moderation_status = 'approved'
               AND r.comment IS NOT NULL
               AND r.comment <> ''
             ORDER BY t.created_at DESC
             LIMIT 3"
        )->fetchAll();

        $settings = $this->pdo->query(
            "SELECT setting_key, setting_value
             FROM cafe_settings
             WHERE setting_key IN ('cafe_address', 'cafe_phone')"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'categories' => $categories,
            'featured' => $featured,
            'popular' => $popular,
            'testimonials' => $testimonials,
            'address' => trim((string) ($settings['cafe_address'] ?? '')),
            'phone' => trim((string) ($settings['cafe_phone'] ?? '')),
        ];
    }

    public function store(): never
    {
        try {
            $values = $this->validatedValues(null);
            $upload = $this->validatedImageUpload();
        } catch (DomainException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($this->url('admin/products/create'));
        }

        $newImage = $upload === null ? null : $this->storeImage($upload);
        $values['image'] = $newImage;

        try {
            $this->pdo->beginTransaction();
            $this->assertUsableCategory((int) $values['category_id'], null);
            $statement = $this->pdo->prepare(
                'INSERT INTO products
                    (category_id, name, description, image, price, stock, min_stock,
                     min_order_quantity, max_order_quantity, availability_status, is_active)
                 VALUES
                    (:category_id, :name, :description, :image, :price, :stock, :min_stock,
                     :min_order_quantity, :max_order_quantity, :availability_status, :is_active)'
            );
            $statement->execute($values);
            $productId = (int) $this->pdo->lastInsertId();
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'product_created',
                'product',
                $productId,
                null,
                $this->loggableValues($values)
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->removeStoredImage($newImage);

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/products/create'));
            }

            throw $exception;
        }

        setFlash('success', 'Produk berhasil ditambahkan.');
        redirect($this->url('admin/products'));
    }

    public function update(int $productId): never
    {
        $current = $this->adminProduct($productId);
        if ($current === null) {
            setFlash('danger', 'Produk tidak ditemukan.');
            redirect($this->url('admin/products'));
        }

        try {
            $values = $this->validatedValues($current);
            $upload = $this->validatedImageUpload();
        } catch (DomainException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($this->url('admin/products/edit?id=' . $productId));
        }

        $newImage = $upload === null ? null : $this->storeImage($upload);
        $oldImage = $current['image'];

        try {
            $this->pdo->beginTransaction();
            $select = $this->pdo->prepare(
                'SELECT p.id, p.category_id, p.name, p.description, p.image, p.price,
                        p.stock, p.min_stock, p.min_order_quantity, p.max_order_quantity,
                        p.availability_status, p.is_active, p.has_order_history,
                        CASE WHEN p.has_order_history = 1 OR EXISTS (
                            SELECT 1 FROM order_items oi WHERE oi.product_id = p.id
                        ) THEN 1 ELSE 0 END AS is_historical
                 FROM products p
                 WHERE p.id = :id
                 FOR UPDATE'
            );
            $select->execute(['id' => $productId]);
            $lockedProduct = $select->fetch();
            if ($lockedProduct === false) {
                throw new DomainException('Produk tidak ditemukan.');
            }

            $historical = (int) $lockedProduct['is_historical'] === 1;
            if ($historical && isset($_POST['price']) && $values['price'] !== (string) $lockedProduct['price']) {
                throw new DomainException('Harga produk yang pernah dipesan tidak dapat diubah.');
            }
            if ($historical) {
                $values['price'] = $lockedProduct['price'];
            }
            $this->assertUsableCategory((int) $values['category_id'], (int) $lockedProduct['category_id']);

            $imagePath = $newImage ?? $lockedProduct['image'];
            $values['image'] = $imagePath;
            $values['stock'] = (int) $lockedProduct['stock'];
            if ($values['stock'] === 0 && $values['availability_status'] === 'available') {
                $values['availability_status'] = 'sold_out';
            }

            $update = $this->pdo->prepare(
                'UPDATE products
                 SET category_id = :category_id,
                     name = :name,
                     description = :description,
                     image = :image,
                     price = :price,
                     min_stock = :min_stock,
                     min_order_quantity = :min_order_quantity,
                     max_order_quantity = :max_order_quantity,
                     availability_status = :availability_status,
                     is_active = :is_active
                 WHERE id = :id'
            );
            $update->execute([
                'category_id' => $values['category_id'],
                'name' => $values['name'],
                'description' => $values['description'],
                'image' => $values['image'],
                'price' => $values['price'],
                'min_stock' => $values['min_stock'],
                'min_order_quantity' => $values['min_order_quantity'],
                'max_order_quantity' => $values['max_order_quantity'],
                'availability_status' => $values['availability_status'],
                'is_active' => $values['is_active'],
                'id' => $productId,
            ]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'product_updated',
                'product',
                $productId,
                $this->loggableValues($lockedProduct),
                $this->loggableValues($values)
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->removeStoredImage($newImage);

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/products/edit?id=' . $productId));
            }

            throw $exception;
        }

        if ($newImage !== null && $oldImage !== null && $newImage !== $oldImage) {
            $this->removeStoredImage($oldImage);
        }
        setFlash('success', 'Perubahan produk berhasil disimpan.');
        redirect($this->url('admin/products'));
    }

    public function delete(int $productId): never
    {
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT id, name, image, has_order_history
                 FROM products
                 WHERE id = :id
                 FOR UPDATE'
            );
            $select->execute(['id' => $productId]);
            $product = $select->fetch();
            if ($product === false) {
                throw new DomainException('Produk tidak ditemukan.');
            }

            $orders = $this->pdo->prepare(
                'SELECT COUNT(*) FROM order_items WHERE product_id = :product_id'
            );
            $orders->execute(['product_id' => $productId]);
            if ((int) $product['has_order_history'] === 1 || (int) $orders->fetchColumn() > 0) {
                throw new DomainException('Produk yang pernah dipesan tidak dapat dihapus. Nonaktifkan produk sebagai gantinya.');
            }

            $delete = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
            $delete->execute(['id' => $productId]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'product_deleted',
                'product',
                $productId,
                ['name' => $product['name'], 'image' => $product['image']],
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/products'));
            }

            throw $exception;
        }

        $this->removeStoredImage($product['image']);
        setFlash('success', 'Produk berhasil dihapus.');
        redirect($this->url('admin/products'));
    }

    public static function serveImage(string $fileName): never
    {
        if (!preg_match('/\A[a-f0-9]{32}\.(jpg|jpeg|png|webp)\z/', $fileName)) {
            http_response_code(404);
            exit;
        }

        $storageDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $filePath = $storageDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($filePath)) {
            http_response_code(404);
            exit;
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!is_string($mimeType) || !in_array($mimeType, array_values(self::ALLOWED_IMAGES), true)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . (string) filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        exit;
    }

    private function validatedValues(?array $current): array
    {
        $name = $_POST['name'] ?? '';
        $name = is_string($name) ? trim($name) : '';
        if ($name === '' || strlen($name) > 160) {
            throw new DomainException('Nama produk wajib diisi dan maksimal 160 karakter.');
        }

        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        if ($categoryId === false || $categoryId < 1) {
            throw new DomainException('Pilih kategori produk yang valid.');
        }

        $description = $_POST['description'] ?? '';
        $description = is_string($description) ? trim($description) : '';
        $price = $this->validatedDecimal('price', $current['price'] ?? null, 'Harga produk');
        $stock = $current === null
            ? $this->validatedInteger('stock', 0, 4294967295, 'Stok awal')
            : (int) $current['stock'];
        $minStock = $this->validatedInteger('min_stock', $current['min_stock'] ?? 0, 4294967295, 'Batas minimum stok');
        $minOrder = $this->validatedInteger('min_order_quantity', $current['min_order_quantity'] ?? 1, 4294967295, 'Minimum pembelian');
        $maxOrder = $this->validatedInteger('max_order_quantity', $current['max_order_quantity'] ?? 99, 4294967295, 'Maksimum pembelian');
        if ($minOrder < 1 || $maxOrder < $minOrder) {
            throw new DomainException('Batas pembelian harus memenuhi minimum ≥ 1 dan maksimum ≥ minimum.');
        }

        $status = $_POST['availability_status'] ?? 'available';
        if (!is_string($status) || !in_array($status, ['available', 'sold_out', 'unavailable'], true)) {
            throw new DomainException('Status ketersediaan produk tidak valid.');
        }
        if ($stock === 0 && $status === 'available') {
            $status = 'sold_out';
        }

        return [
            'category_id' => (int) $categoryId,
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'image' => null,
            'price' => $price,
            'stock' => $stock,
            'min_stock' => $minStock,
            'min_order_quantity' => $minOrder,
            'max_order_quantity' => $maxOrder,
            'availability_status' => $status,
            'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0,
        ];
    }

    private function validatedDecimal(string $key, mixed $existing, string $label): string
    {
        $value = $_POST[$key] ?? null;
        if ($value === null && $existing !== null) {
            return (string) $existing;
        }
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new DomainException($label . ' wajib diisi.');
        }
        $value = trim((string) $value);
        if (!preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/', $value)) {
            throw new DomainException($label . ' harus lebih besar dari 0 dan maksimal 2 angka desimal.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $normalized = $whole . '.' . str_pad($fraction, 2, '0');
        if ($normalized === '0.00') {
            throw new DomainException($label . ' harus lebih besar dari 0 dan maksimal 2 angka desimal.');
        }

        return $normalized;
    }

    private function validatedInteger(string $key, int $default, int $maximum, string $label): int
    {
        $value = $_POST[$key] ?? (string) $default;
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 0 || $integer > $maximum) {
            throw new DomainException($label . ' harus berupa bilangan bulat yang valid.');
        }

        return (int) $integer;
    }

    private function validatedImageUpload(): ?array
    {
        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            return null;
        }

        $file = $_FILES['image'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('Foto gagal diunggah. Periksa ukuran file dan coba lagi.');
        }

        $temporaryPath = $file['tmp_name'] ?? '';
        $originalName = $file['name'] ?? '';
        $size = (int) ($file['size'] ?? 0);
        $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
            throw new DomainException('File foto tidak valid.');
        }
        if ($size < 1 || $size > self::MAX_IMAGE_BYTES) {
            throw new DomainException('Ukuran foto maksimal 5 MB.');
        }
        if (!isset(self::ALLOWED_IMAGES[$extension])) {
            throw new DomainException('Format foto harus JPG, PNG, atau WebP.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        $imageSize = getimagesize($temporaryPath);
        if ($mimeType !== self::ALLOWED_IMAGES[$extension]
            || $imageSize === false
            || $imageSize[0] < 1
            || $imageSize[1] < 1) {
            throw new DomainException('Isi file tidak cocok dengan format foto yang dipilih.');
        }

        return [
            'temporary_path' => $temporaryPath,
            'file_name' => bin2hex(random_bytes(16)) . '.' . $extension,
        ];
    }

    private function storeImage(array $upload): string
    {
        if (!is_dir($this->storageDirectory)
            && !mkdir($this->storageDirectory, 0775, true)
            && !is_dir($this->storageDirectory)) {
            throw new RuntimeException('Folder penyimpanan foto produk tidak dapat dibuat.');
        }

        $destination = $this->storageDirectory . DIRECTORY_SEPARATOR . $upload['file_name'];
        if (!move_uploaded_file($upload['temporary_path'], $destination)) {
            throw new RuntimeException('Foto produk tidak dapat disimpan.');
        }

        return 'uploads/products/' . $upload['file_name'];
    }

    private function removeStoredImage(?string $relativePath): void
    {
        if ($relativePath === null || !preg_match('/\Auploads\/products\/[a-f0-9]{32}\.(jpg|jpeg|png|webp)\z/', $relativePath)) {
            return;
        }

        $path = $this->storageDirectory . DIRECTORY_SEPARATOR . basename($relativePath);
        if (is_file($path) && !unlink($path)) {
            error_log('CozyCafe could not remove product image: ' . $path);
        }
    }

    private function assertUsableCategory(int $categoryId, ?int $existingCategoryId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT is_active FROM categories WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $categoryId]);
        $isActive = $statement->fetchColumn();
        if ($isActive === false) {
            throw new DomainException('Kategori yang dipilih tidak ditemukan.');
        }
        if ((int) $isActive !== 1 && $categoryId !== $existingCategoryId) {
            throw new DomainException('Pilih kategori yang aktif.');
        }
    }

    private function loggableValues(array $values): array
    {
        unset($values['image']);

        return $values;
    }

    private function url(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
