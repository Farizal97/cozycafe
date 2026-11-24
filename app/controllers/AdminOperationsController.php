<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/LogService.php';

final class AdminOperationsController
{
    private const ORDER_STATUSES = [
        'pending', 'processing', 'ready', 'completed', 'cancelled',
        'expired', 'cancellation_requested', 'cancellation_rejected',
    ];
    private const BOOKING_STATUSES = [
        'pending', 'confirmed', 'rejected', 'completed', 'cancelled',
        'cancellation_requested', 'checked_in', 'no_show',
    ];
    private readonly LogService $logs;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath
    ) {
        $this->logs = new LogService($pdo);
    }

    public function orders(array $input): array
    {
        $filters = $this->orderFilters($input);
        $where = ['o.created_at >= :start_at', 'o.created_at < :end_at'];
        $params = [
            'start_at' => $filters['start_date'] . ' 00:00:00',
            'end_at' => (new DateTimeImmutable($filters['end_date'], new DateTimeZone('Asia/Jakarta')))
                ->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
        ];
        if ($filters['status'] !== '') {
            $where[] = 'o.order_status = :status';
            $params['status'] = $filters['status'];
        }

        $statement = $this->pdo->prepare(
            'SELECT o.id, o.order_code, o.order_type, o.order_status, o.payment_status,
                    o.total_amount, o.created_at, c.name AS customer_name, c.phone,
                    pm.name AS payment_method_name
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             INNER JOIN payment_methods pm ON pm.id = o.payment_method_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY o.created_at DESC, o.id DESC
             LIMIT 200'
        );
        $statement->execute($params);

        return ['filters' => $filters, 'orders' => $statement->fetchAll()];
    }

    public function orderDetail(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.*, c.name AS customer_name, c.phone,
                    pm.name AS payment_method_name, t.table_number
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             INNER JOIN payment_methods pm ON pm.id = o.payment_method_id
             LEFT JOIN tables t ON t.id = o.table_id
             WHERE o.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();
        if ($order === false) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT product_name_snapshot, price_snapshot, quantity, subtotal
             FROM order_items WHERE order_id = :id ORDER BY id'
        );
        $items->execute(['id' => $orderId]);
        $order['items'] = $items->fetchAll();

        $histories = $this->pdo->prepare(
            'SELECT old_status, new_status, changed_at, a.username
             FROM order_status_histories h
             LEFT JOIN admins a ON a.id = h.changed_by
             WHERE h.order_id = :id ORDER BY h.changed_at, h.id'
        );
        $histories->execute(['id' => $orderId]);
        $order['histories'] = $histories->fetchAll();

        $cancellation = $this->pdo->prepare(
            'SELECT id, reason, status, admin_response, created_at
             FROM order_cancellation_requests WHERE order_id = :id
             ORDER BY created_at DESC, id DESC'
        );
        $cancellation->execute(['id' => $orderId]);
        $order['cancellations'] = $cancellation->fetchAll();

        return $order;
    }

    public function updateOrderStatus(): never
    {
        $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = $_POST['order_status'] ?? '';
        $restoreStock = ($_POST['restore_stock'] ?? '') === '1';
        if ($orderId === false || $orderId < 1 || !is_string($newStatus)
            || !in_array($newStatus, self::ORDER_STATUSES, true)) {
            setFlash('danger', 'Perubahan status pesanan tidak valid.');
            $this->redirect('admin/orders');
        }

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $orderId]);
            $order = $select->fetch();
            if ($order === false) {
                throw new DomainException('Pesanan tidak ditemukan.');
            }
            $oldStatus = $order['order_status'];
            if ($oldStatus === $newStatus) {
                throw new DomainException('Status pesanan tidak berubah.');
            }
            $transitions = [
                'pending' => ['processing', 'cancelled', 'expired'],
                'processing' => ['ready', 'cancelled'],
                'ready' => ['completed', 'cancelled'],
            ];
            if (!in_array($newStatus, $transitions[$oldStatus] ?? [], true)) {
                throw new DomainException('Perubahan status tidak sesuai alur pesanan.');
            }

            $stockRestored = false;
            if ($oldStatus === 'pending' && $newStatus === 'processing') {
                $this->deductOrderStock((int) $orderId);
            } elseif ($newStatus === 'cancelled' && $restoreStock && $order['confirmed_at'] !== null) {
                $this->restoreOrderStock((int) $orderId);
                $stockRestored = true;
            }

            $update = $this->pdo->prepare(
                'UPDATE orders
                 SET order_status = :status,
                     confirmed_at = CASE WHEN :is_processing = 1 THEN COALESCE(confirmed_at, NOW()) ELSE confirmed_at END,
                     completed_at = CASE WHEN :is_completed = 1 THEN NOW() ELSE completed_at END
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $newStatus,
                'is_processing' => $newStatus === 'processing' ? 1 : 0,
                'is_completed' => $newStatus === 'completed' ? 1 : 0,
                'id' => $orderId,
            ]);

            $history = $this->pdo->prepare(
                'INSERT INTO order_status_histories (order_id, old_status, new_status, changed_by)
                 VALUES (:order_id, :old_status, :new_status, :admin_id)'
            );
            $history->execute([
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'admin_id' => (int) $_SESSION['admin_id'],
            ]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'order_status_updated',
                'order',
                (int) $orderId,
                ['order_status' => $oldStatus],
                ['order_status' => $newStatus, 'stock_restored' => $stockRestored]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/orders/detail?id=' . (int) $orderId);
            }
            throw $exception;
        }

        setFlash('success', 'Status pesanan berhasil diperbarui.');
        $this->redirect('admin/orders/detail?id=' . (int) $orderId);
    }

    public function decideOrderCancellation(): never
    {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        if ($requestId === false || $requestId < 1 || !in_array($decision, ['approve', 'reject'], true)) {
            setFlash('danger', 'Keputusan pembatalan pesanan tidak valid.');
            $this->redirect('admin/orders');
        }

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT r.id, r.order_id, r.status AS request_status, o.order_status,
                        o.confirmed_at
                 FROM order_cancellation_requests r
                 INNER JOIN orders o ON o.id = r.order_id
                 WHERE r.id = :id FOR UPDATE'
            );
            $select->execute(['id' => $requestId]);
            $request = $select->fetch();
            if ($request === false || $request['request_status'] !== 'pending'
                || $request['order_status'] !== 'cancellation_requested') {
                throw new DomainException('Permintaan pembatalan tidak ditemukan atau sudah diproses.');
            }

            $nextOrderStatus = 'cancelled';
            if ($decision === 'reject') {
                $previousStatus = $this->pdo->prepare(
                    "SELECT new_status FROM order_status_histories
                     WHERE order_id = :order_id AND new_status <> 'cancellation_requested'
                     ORDER BY changed_at DESC, id DESC LIMIT 1"
                );
                $previousStatus->execute(['order_id' => $request['order_id']]);
                $historyStatus = $previousStatus->fetchColumn();
                $nextOrderStatus = in_array($historyStatus, ['pending', 'processing', 'ready'], true)
                    ? $historyStatus
                    : ($request['confirmed_at'] === null ? 'pending' : 'processing');
            }

            $updateRequest = $this->pdo->prepare(
                'UPDATE order_cancellation_requests
                 SET status = :status, admin_response = :response, processed_at = NOW()
                 WHERE id = :id'
            );
            $updateRequest->execute([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'response' => $decision === 'approve' ? 'Disetujui admin.' : 'Ditolak admin.',
                'id' => $requestId,
            ]);
            $updateOrder = $this->pdo->prepare(
                'UPDATE orders SET order_status = :status WHERE id = :id'
            );
            $updateOrder->execute(['status' => $nextOrderStatus, 'id' => $request['order_id']]);
            $stockRestored = false;
            if ($decision === 'approve' && $request['confirmed_at'] !== null) {
                $this->restoreOrderStock((int) $request['order_id']);
                $stockRestored = true;
            }
            $history = $this->pdo->prepare(
                'INSERT INTO order_status_histories (order_id, old_status, new_status, changed_by)
                 VALUES (:order_id, :old_status, :new_status, :admin_id)'
            );
            $history->execute([
                'order_id' => $request['order_id'],
                'old_status' => 'cancellation_requested',
                'new_status' => $nextOrderStatus,
                'admin_id' => (int) $_SESSION['admin_id'],
            ]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $decision === 'approve' ? 'order_cancellation_approved' : 'order_cancellation_rejected',
                'order',
                (int) $request['order_id'],
                ['order_status' => 'cancellation_requested'],
                ['order_status' => $nextOrderStatus, 'stock_restored' => $stockRestored]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/orders');
            }
            throw $exception;
        }

        setFlash('success', $decision === 'approve' ? 'Pembatalan pesanan disetujui.' : 'Pembatalan pesanan ditolak.');
        $this->redirect('admin/orders/detail?id=' . (int) $request['order_id']);
    }

    public function payments(): array
    {
        return $this->pdo->query(
            "SELECT p.id AS payment_id, p.amount, p.status AS payment_status, p.created_at,
                    o.id AS order_id, o.order_code, o.order_status, o.payment_deadline,
                    o.payment_status AS order_payment_status,
                    c.name AS customer_name, pm.name AS payment_method_name,
                    pm.method_type,
                    (SELECT COALESCE(SUM(r.amount), 0) FROM refunds r WHERE r.order_id = o.id) AS refunded_amount,
                    (SELECT pp.id FROM payment_proofs pp
                     WHERE pp.payment_id = p.id ORDER BY pp.uploaded_at DESC, pp.id DESC LIMIT 1) AS proof_id,
                    (SELECT pp.verification_status FROM payment_proofs pp
                     WHERE pp.payment_id = p.id ORDER BY pp.uploaded_at DESC, pp.id DESC LIMIT 1) AS proof_status
             FROM payments p
             INNER JOIN orders o ON o.id = p.order_id
             INNER JOIN customers c ON c.id = o.customer_id
             INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
             ORDER BY CASE WHEN p.status = 'waiting_verification' THEN 0 ELSE 1 END,
                      p.created_at DESC, p.id DESC
             LIMIT 200"
        )->fetchAll();
    }

    public function verifyPayment(): never
    {
        $proofId = filter_var($_POST['proof_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        $reason = trim(is_string($_POST['rejection_reason'] ?? null) ? $_POST['rejection_reason'] : '');
        if ($proofId === false || $proofId < 1 || !in_array($decision, ['approve', 'reject'], true)
            || ($decision === 'reject' && ($reason === '' || strlen($reason) > 2000))) {
            setFlash('danger', 'Keputusan pembayaran tidak valid. Alasan penolakan wajib diisi.');
            $this->redirect('admin/payments');
        }

        $this->pdo->beginTransaction();
        try {
            $proofQuery = $this->pdo->prepare(
                'SELECT pp.id, pp.payment_id, pp.verification_status, p.order_id
                 FROM payment_proofs pp
                 INNER JOIN payments p ON p.id = pp.payment_id
                 WHERE pp.id = :id FOR UPDATE'
            );
            $proofQuery->execute(['id' => $proofId]);
            $proof = $proofQuery->fetch();
            if ($proof === false || $proof['verification_status'] !== 'pending') {
                throw new DomainException('Bukti pembayaran tidak ditemukan atau sudah diverifikasi.');
            }

            $paymentStatus = $decision === 'approve' ? 'paid' : 'rejected';
            $proofUpdate = $this->pdo->prepare(
                'UPDATE payment_proofs
                 SET verification_status = :status, rejection_reason = :reason, verified_at = NOW()
                 WHERE id = :id'
            );
            $proofUpdate->execute([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'reason' => $decision === 'reject' ? $reason : null,
                'id' => $proofId,
            ]);
            $paymentUpdate = $this->pdo->prepare(
                'UPDATE payments SET status = :status, verified_by = :admin_id, verified_at = NOW()
                 WHERE id = :id'
            );
            $paymentUpdate->execute([
                'status' => $paymentStatus,
                'admin_id' => (int) $_SESSION['admin_id'],
                'id' => $proof['payment_id'],
            ]);
            $orderUpdate = $this->pdo->prepare('UPDATE orders SET payment_status = :status WHERE id = :id');
            $orderUpdate->execute(['status' => $paymentStatus, 'id' => $proof['order_id']]);
            $logAction = $decision === 'approve' ? 'payment_proof_approved' : 'payment_proof_rejected';
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $logAction,
                'payment',
                (int) $proof['payment_id'],
                ['verification_status' => 'pending'],
                ['verification_status' => $paymentStatus, 'reason' => $decision === 'reject' ? $reason : null]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/payments');
            }
            throw $exception;
        }

        setFlash('success', $decision === 'approve' ? 'Pembayaran berhasil diverifikasi.' : 'Bukti pembayaran ditolak.');
        $this->redirect('admin/payments');
    }

    public function confirmCashPayment(): never
    {
        $paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
        if ($paymentId === false || $paymentId < 1) {
            setFlash('danger', 'Data pembayaran tunai tidak valid.');
            $this->redirect('admin/payments');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT p.id, p.order_id, p.status, pm.method_type
                 FROM payments p INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
                 WHERE p.id = :id FOR UPDATE'
            );
            $select->execute(['id' => $paymentId]);
            $payment = $select->fetch();
            if ($payment === false || $payment['method_type'] !== 'cash' || $payment['status'] !== 'unpaid') {
                throw new DomainException('Pembayaran ini bukan pembayaran tunai yang belum dikonfirmasi.');
            }
            $this->setPaymentPaid((int) $payment['id'], (int) $payment['order_id']);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'cash_payment_confirmed',
                'payment',
                (int) $paymentId,
                ['status' => 'unpaid'],
                ['status' => 'paid']
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/payments');
            }
            throw $exception;
        }
        setFlash('success', 'Pembayaran tunai berhasil dikonfirmasi.');
        $this->redirect('admin/payments');
    }

    public function createRefund(): never
    {
        $paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
        $amountInput = $_POST['amount'] ?? null;
        $note = trim(is_string($_POST['admin_note'] ?? null) ? $_POST['admin_note'] : '');
        if ($paymentId === false || $paymentId < 1 || !is_string($amountInput)
            || preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/', $amountInput) !== 1
            || $note === '' || mb_strlen($note) > 2000) {
            setFlash('danger', 'Jumlah dan alasan pengembalian dana wajib valid.');
            $this->redirect('admin/payments');
        }
        $refundCents = (int) round((float) $amountInput * 100);
        if ($refundCents < 1) {
            setFlash('danger', 'Jumlah pengembalian dana harus lebih dari Rp0.');
            $this->redirect('admin/payments');
        }

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT p.id, p.order_id, p.amount, p.status
                 FROM payments p WHERE p.id = :id FOR UPDATE'
            );
            $select->execute(['id' => $paymentId]);
            $payment = $select->fetch();
            if ($payment === false || !in_array($payment['status'], ['paid', 'refunded'], true)) {
                throw new DomainException('Hanya pembayaran yang sudah lunas dapat dikembalikan.');
            }
            $refundQuery = $this->pdo->prepare(
                'SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE order_id = :order_id'
            );
            $refundQuery->execute(['order_id' => $payment['order_id']]);
            $paidCents = (int) round((float) $payment['amount'] * 100);
            $refundedCents = (int) round((float) $refundQuery->fetchColumn() * 100);
            $remainingCents = $paidCents - $refundedCents;
            if ($refundCents > $remainingCents) {
                throw new DomainException('Jumlah melebihi sisa pembayaran yang dapat dikembalikan.');
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO refunds (order_id, amount, refund_date, admin_note)
                 VALUES (:order_id, :amount, NOW(), :admin_note)'
            );
            $insert->execute([
                'order_id' => $payment['order_id'],
                'amount' => number_format($refundCents / 100, 2, '.', ''),
                'admin_note' => $note,
            ]);
            $newRefundedCents = $refundedCents + $refundCents;
            if ($newRefundedCents >= $paidCents) {
                $paymentUpdate = $this->pdo->prepare(
                    'UPDATE payments SET status = \'refunded\' WHERE id = :id'
                );
                $paymentUpdate->execute(['id' => $paymentId]);
                $orderUpdate = $this->pdo->prepare(
                    'UPDATE orders SET payment_status = \'refunded\' WHERE id = :id'
                );
                $orderUpdate->execute(['id' => $payment['order_id']]);
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'payment_refunded',
                'payment',
                (int) $paymentId,
                ['refunded_amount' => number_format($refundedCents / 100, 2, '.', '')],
                [
                    'refund_amount' => number_format($refundCents / 100, 2, '.', ''),
                    'remaining_amount' => number_format(($paidCents - $newRefundedCents) / 100, 2, '.', ''),
                    'note' => $note,
                ]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/payments');
            }
            throw $exception;
        }
        setFlash('success', 'Pengembalian dana berhasil dicatat.');
        $this->redirect('admin/payments');
    }

    public function paymentProof(int $proofId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT file_path, mime_type, original_filename
             FROM payment_proofs WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $proofId]);
        $proof = $statement->fetch();

        return $proof === false ? null : $proof;
    }

    public function bookings(array $input): array
    {
        $date = $input['date'] ?? '';
        if (!is_string($date) || ($date !== '' && !$this->isValidDate($date))) {
            throw new DomainException('Tanggal reservasi tidak valid.');
        }
        $status = $input['status'] ?? '';
        if (!is_string($status) || ($status !== '' && !in_array($status, self::BOOKING_STATUSES, true))) {
            throw new DomainException('Status reservasi tidak valid.');
        }

        $where = [];
        $params = [];
        if ($date !== '') {
            $where[] = 'b.booking_date = :booking_date';
            $params['booking_date'] = $date;
        }
        if ($status !== '') {
            $where[] = 'b.status = :status';
            $params['status'] = $status;
        }
        $sql = "SELECT b.id, b.booking_code, b.booking_date, b.slot_start, b.slot_end,
                       b.guest_count, b.status, b.checked_in_at, c.name AS customer_name,
                       c.phone,
                       GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') AS area_names,
                       GROUP_CONCAT(DISTINCT t.table_number ORDER BY t.table_number SEPARATOR ', ') AS table_numbers,
                       (SELECT bc.id FROM booking_cancellation_requests bc
                        WHERE bc.booking_id = b.id AND bc.status = 'pending'
                        ORDER BY bc.created_at DESC, bc.id DESC LIMIT 1) AS cancellation_id
                FROM bookings b
                INNER JOIN customers c ON c.id = b.customer_id
                LEFT JOIN booking_tables bt ON bt.booking_id = b.id
                LEFT JOIN tables t ON t.id = bt.table_id
                LEFT JOIN booking_areas a ON a.id = t.area_id";
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY b.id ORDER BY b.booking_date DESC, b.slot_start DESC, b.id DESC LIMIT 200';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return ['date' => $date, 'status' => $status, 'bookings' => $statement->fetchAll()];
    }

    public function updateBookingStatus(): never
    {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = $_POST['status'] ?? '';
        if ($bookingId === false || $bookingId < 1 || !is_string($newStatus)
            || !in_array($newStatus, self::BOOKING_STATUSES, true)) {
            setFlash('danger', 'Perubahan status reservasi tidak valid.');
            $this->redirect('admin/bookings');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM bookings WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $bookingId]);
            $booking = $select->fetch();
            if ($booking === false) {
                throw new DomainException('Reservasi tidak ditemukan.');
            }
            $oldStatus = $booking['status'];
            $transitions = [
                'pending' => ['confirmed', 'rejected', 'cancelled'],
                'confirmed' => ['checked_in', 'completed', 'cancelled', 'no_show'],
                'checked_in' => ['completed'],
            ];
            if (!in_array($newStatus, $transitions[$oldStatus] ?? [], true)) {
                throw new DomainException('Perubahan status tidak sesuai alur reservasi.');
            }
            if ($newStatus === 'no_show') {
                $slotStart = new DateTimeImmutable(
                    $booking['booking_date'] . ' ' . $booking['slot_start'],
                    new DateTimeZone('Asia/Jakarta')
                );
                if (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')) < $slotStart->modify('+30 minutes')) {
                    throw new DomainException('Reservasi baru dapat ditandai tidak hadir 30 menit setelah waktu mulai.');
                }
            }

            $update = $this->pdo->prepare(
                'UPDATE bookings
                 SET status = :status,
                     checked_in_at = CASE WHEN :set_checkin_at = 1 THEN NOW() ELSE checked_in_at END,
                     checkin_method = CASE WHEN :set_checkin_method = 1 THEN \'admin\' ELSE checkin_method END
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $newStatus,
                'set_checkin_at' => $newStatus === 'checked_in' ? 1 : 0,
                'set_checkin_method' => $newStatus === 'checked_in' ? 1 : 0,
                'id' => $bookingId,
            ]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'booking_status_updated',
                'booking',
                (int) $bookingId,
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/bookings');
            }
            throw $exception;
        }
        setFlash('success', 'Status reservasi berhasil diperbarui.');
        $this->redirect('admin/bookings');
    }

    public function decideBookingCancellation(): never
    {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        if ($requestId === false || $requestId < 1 || !in_array($decision, ['approve', 'reject'], true)) {
            setFlash('danger', 'Keputusan pembatalan reservasi tidak valid.');
            $this->redirect('admin/bookings');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT r.id, r.booking_id, r.status AS request_status, b.status AS booking_status
                 FROM booking_cancellation_requests r
                 INNER JOIN bookings b ON b.id = r.booking_id
                 WHERE r.id = :id FOR UPDATE'
            );
            $select->execute(['id' => $requestId]);
            $request = $select->fetch();
            if ($request === false || $request['request_status'] !== 'pending') {
                throw new DomainException('Permintaan pembatalan tidak ditemukan atau sudah diproses.');
            }
            $newBookingStatus = $decision === 'approve'
                ? 'cancelled'
                : ($request['booking_status'] === 'cancellation_requested' ? 'confirmed' : $request['booking_status']);
            $updateRequest = $this->pdo->prepare(
                'UPDATE booking_cancellation_requests
                 SET status = :status, admin_response = :response, processed_at = NOW()
                 WHERE id = :id'
            );
            $updateRequest->execute([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'response' => $decision === 'approve' ? 'Disetujui admin.' : 'Ditolak admin.',
                'id' => $requestId,
            ]);
            $updateBooking = $this->pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
            $updateBooking->execute(['status' => $newBookingStatus, 'id' => $request['booking_id']]);
            $logAction = $decision === 'approve'
                ? 'booking_cancellation_approved'
                : 'booking_cancellation_rejected';
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $logAction,
                'booking',
                (int) $request['booking_id'],
                ['status' => $request['booking_status']],
                ['status' => $newBookingStatus]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/bookings');
            }
            throw $exception;
        }
        setFlash('success', $decision === 'approve' ? 'Pembatalan reservasi disetujui.' : 'Pembatalan reservasi ditolak.');
        $this->redirect('admin/bookings');
    }

    public function areas(): array
    {
        return $this->pdo->query(
            'SELECT a.id, a.name, a.description, a.is_active, COUNT(t.id) AS table_count
             FROM booking_areas a LEFT JOIN tables t ON t.area_id = a.id
             GROUP BY a.id ORDER BY a.name'
        )->fetchAll();
    }

    public function area(int $areaId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM booking_areas WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $areaId]);
        $area = $statement->fetch();

        return $area === false ? null : $area;
    }

    public function saveArea(?int $areaId): never
    {
        $isNew = $areaId === null;
        $formPath = $isNew ? 'admin/areas/create' : 'admin/areas/edit?id=' . $areaId;
        $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
        $description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');
        $isActive = ($_POST['is_active'] ?? '') === '1' ? 1 : 0;
        if ($name === '' || mb_strlen($name) > 100) {
            setFlash('danger', 'Nama area wajib diisi (maksimal 100 karakter).');
            $this->redirect($formPath);
        }

        $this->pdo->beginTransaction();
        try {
            $before = null;
            if ($areaId === null) {
                $statement = $this->pdo->prepare(
                    'INSERT INTO booking_areas (name, description, is_active)
                     VALUES (:name, :description, :is_active)'
                );
                $statement->execute([
                    'name' => $name,
                    'description' => $description === '' ? null : $description,
                    'is_active' => $isActive,
                ]);
                $areaId = (int) $this->pdo->lastInsertId();
                $action = 'booking_area_created';
            } else {
                $select = $this->pdo->prepare('SELECT * FROM booking_areas WHERE id = :id FOR UPDATE');
                $select->execute(['id' => $areaId]);
                $before = $select->fetch();
                if ($before === false) {
                    throw new DomainException('Area reservasi tidak ditemukan.');
                }
                $statement = $this->pdo->prepare(
                    'UPDATE booking_areas SET name = :name, description = :description, is_active = :is_active
                     WHERE id = :id'
                );
                $statement->execute([
                    'name' => $name,
                    'description' => $description === '' ? null : $description,
                    'is_active' => $isActive,
                    'id' => $areaId,
                ]);
                $action = 'booking_area_updated';
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $action,
                'booking_area',
                $areaId,
                $before === null ? null : ['name' => $before['name'], 'description' => $before['description'], 'is_active' => (int) $before['is_active']],
                ['name' => $name, 'description' => $description === '' ? null : $description, 'is_active' => $isActive]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/areas');
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                setFlash('danger', 'Nama area tersebut sudah digunakan.');
                $this->redirect($formPath);
            }
            throw $exception;
        }
        setFlash('success', 'Area reservasi berhasil disimpan.');
        $this->redirect('admin/areas');
    }

    public function deleteArea(int $areaId): never
    {
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM booking_areas WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $areaId]);
            $area = $select->fetch();
            if ($area === false) {
                throw new DomainException('Area reservasi tidak ditemukan.');
            }
            $delete = $this->pdo->prepare('DELETE FROM booking_areas WHERE id = :id');
            $delete->execute(['id' => $areaId]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'booking_area_deleted',
                'booking_area',
                $areaId,
                ['name' => $area['name']],
                null
            );
            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                setFlash('danger', 'Area yang masih memiliki meja tidak dapat dihapus. Pindahkan meja terlebih dahulu.');
                $this->redirect('admin/areas');
            }
            throw $exception;
        } catch (DomainException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            setFlash('danger', $exception->getMessage());
            $this->redirect('admin/areas');
        }
        setFlash('success', 'Area reservasi berhasil dihapus.');
        $this->redirect('admin/areas');
    }

    public function tables(): array
    {
        return $this->pdo->query(
            'SELECT t.id, t.table_number, t.capacity, t.position_x, t.position_y,
                    t.is_active, t.area_id, a.name AS area_name,
                    (SELECT COUNT(*) FROM booking_tables bt
                     INNER JOIN bookings b ON b.id = bt.booking_id
                     WHERE bt.table_id = t.id
                       AND b.status IN (\'pending\', \'confirmed\', \'checked_in\', \'cancellation_requested\')) AS active_bookings
             FROM tables t INNER JOIN booking_areas a ON a.id = t.area_id
             ORDER BY t.table_number'
        )->fetchAll();
    }

    public function table(int $tableId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM tables WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $tableId]);
        $table = $statement->fetch();

        return $table === false ? null : $table;
    }

    public function saveTable(?int $tableId): never
    {
        $isNew = $tableId === null;
        $formPath = $isNew ? 'admin/tables/create' : 'admin/tables/edit?id=' . $tableId;
        $tableNumber = trim(is_string($_POST['table_number'] ?? null) ? $_POST['table_number'] : '');
        $areaId = filter_var($_POST['area_id'] ?? null, FILTER_VALIDATE_INT);
        $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
        $x = filter_var($_POST['position_x'] ?? null, FILTER_VALIDATE_FLOAT);
        $y = filter_var($_POST['position_y'] ?? null, FILTER_VALIDATE_FLOAT);
        $isActive = ($_POST['is_active'] ?? '') === '1' ? 1 : 0;
        if ($tableNumber === '' || mb_strlen($tableNumber) > 40 || $areaId === false || $areaId < 1
            || $capacity === false || $capacity < 1 || $capacity > 255
            || $x === false || $x < 0 || $x > 100 || $y === false || $y < 0 || $y > 100) {
            setFlash('danger', 'Nomor meja, area, kapasitas, atau posisi denah tidak valid.');
            $this->redirect($formPath);
        }

        $this->pdo->beginTransaction();
        try {
            $areaQuery = $this->pdo->prepare('SELECT is_active FROM booking_areas WHERE id = :id FOR UPDATE');
            $areaQuery->execute(['id' => $areaId]);
            $area = $areaQuery->fetch();
            if ($area === false || ($isActive === 1 && (int) $area['is_active'] !== 1)) {
                throw new DomainException('Pilih area reservasi yang aktif untuk meja aktif.');
            }

            if ($isNew) {
                $statement = $this->pdo->prepare(
                    'INSERT INTO tables (table_number, area_id, capacity, position_x, position_y, is_active)
                     VALUES (:table_number, :area_id, :capacity, :position_x, :position_y, :is_active)'
                );
                $statement->execute([
                    'table_number' => $tableNumber,
                    'area_id' => $areaId,
                    'capacity' => $capacity,
                    'position_x' => $x,
                    'position_y' => $y,
                    'is_active' => $isActive,
                ]);
                $tableId = (int) $this->pdo->lastInsertId();
                $action = 'table_created';
                $before = null;
            } else {
                $tableQuery = $this->pdo->prepare('SELECT * FROM tables WHERE id = :id FOR UPDATE');
                $tableQuery->execute(['id' => $tableId]);
                $before = $tableQuery->fetch();
                if ($before === false) {
                    throw new DomainException('Meja tidak ditemukan.');
                }
                $activeUse = $this->pdo->prepare(
                    "SELECT MAX(b.guest_count)
                         FROM booking_tables bt
                         INNER JOIN bookings b ON b.id = bt.booking_id
                         WHERE bt.table_id = :table_id
                           AND b.status IN ('pending', 'confirmed', 'checked_in', 'cancellation_requested')
                        "
                );
                $activeUse->execute(['table_id' => $tableId]);
                $largestActiveParty = $activeUse->fetchColumn();
                if ($largestActiveParty !== false && $largestActiveParty !== null
                    && ($isActive === 0 || (int) $capacity < (int) $largestActiveParty
                        || (int) $areaId !== (int) $before['area_id'])) {
                    throw new DomainException('Meja dengan reservasi aktif tidak dapat dinonaktifkan, dipindah area, atau dikurangi kapasitasnya.');
                }
                if ($isActive === 0) {
                    $activeSession = $this->pdo->prepare(
                        "SELECT 1 FROM table_sessions
                         WHERE table_id = :table_id AND status = 'active' AND expected_end_at > NOW()
                         LIMIT 1"
                    );
                    $activeSession->execute(['table_id' => $tableId]);
                    if ($activeSession->fetchColumn() !== false) {
                        throw new DomainException('Meja dengan reservasi atau sesi aktif tidak dapat dinonaktifkan.');
                    }
                }
                $statement = $this->pdo->prepare(
                    'UPDATE tables SET table_number = :table_number, area_id = :area_id,
                        capacity = :capacity, position_x = :position_x, position_y = :position_y,
                        is_active = :is_active WHERE id = :id'
                );
                $statement->execute([
                    'table_number' => $tableNumber,
                    'area_id' => $areaId,
                    'capacity' => $capacity,
                    'position_x' => $x,
                    'position_y' => $y,
                    'is_active' => $isActive,
                    'id' => $tableId,
                ]);
                $action = 'table_updated';
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $action,
                'table',
                $tableId,
                $before === null ? null : ['table_number' => $before['table_number'], 'area_id' => (int) $before['area_id'], 'capacity' => (int) $before['capacity'], 'is_active' => (int) $before['is_active']],
                ['table_number' => $tableNumber, 'area_id' => (int) $areaId, 'capacity' => (int) $capacity, 'position_x' => $x, 'position_y' => $y, 'is_active' => $isActive]
            );
            $this->pdo->commit();
        } catch (DomainException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            setFlash('danger', $exception->getMessage());
            $this->redirect('admin/tables');
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                setFlash('danger', 'Nomor meja tersebut sudah digunakan.');
                $this->redirect($formPath);
            }
            throw $exception;
        }
        setFlash('success', 'Data meja berhasil disimpan.');
        $this->redirect('admin/tables');
    }

    public function areasForSelect(): array
    {
        return $this->pdo->query('SELECT id, name, is_active FROM booking_areas ORDER BY name')->fetchAll();
    }

    public function customers(string $search): array
    {
        $search = trim($search);
        if (mb_strlen($search) > 80) {
            $search = mb_substr($search, 0, 80);
        }
        $sql = 'SELECT c.id, c.name, c.phone, c.is_anonymized, c.created_at,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
                       (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count,
                       (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o WHERE o.customer_id = c.id) AS total_spend
                FROM customers c';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE c.name LIKE :name_search OR c.phone LIKE :phone_search';
            $escapedSearch = '%' . addcslashes($search, '%_\\') . '%';
            $params['name_search'] = $escapedSearch;
            $params['phone_search'] = $escapedSearch;
        }
        $sql .= ' ORDER BY c.created_at DESC, c.id DESC LIMIT 200';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function customer(int $customerId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, phone, is_anonymized, created_at
             FROM customers WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $customerId]);
        $customer = $statement->fetch();
        if ($customer === false) {
            return null;
        }

        $orders = $this->pdo->prepare(
            'SELECT order_code, order_type, order_status, payment_status, total_amount, created_at
             FROM orders WHERE customer_id = :id ORDER BY created_at DESC LIMIT 30'
        );
        $orders->execute(['id' => $customerId]);
        $customer['orders'] = $orders->fetchAll();

        $bookings = $this->pdo->prepare(
            'SELECT booking_code, booking_date, slot_start, slot_end, guest_count, status
             FROM bookings WHERE customer_id = :id ORDER BY booking_date DESC, slot_start DESC LIMIT 30'
        );
        $bookings->execute(['id' => $customerId]);
        $customer['bookings'] = $bookings->fetchAll();

        $reviews = $this->pdo->prepare(
            'SELECT review_type, rating, comment, moderation_status, created_at
             FROM reviews WHERE customer_id = :id ORDER BY created_at DESC LIMIT 30'
        );
        $reviews->execute(['id' => $customerId]);
        $customer['reviews'] = $reviews->fetchAll();

        return $customer;
    }

    public function anonymizeCustomer(int $customerId): never
    {
        if (($_POST['confirm_anonymize'] ?? '') !== '1') {
            setFlash('warning', 'Konfirmasi anonimisasi harus dicentang.');
            $this->redirect('admin/customers/detail?id=' . $customerId);
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM customers WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $customerId]);
            $customer = $select->fetch();
            if ($customer === false) {
                throw new DomainException('Pelanggan tidak ditemukan.');
            }
            if ((int) $customer['is_anonymized'] === 1) {
                throw new DomainException('Data pelanggan ini sudah dianonimkan.');
            }
            $activeOrder = $this->pdo->prepare(
                "SELECT 1 FROM orders WHERE customer_id = :id
                 AND order_status NOT IN ('completed', 'cancelled', 'expired') LIMIT 1"
            );
            $activeOrder->execute(['id' => $customerId]);
            $activeBooking = $this->pdo->prepare(
                "SELECT 1 FROM bookings WHERE customer_id = :id
                 AND status IN ('pending', 'confirmed', 'cancellation_requested', 'checked_in') LIMIT 1"
            );
            $activeBooking->execute(['id' => $customerId]);
            if ($activeOrder->fetchColumn() !== false || $activeBooking->fetchColumn() !== false) {
                throw new DomainException('Pelanggan dengan pesanan atau reservasi aktif belum dapat dianonimkan.');
            }

            $newName = 'Pelanggan Anonim #' . $customerId;
            $update = $this->pdo->prepare(
                'UPDATE customers SET name = :name, phone = NULL, is_anonymized = 1 WHERE id = :id'
            );
            $update->execute(['name' => $newName, 'id' => $customerId]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'customer_anonymized',
                'customer',
                $customerId,
                ['is_anonymized' => false],
                ['name' => $newName, 'phone' => null, 'is_anonymized' => true]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/customers/detail?id=' . $customerId);
            }
            throw $exception;
        }
        setFlash('success', 'Data pribadi pelanggan berhasil dianonimkan. Riwayat transaksi tetap tersimpan.');
        $this->redirect('admin/customers/detail?id=' . $customerId);
    }

    private function orderFilters(array $input): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta'));
        $defaultStart = $today->modify('-30 days')->format('Y-m-d');
        $startDate = $input['start_date'] ?? $defaultStart;
        $endDate = $input['end_date'] ?? $today->format('Y-m-d');
        if (!is_string($startDate) || !$this->isValidDate($startDate)
            || !is_string($endDate) || !$this->isValidDate($endDate)
            || $startDate > $endDate) {
            throw new DomainException('Rentang tanggal pesanan tidak valid.');
        }
        $status = $input['status'] ?? '';
        if (!is_string($status) || ($status !== '' && !in_array($status, self::ORDER_STATUSES, true))) {
            throw new DomainException('Status pesanan tidak valid.');
        }

        return ['start_date' => $startDate, 'end_date' => $endDate, 'status' => $status];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Asia/Jakarta'));
        $errors = DateTimeImmutable::getLastErrors();
        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date;
    }

    private function deductOrderStock(int $orderId): void
    {
        $items = $this->pdo->prepare(
            'SELECT product_id, product_name_snapshot, quantity
             FROM order_items WHERE order_id = :id ORDER BY id'
        );
        $items->execute(['id' => $orderId]);
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id'] === null) {
                continue;
            }
            $productQuery = $this->pdo->prepare(
                'SELECT id, name, stock, availability_status FROM products WHERE id = :id FOR UPDATE'
            );
            $productQuery->execute(['id' => $item['product_id']]);
            $product = $productQuery->fetch();
            if ($product === false || $product['availability_status'] === 'unavailable'
                || (int) $product['stock'] < (int) $item['quantity']) {
                throw new DomainException('Stok ' . $item['product_name_snapshot'] . ' tidak mencukupi untuk memproses pesanan.');
            }
            $newStock = (int) $product['stock'] - (int) $item['quantity'];
            $newStatus = $newStock === 0 ? 'sold_out' : $product['availability_status'];
            $update = $this->pdo->prepare(
                'UPDATE products SET stock = :stock, availability_status = :status WHERE id = :id'
            );
            $update->execute(['stock' => $newStock, 'status' => $newStatus, 'id' => $product['id']]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'order_stock_deducted',
                'product',
                (int) $product['id'],
                ['stock' => (int) $product['stock'], 'availability_status' => $product['availability_status']],
                ['stock' => $newStock, 'availability_status' => $newStatus]
            );
        }
    }

    private function restoreOrderStock(int $orderId): void
    {
        $items = $this->pdo->prepare(
            'SELECT product_id, product_name_snapshot, quantity
             FROM order_items WHERE order_id = :id ORDER BY id'
        );
        $items->execute(['id' => $orderId]);
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id'] === null) {
                continue;
            }
            $productQuery = $this->pdo->prepare(
                'SELECT id, name, stock, availability_status FROM products WHERE id = :id FOR UPDATE'
            );
            $productQuery->execute(['id' => $item['product_id']]);
            $product = $productQuery->fetch();
            if ($product === false) {
                continue;
            }
            $newStock = (int) $product['stock'] + (int) $item['quantity'];
            $newStatus = $product['availability_status'] === 'unavailable'
                ? 'unavailable'
                : ($newStock > 0 ? 'available' : 'sold_out');
            $update = $this->pdo->prepare(
                'UPDATE products SET stock = :stock, availability_status = :status WHERE id = :id'
            );
            $update->execute(['stock' => $newStock, 'status' => $newStatus, 'id' => $product['id']]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'cancelled_order_stock_restored',
                'product',
                (int) $product['id'],
                ['stock' => (int) $product['stock'], 'availability_status' => $product['availability_status']],
                ['stock' => $newStock, 'availability_status' => $newStatus]
            );
        }
    }

    private function setPaymentPaid(int $paymentId, int $orderId): void
    {
        $payment = $this->pdo->prepare(
            'UPDATE payments SET status = \'paid\', verified_by = :admin_id, verified_at = NOW()
             WHERE id = :id'
        );
        $payment->execute(['admin_id' => (int) $_SESSION['admin_id'], 'id' => $paymentId]);
        $order = $this->pdo->prepare('UPDATE orders SET payment_status = \'paid\' WHERE id = :id');
        $order->execute(['id' => $orderId]);
    }

    private function redirect(string $path): never
    {
        redirect($this->basePath . '/' . ltrim($path, '/'));
    }
}
