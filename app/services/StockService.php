<?php

declare(strict_types=1);

require_once __DIR__ . '/LogService.php';

final class StockService
{
    private const MAX_STOCK = 4294967295;
    private const ALLOWED_STATUSES = ['available', 'sold_out', 'unavailable'];
    private readonly LogService $logs;

    public function __construct(private readonly PDO $pdo)
    {
        $this->logs = new LogService($pdo);
    }

    public function addStock(int $productId, int $quantity, int $adminId): array
    {
        if ($productId < 1 || $quantity < 1) {
            throw new InvalidArgumentException('Produk dan jumlah penambahan stok harus valid.');
        }

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT id, name, stock, availability_status
                 FROM products
                 WHERE id = :id
                 FOR UPDATE'
            );
            $select->execute(['id' => $productId]);
            $product = $select->fetch();
            if ($product === false) {
                throw new DomainException('Produk tidak ditemukan.');
            }

            $oldStock = (int) $product['stock'];
            if ($quantity > self::MAX_STOCK - $oldStock) {
                throw new InvalidArgumentException('Jumlah stok melebihi batas yang didukung.');
            }

            $newStock = $oldStock + $quantity;
            $oldStatus = $product['availability_status'];
            $newStatus = $oldStatus === 'unavailable'
                ? 'unavailable'
                : ($newStock === 0 ? 'sold_out' : 'available');

            $update = $this->pdo->prepare(
                'UPDATE products
                 SET stock = :stock, availability_status = :availability_status
                 WHERE id = :id'
            );
            $update->execute([
                'stock' => $newStock,
                'availability_status' => $newStatus,
                'id' => $productId,
            ]);

            $this->logs->record(
                $adminId,
                'stock_added',
                'product',
                $productId,
                ['stock' => $oldStock, 'availability_status' => $oldStatus],
                [
                    'stock' => $newStock,
                    'availability_status' => $newStatus,
                    'added_quantity' => $quantity,
                ]
            );
            $this->pdo->commit();

            return [
                'name' => $product['name'],
                'stock' => $newStock,
                'availability_status' => $newStatus,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function updateStatuses(array $productIds, string $status, int $adminId): int
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Status ketersediaan tidak valid.');
        }

        $normalizedIds = [];
        foreach ($productIds as $productId) {
            if (!is_string($productId) && !is_int($productId)) {
                throw new InvalidArgumentException('Daftar produk tidak valid.');
            }
            $validatedId = filter_var($productId, FILTER_VALIDATE_INT);
            if ($validatedId === false || $validatedId < 1) {
                throw new InvalidArgumentException('Daftar produk tidak valid.');
            }
            $normalizedIds[] = (int) $validatedId;
        }
        $productIds = array_values(array_unique($normalizedIds));
        if ($productIds === [] || count($productIds) > 100) {
            throw new InvalidArgumentException('Pilih antara 1 sampai 100 produk.');
        }

        $this->pdo->beginTransaction();
        try {
            $placeholders = [];
            $parameters = [];
            foreach ($productIds as $index => $productId) {
                $placeholder = ':product_' . $index;
                $placeholders[] = $placeholder;
                $parameters['product_' . $index] = $productId;
            }

            $select = $this->pdo->prepare(
                'SELECT id, name, stock, availability_status
                 FROM products
                 WHERE id IN (' . implode(', ', $placeholders) . ')
                 ORDER BY id
                 FOR UPDATE'
            );
            $select->execute($parameters);
            $products = $select->fetchAll();
            if (count($products) !== count($productIds)) {
                throw new DomainException('Satu atau beberapa produk tidak ditemukan.');
            }

            $update = $this->pdo->prepare(
                'UPDATE products SET availability_status = :status WHERE id = :id'
            );
            $changed = 0;
            foreach ($products as $product) {
                $effectiveStatus = $status === 'available' && (int) $product['stock'] === 0
                    ? 'sold_out'
                    : $status;
                if ($product['availability_status'] === $effectiveStatus) {
                    continue;
                }

                $update->execute(['status' => $effectiveStatus, 'id' => $product['id']]);
                $this->logs->record(
                    $adminId,
                    'product_availability_updated',
                    'product',
                    (int) $product['id'],
                    ['availability_status' => $product['availability_status']],
                    ['availability_status' => $effectiveStatus]
                );
                $changed++;
            }

            $this->pdo->commit();
            return $changed;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
