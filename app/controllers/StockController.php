<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/StockService.php';

final class StockController
{
    private readonly StockService $stocks;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath
    ) {
        $this->stocks = new StockService($pdo);
    }

    public function products(): array
    {
        return $this->pdo->query(
            'SELECT p.id, p.name, p.stock, p.min_stock, p.availability_status,
                    p.is_active, c.name AS category_name
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             ORDER BY p.name ASC'
        )->fetchAll();
    }

    public function addStock(): never
    {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
            setFlash('danger', 'Pilih produk dan masukkan jumlah stok tambahan yang valid.');
            redirect($this->url('admin/stock'));
        }

        try {
            $result = $this->stocks->addStock(
                (int) $productId,
                (int) $quantity,
                (int) $_SESSION['admin_id']
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($this->url('admin/stock'));
        }

        setFlash(
            'success',
            'Stok ' . $result['name'] . ' berhasil ditambahkan. Jumlah baru: ' . $result['stock'] . '.'
        );
        redirect($this->url('admin/stock'));
    }

    public function updateStatus(): never
    {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['availability_status'] ?? '';
        if ($productId === false || $productId < 1 || !is_string($status)) {
            setFlash('danger', 'Data perubahan status produk tidak valid.');
            redirect($this->url('admin/stock'));
        }

        try {
            $changed = $this->stocks->updateStatuses(
                [(int) $productId],
                $status,
                (int) $_SESSION['admin_id']
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($this->url('admin/stock'));
        }

        setFlash('success', $changed > 0 ? 'Status ketersediaan berhasil diperbarui.' : 'Status produk tidak berubah.');
        redirect($this->url('admin/stock'));
    }

    public function bulkUpdateStatus(): never
    {
        $productIds = $_POST['product_ids'] ?? [];
        $status = $_POST['availability_status'] ?? '';
        if (!is_array($productIds) || !is_string($status)) {
            setFlash('danger', 'Data pembaruan massal tidak valid.');
            redirect($this->url('admin/stock'));
        }

        try {
            $changed = $this->stocks->updateStatuses(
                $productIds,
                $status,
                (int) $_SESSION['admin_id']
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            setFlash('danger', $exception->getMessage());
            redirect($this->url('admin/stock'));
        }

        setFlash('success', $changed . ' status produk berhasil diperbarui.');
        redirect($this->url('admin/stock'));
    }

    private function url(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
