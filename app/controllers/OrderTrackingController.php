<?php

declare(strict_types=1);

final class OrderTrackingController
{
    private const MAX_ATTEMPTS = 10;
    private const ATTEMPT_WINDOW_SECONDS = 900;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function verify(string $orderCode, string $phone): bool
    {
        unset($_SESSION['tracked_order_code']);
        $attemptKey = $this->attemptKey();
        $this->assertCanAttempt($attemptKey);
        $this->recordAttempt($attemptKey);

        $orderCode = strtoupper(trim($orderCode));
        $phone = preg_replace('/[\s().-]/', '', trim($phone)) ?? '';
        if (preg_match('/\AORD-[0-9]{8}-[A-F0-9]{18}\z/', $orderCode) !== 1
            || preg_match('/\A\+?[0-9]{8,15}\z/', $phone) !== 1) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'SELECT o.order_code
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             WHERE o.order_code = :order_code AND c.phone = :phone
             LIMIT 1'
        );
        $statement->execute(['order_code' => $orderCode, 'phone' => $phone]);
        $matchedCode = $statement->fetchColumn();
        if ($matchedCode === false) {
            return false;
        }

        unset($_SESSION[$attemptKey]);
        $_SESSION['tracked_order_code'] = $matchedCode;

        return true;
    }

    public function clearTracking(): void
    {
        unset($_SESSION['tracked_order_code']);
    }

    public function trackedOrder(): ?array
    {
        $orderCode = $_SESSION['tracked_order_code'] ?? '';
        if (!is_string($orderCode)
            || preg_match('/\AORD-[0-9]{8}-[A-F0-9]{18}\z/', $orderCode) !== 1) {
            unset($_SESSION['tracked_order_code']);
            return null;
        }

        $order = $this->loadOrder($orderCode);
        if ($order === null) {
            unset($_SESSION['tracked_order_code']);
        }

        return $order;
    }

    public function payload(array $order): array
    {
        $deadline = $order['payment_deadline'] === null
            ? null
            : (new DateTimeImmutable($order['payment_deadline'], new DateTimeZone('Asia/Jakarta')))->format(DATE_ATOM);

        return [
            'order_code' => $order['order_code'],
            'order_status' => $order['order_status'],
            'order_status_label' => self::orderStatusLabel($order['order_status']),
            'payment_status' => $order['payment_status'],
            'payment_status_label' => self::paymentStatusLabel($order['payment_status']),
            'payment_deadline' => $deadline,
            'payment_deadline_label' => formatDateTimeWib($order['payment_deadline']),
            'total_amount' => $order['total_amount'],
            'histories' => array_map(static fn (array $history): array => [
                'status_label' => self::orderStatusLabel($history['new_status']),
                'changed_at' => formatDateTimeWib($history['changed_at']),
            ], $order['histories']),
            'substitutions' => array_map(static fn (array $substitution): array => [
                'original_product_name' => $substitution['original_product_name'],
                'replacement_product_name' => $substitution['replacement_product_name'],
                'replacement_price' => $substitution['replacement_price'],
                'quantity' => (int) $substitution['quantity'],
                'status_label' => self::substitutionStatusLabel($substitution['status']),
                'expires_at' => formatDateTimeWib($substitution['expires_at']),
            ], $order['substitutions']),
        ];
    }

    public static function orderStatusLabel(string $status): string
    {
        return [
            'pending' => 'Menunggu konfirmasi',
            'processing' => 'Sedang diproses',
            'ready' => 'Siap',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            'cancellation_requested' => 'Pengajuan pembatalan',
            'cancellation_rejected' => 'Pembatalan ditolak',
        ][$status] ?? 'Status tidak diketahui';
    }

    public static function paymentStatusLabel(string $status): string
    {
        return [
            'unpaid' => 'Belum dibayar',
            'waiting_verification' => 'Menunggu verifikasi',
            'paid' => 'Lunas',
            'rejected' => 'Ditolak',
            'refunded' => 'Dikembalikan',
        ][$status] ?? 'Status tidak diketahui';
    }

    public static function substitutionStatusLabel(string $status): string
    {
        return [
            'pending' => 'Menunggu jawaban',
            'accepted' => 'Disetujui',
            'rejected' => 'Ditolak',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
        ][$status] ?? 'Status tidak diketahui';
    }

    private function loadOrder(string $orderCode): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.id, o.order_code, o.order_type, o.subtotal, o.discount_amount,
                    o.tax_amount, o.service_charge, o.total_amount, o.payment_status,
                    o.order_status, o.payment_deadline, o.created_at,
                    pm.name AS payment_method_name, pm.method_type
             FROM orders o
             INNER JOIN payment_methods pm ON pm.id = o.payment_method_id
             WHERE o.order_code = :order_code
             LIMIT 1'
        );
        $statement->execute(['order_code' => $orderCode]);
        $order = $statement->fetch();
        if ($order === false) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT product_name_snapshot AS name, price_snapshot, quantity, subtotal
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $items->execute(['order_id' => $order['id']]);
        $order['items'] = $items->fetchAll();

        $histories = $this->pdo->prepare(
            'SELECT new_status, changed_at
             FROM order_status_histories
             WHERE order_id = :order_id
             ORDER BY changed_at ASC, id ASC'
        );
        $histories->execute(['order_id' => $order['id']]);
        $order['histories'] = $histories->fetchAll();

        $substitutions = $this->pdo->prepare(
            'SELECT original_product_name, replacement_product_name, replacement_price,
                    quantity, status, expires_at
             FROM order_substitutions
             WHERE order_id = :order_id
             ORDER BY created_at ASC, id ASC'
        );
        $substitutions->execute(['order_id' => $order['id']]);
        $order['substitutions'] = $substitutions->fetchAll();

        return $order;
    }

    private function attemptKey(): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'order_tracking_attempts_' . hash('sha256', $ipAddress);
    }

    private function assertCanAttempt(string $key): void
    {
        $cutoff = time() - self::ATTEMPT_WINDOW_SECONDS;
        $attempts = $_SESSION[$key] ?? [];
        $attempts = is_array($attempts)
            ? array_values(array_filter($attempts, static fn (mixed $timestamp): bool =>
                is_int($timestamp) && $timestamp > $cutoff
            ))
            : [];
        $_SESSION[$key] = $attempts;

        if (count($attempts) >= self::MAX_ATTEMPTS) {
            throw new DomainException('Terlalu banyak percobaan. Silakan coba kembali dalam 15 menit.');
        }
    }

    private function recordAttempt(string $key): void
    {
        $_SESSION[$key][] = time();
    }
}
