<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/LogService.php';

final class CategoryController
{
    private readonly LogService $logs;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath
    ) {
        $this->logs = new LogService($pdo);
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT c.id, c.name, c.description, c.is_active, c.created_at,
                    COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id, c.name, c.description, c.is_active, c.created_at
             ORDER BY c.name ASC'
        );

        return $statement->fetchAll();
    }

    public function find(int $categoryId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, description, is_active
             FROM categories
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $categoryId]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function store(): never
    {
        [$name, $description, $isActive] = $this->validatedInput('admin/categories/create');
        $this->pdo->beginTransaction();
        try {
            $activeCategories = $this->lockActiveCategories();
            if ($isActive === 0 && $activeCategories === 0) {
                throw new DomainException('Setidaknya satu kategori harus tetap aktif.');
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO categories (name, description, is_active)
                 VALUES (:name, :description, :is_active)'
            );
            $statement->execute([
                'name' => $name,
                'description' => $description,
                'is_active' => $isActive,
            ]);
            $categoryId = (int) $this->pdo->lastInsertId();
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'category_created',
                'category',
                $categoryId,
                null,
                ['name' => $name, 'description' => $description, 'is_active' => $isActive]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/categories/create'));
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                setFlash('danger', 'Nama kategori tersebut sudah digunakan.');
                redirect($this->url('admin/categories/create'));
            }

            throw $exception;
        }

        setFlash('success', 'Kategori berhasil ditambahkan.');
        redirect($this->url('admin/categories'));
    }

    public function update(int $categoryId): never
    {
        [$name, $description, $isActive] = $this->validatedInput('admin/categories/edit?id=' . $categoryId);
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT id, name, description, is_active
                 FROM categories
                 WHERE id = :id
                 FOR UPDATE'
            );
            $select->execute(['id' => $categoryId]);
            $before = $select->fetch();
            if ($before === false) {
                throw new DomainException('Kategori tidak ditemukan.');
            }

            $activeCategories = $this->lockActiveCategories();
            if ((int) $before['is_active'] === 1 && $isActive === 0 && $activeCategories <= 1) {
                throw new DomainException('Minimal satu kategori harus tetap aktif.');
            }
            if ($isActive === 0 && $activeCategories === 0) {
                throw new DomainException('Setidaknya satu kategori harus tetap aktif.');
            }

            $statement = $this->pdo->prepare(
                'UPDATE categories
                 SET name = :name, description = :description, is_active = :is_active
                 WHERE id = :id'
            );
            $statement->execute([
                'name' => $name,
                'description' => $description,
                'is_active' => $isActive,
                'id' => $categoryId,
            ]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'category_updated',
                'category',
                $categoryId,
                [
                    'name' => $before['name'],
                    'description' => $before['description'],
                    'is_active' => (int) $before['is_active'],
                ],
                ['name' => $name, 'description' => $description, 'is_active' => $isActive]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/categories/edit?id=' . $categoryId));
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                setFlash('danger', 'Nama kategori tersebut sudah digunakan.');
                redirect($this->url('admin/categories/edit?id=' . $categoryId));
            }

            throw $exception;
        }

        setFlash('success', 'Perubahan kategori berhasil disimpan.');
        redirect($this->url('admin/categories'));
    }

    public function delete(int $categoryId): never
    {
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT id, name, is_active FROM categories WHERE id = :id FOR UPDATE'
            );
            $select->execute(['id' => $categoryId]);
            $category = $select->fetch();
            if ($category === false) {
                throw new DomainException('Kategori tidak ditemukan.');
            }

            $this->lockActiveCategories();
            $productCount = $this->pdo->prepare(
                'SELECT COUNT(*) FROM products WHERE category_id = :category_id'
            );
            $productCount->execute(['category_id' => $categoryId]);
            if ((int) $productCount->fetchColumn() > 0) {
                throw new DomainException('Kategori masih memiliki produk. Pindahkan produk terlebih dahulu.');
            }

            $activeCount = $this->activeCategoryCount();
            if ($activeCount === 0 || ((int) $category['is_active'] === 1 && $activeCount <= 1)) {
                throw new DomainException('Kategori aktif terakhir tidak dapat dihapus.');
            }

            $delete = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
            $delete->execute(['id' => $categoryId]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'category_deleted',
                'category',
                $categoryId,
                ['name' => $category['name'], 'is_active' => (int) $category['is_active']],
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                redirect($this->url('admin/categories'));
            }

            throw $exception;
        }

        setFlash('success', 'Kategori berhasil dihapus.');
        redirect($this->url('admin/categories'));
    }

    private function validatedInput(string $errorPath): array
    {
        $name = $_POST['name'] ?? '';
        $name = is_string($name) ? trim($name) : '';
        if ($name === '' || strlen($name) > 120) {
            setFlash('danger', 'Nama kategori wajib diisi dan maksimal 120 karakter.');
            redirect($this->url($errorPath));
        }

        $description = $_POST['description'] ?? '';
        $description = is_string($description) ? trim($description) : '';
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

        return [$name, $description === '' ? null : $description, $isActive];
    }

    private function lockActiveCategories(): int
    {
        $statement = $this->pdo->query(
            'SELECT id FROM categories WHERE is_active = 1 FOR UPDATE'
        );

        return count($statement->fetchAll());
    }

    private function activeCategoryCount(): int
    {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM categories WHERE is_active = 1'
        )->fetchColumn();
    }

    private function url(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
