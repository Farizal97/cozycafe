<?php

declare(strict_types=1);

final class AdminReportsController
{
    private const PAGE_SIZE = 50;
    private const REPORT_TYPES = ['transactions', 'orders', 'payments', 'bookings', 'revenue'];
    private const ORDER_TYPES = ['dine_in', 'takeaway'];
    private const PAYMENT_STATUSES = ['unpaid', 'waiting_verification', 'paid', 'rejected', 'refunded'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    private function safeCsvCell(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/\A[\s]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
    }

    public function paymentMethods(): array
    {
        return $this->pdo->query(
            'SELECT id, name FROM payment_methods ORDER BY name ASC'
        )->fetchAll();
    }

    public function filters(array $input): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta'));
        $defaultStart = $today->modify('first day of this month')->format('Y-m-d');
        $startDate = $this->validatedDate($input['start_date'] ?? $defaultStart, $defaultStart, 'tanggal awal');
        $endDate = $this->validatedDate($input['end_date'] ?? $today->format('Y-m-d'), $today->format('Y-m-d'), 'tanggal akhir');
        if ($startDate > $endDate) {
            throw new DomainException('Tanggal awal tidak boleh melewati tanggal akhir.');
        }

        $reportType = $input['report'] ?? 'transactions';
        if (!is_string($reportType) || !in_array($reportType, self::REPORT_TYPES, true)) {
            throw new DomainException('Jenis laporan tidak valid.');
        }

        $orderType = $input['order_type'] ?? '';
        if (!is_string($orderType) || ($orderType !== '' && !in_array($orderType, self::ORDER_TYPES, true))) {
            throw new DomainException('Jenis transaksi tidak valid.');
        }

        $paymentStatus = $input['payment_status'] ?? '';
        if (!is_string($paymentStatus)
            || ($paymentStatus !== '' && !in_array($paymentStatus, self::PAYMENT_STATUSES, true))) {
            throw new DomainException('Status pembayaran tidak valid.');
        }

        $paymentMethodId = $input['payment_method_id'] ?? '';
        if ($paymentMethodId === '' || $paymentMethodId === null) {
            $paymentMethodId = null;
        } else {
            $paymentMethodId = filter_var($paymentMethodId, FILTER_VALIDATE_INT);
            if ($paymentMethodId === false || $paymentMethodId < 1) {
                throw new DomainException('Metode pembayaran tidak valid.');
            }
        }

        return [
            'report' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_type' => $orderType,
            'payment_method_id' => $paymentMethodId === null ? null : (int) $paymentMethodId,
            'payment_status' => $paymentStatus,
        ];
    }

    public function report(array $filters, int $page): array
    {
        $page = max(1, $page);
        $totalRows = $this->countRows($filters);
        $totalPages = max(1, (int) ceil($totalRows / self::PAGE_SIZE));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $this->fetchRows($filters, self::PAGE_SIZE, $offset);
        $summary = $this->summary($filters);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'page' => $page,
            'page_size' => self::PAGE_SIZE,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ];
    }

    public function activityLogs(int $page): array
    {
        $page = max(1, $page);
        $totalRows = (int) $this->pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / self::PAGE_SIZE));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $statement = $this->pdo->query(
            'SELECT al.id, al.action, al.entity_type, al.entity_id,
                    al.before_data, al.after_data, al.created_at,
                    a.username AS admin_username
             FROM activity_logs al
             LEFT JOIN admins a ON a.id = al.admin_id
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT ' . self::PAGE_SIZE . ' OFFSET ' . $offset
        );

        return [
            'logs' => $statement->fetchAll(),
            'page' => $page,
            'total_pages' => $totalPages,
            'total_rows' => $totalRows,
        ];
    }

    public function exportCsv(array $filters): never
    {
        $rows = $this->fetchRows($filters, null, 0);
        $headers = $this->exportHeaders($filters['report']);
        $filename = 'cozycafe-' . $filters['report'] . '-' . $filters['start_date'] . '-'
            . $filters['end_date'] . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, private');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new RuntimeException('File ekspor laporan tidak dapat dibuat.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, array_map($this->safeCsvCell(...), $headers));
        foreach ($rows as $row) {
            fputcsv($output, array_map($this->safeCsvCell(...), $this->exportRow($filters['report'], $row)));
        }
        fclose($output);
        exit;
    }

    private function validatedDate(mixed $value, string $default, string $label): string
    {
        if ($value === null) {
            return $default;
        }
        if (!is_string($value)) {
            throw new DomainException('Format ' . $label . ' tidak valid.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Jakarta'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value) {
            throw new DomainException('Format ' . $label . ' tidak valid.');
        }

        return $value;
    }

    private function orderConditions(
        array $filters,
        string $paymentStatusColumn = 'o.payment_status',
        string $dateColumn = 'o.created_at',
        string $paymentMethodColumn = 'o.payment_method_id'
    ): array
    {
        $conditions = [$dateColumn . ' >= :start_at', $dateColumn . ' < :end_at'];
        $parameters = [
            'start_at' => $filters['start_date'] . ' 00:00:00',
            'end_at' => (new DateTimeImmutable($filters['end_date'], new DateTimeZone('Asia/Jakarta')))
                ->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
        ];
        if ($filters['order_type'] !== '') {
            $conditions[] = 'o.order_type = :order_type';
            $parameters['order_type'] = $filters['order_type'];
        }
        if ($filters['payment_method_id'] !== null) {
            $conditions[] = $paymentMethodColumn . ' = :payment_method_id';
            $parameters['payment_method_id'] = $filters['payment_method_id'];
        }
        if ($filters['payment_status'] !== '') {
            $conditions[] = $paymentStatusColumn . ' = :payment_status';
            $parameters['payment_status'] = $filters['payment_status'];
        }

        return [implode(' AND ', $conditions), $parameters];
    }

    private function fetchRows(array $filters, ?int $limit, int $offset): array
    {
        $reportType = $filters['report'];
        if ($reportType === 'bookings') {
            $where = 'b.booking_date >= :start_date AND b.booking_date <= :end_date';
            $parameters = [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ];
            $sql = 'SELECT b.booking_code, b.booking_date, b.slot_start, b.slot_end,
                           b.guest_count, b.status,
                           GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR \', \') AS area_names,
                           GROUP_CONCAT(DISTINCT t.table_number ORDER BY t.table_number SEPARATOR \', \') AS table_numbers
                    FROM bookings b
                    LEFT JOIN booking_tables bt ON bt.booking_id = b.id
                    LEFT JOIN tables t ON t.id = bt.table_id
                    LEFT JOIN booking_areas a ON a.id = t.area_id
                    WHERE ' . $where . '
                    GROUP BY b.id
                    ORDER BY b.booking_date DESC, b.slot_start DESC, b.id DESC';
        } elseif ($reportType === 'payments') {
            [$where, $parameters] = $this->orderConditions(
                $filters,
                'p.status',
                'p.created_at',
                'p.payment_method_id'
            );
            $sql = 'SELECT p.created_at AS payment_date, o.order_code, o.order_type,
                           pm.name AS payment_method_name, p.status AS payment_status,
                           p.amount
                    FROM payments p
                    INNER JOIN orders o ON o.id = p.order_id
                    INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
                    WHERE ' . $where . '
                    ORDER BY p.created_at DESC, p.id DESC';
        } elseif ($reportType === 'revenue') {
            [$where, $parameters] = $this->orderConditions($filters);
            $sql = 'SELECT DATE(o.created_at) AS report_date,
                           COUNT(*) AS order_count,
                           COALESCE(SUM(CASE WHEN o.payment_status = \'paid\' THEN o.total_amount ELSE 0 END), 0) AS revenue
                    FROM orders o
                    WHERE ' . $where . '
                    GROUP BY DATE(o.created_at)
                    ORDER BY report_date DESC';
        } elseif ($reportType === 'orders') {
            [$where, $parameters] = $this->orderConditions($filters);
            $sql = 'SELECT o.created_at, o.order_code, o.order_type, o.payment_status,
                           o.order_status, oi.product_name_snapshot AS product_name,
                           oi.quantity, oi.price_snapshot, oi.subtotal
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE ' . $where . '
                    ORDER BY o.created_at DESC, o.id DESC, oi.id ASC';
        } else {
            [$where, $parameters] = $this->orderConditions($filters);
            $sql = 'SELECT o.created_at, o.order_code, o.order_type,
                           pm.name AS payment_method_name, o.payment_status,
                           o.order_status, o.total_amount
                    FROM orders o
                    INNER JOIN payment_methods pm ON pm.id = o.payment_method_id
                    WHERE ' . $where . '
                    ORDER BY o.created_at DESC, o.id DESC';
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    private function countRows(array $filters): int
    {
        $reportType = $filters['report'];
        if ($reportType === 'bookings') {
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) FROM bookings
                 WHERE booking_date >= :start_date AND booking_date <= :end_date'
            );
            $statement->execute([
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ]);
        } elseif ($reportType === 'payments') {
            [$where, $parameters] = $this->orderConditions(
                $filters,
                'p.status',
                'p.created_at',
                'p.payment_method_id'
            );
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM payments p
                 INNER JOIN orders o ON o.id = p.order_id
                 WHERE ' . $where
            );
            $statement->execute($parameters);
        } elseif ($reportType === 'revenue') {
            [$where, $parameters] = $this->orderConditions($filters);
            $statement = $this->pdo->prepare(
                'SELECT COUNT(DISTINCT DATE(o.created_at))
                 FROM orders o
                 WHERE ' . $where
            );
            $statement->execute($parameters);
        } elseif ($reportType === 'orders') {
            [$where, $parameters] = $this->orderConditions($filters);
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM order_items oi
                 INNER JOIN orders o ON o.id = oi.order_id
                 WHERE ' . $where
            );
            $statement->execute($parameters);
        } else {
            [$where, $parameters] = $this->orderConditions($filters);
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM orders o WHERE ' . $where);
            $statement->execute($parameters);
        }

        return (int) $statement->fetchColumn();
    }

    private function summary(array $filters): array
    {
        if ($filters['report'] === 'bookings') {
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS record_count, COALESCE(SUM(guest_count), 0) AS guest_count
                 FROM bookings
                 WHERE booking_date >= :start_date AND booking_date <= :end_date'
            );
            $statement->execute([
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ]);
            $data = $statement->fetch();
            return [
                ['label' => 'Jumlah reservasi', 'value' => (int) $data['record_count']],
                ['label' => 'Jumlah tamu', 'value' => (int) $data['guest_count']],
            ];
        }

        if ($filters['report'] === 'payments') {
            [$where, $parameters] = $this->orderConditions($filters, 'p.status');
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS record_count,
                        COALESCE(SUM(p.amount), 0) AS total_amount,
                        COALESCE(SUM(CASE WHEN p.status = \'paid\' THEN p.amount ELSE 0 END), 0) AS paid_amount
                 FROM payments p
                 INNER JOIN orders o ON o.id = p.order_id
                 WHERE ' . $where
            );
            $statement->execute($parameters);
            $data = $statement->fetch();

            return [
                ['label' => 'Transaksi pembayaran', 'value' => (int) $data['record_count']],
                ['label' => 'Total pembayaran', 'value' => formatRupiah($data['total_amount'])],
                ['label' => 'Pembayaran berhasil', 'value' => formatRupiah($data['paid_amount'])],
            ];
        }

        [$where, $parameters] = $this->orderConditions($filters);
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS record_count,
                    COALESCE(SUM(o.total_amount), 0) AS total_amount,
                    COALESCE(SUM(CASE WHEN o.payment_status = \'paid\' THEN o.total_amount ELSE 0 END), 0) AS paid_amount
             FROM orders o
             WHERE ' . $where
        );
        $statement->execute($parameters);
        $data = $statement->fetch();

        if ($filters['report'] === 'revenue') {
            return [
                ['label' => 'Jumlah pesanan', 'value' => (int) $data['record_count']],
                ['label' => 'Pendapatan terbayar', 'value' => formatRupiah($data['paid_amount'])],
            ];
        }

        return [
            ['label' => 'Jumlah pesanan', 'value' => (int) $data['record_count']],
            ['label' => 'Nilai pesanan', 'value' => formatRupiah($data['total_amount'])],
            ['label' => 'Pendapatan terbayar', 'value' => formatRupiah($data['paid_amount'])],
        ];
    }

    private function exportHeaders(string $reportType): array
    {
        return match ($reportType) {
            'orders' => ['Tanggal', 'Kode Pesanan', 'Jenis Pesanan', 'Status Pembayaran', 'Status Pesanan', 'Produk', 'Jumlah', 'Harga Satuan', 'Subtotal'],
            'payments' => ['Tanggal Pembayaran', 'Kode Pesanan', 'Jenis Pesanan', 'Metode Pembayaran', 'Status Pembayaran', 'Jumlah'],
            'bookings' => ['Tanggal', 'Slot', 'Kode Reservasi', 'Area', 'Meja', 'Jumlah Tamu', 'Status'],
            'revenue' => ['Tanggal', 'Jumlah Pesanan', 'Pendapatan Terbayar'],
            default => ['Tanggal', 'Kode Pesanan', 'Jenis Pesanan', 'Metode Pembayaran', 'Status Pembayaran', 'Status Pesanan', 'Total'],
        };
    }

    private function exportRow(string $reportType, array $row): array
    {
        if ($reportType === 'bookings') {
            return [
                $row['booking_date'],
                substr((string) $row['slot_start'], 0, 5) . '–' . substr((string) $row['slot_end'], 0, 5),
                $row['booking_code'],
                $row['area_names'] ?? '',
                $row['table_numbers'] ?? '',
                $row['guest_count'],
                $this->bookingStatusLabel($row['status']),
            ];
        }
        if ($reportType === 'revenue') {
            return [$row['report_date'], $row['order_count'], formatRupiah($row['revenue'])];
        }
        if ($reportType === 'payments') {
            return [
                formatDateTimeWib($row['payment_date']),
                $row['order_code'],
                $this->orderTypeLabel($row['order_type']),
                $row['payment_method_name'],
                $this->paymentStatusLabel($row['payment_status']),
                formatRupiah($row['amount']),
            ];
        }
        if ($reportType === 'orders') {
            return [
                formatDateTimeWib($row['created_at']),
                $row['order_code'],
                $this->orderTypeLabel($row['order_type']),
                $this->paymentStatusLabel($row['payment_status']),
                $this->orderStatusLabel($row['order_status']),
                $row['product_name'],
                $row['quantity'],
                formatRupiah($row['price_snapshot']),
                formatRupiah($row['subtotal']),
            ];
        }

        return [
            formatDateTimeWib($row['created_at']),
            $row['order_code'],
            $this->orderTypeLabel($row['order_type']),
            $row['payment_method_name'],
            $this->paymentStatusLabel($row['payment_status']),
            $this->orderStatusLabel($row['order_status']),
            formatRupiah($row['total_amount']),
        ];
    }

    public function orderStatusLabel(string $status): string
    {
        return [
            'pending' => 'Menunggu konfirmasi',
            'processing' => 'Diproses',
            'ready' => 'Siap',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            'cancellation_requested' => 'Pengajuan pembatalan',
            'cancellation_rejected' => 'Pembatalan ditolak',
        ][$status] ?? 'Tidak diketahui';
    }

    public function paymentStatusLabel(string $status): string
    {
        return [
            'unpaid' => 'Belum dibayar',
            'waiting_verification' => 'Menunggu verifikasi',
            'paid' => 'Lunas',
            'rejected' => 'Ditolak',
            'refunded' => 'Dikembalikan',
        ][$status] ?? 'Tidak diketahui';
    }

    public function bookingStatusLabel(string $status): string
    {
        return [
            'pending' => 'Menunggu konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'rejected' => 'Ditolak',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'cancellation_requested' => 'Pengajuan pembatalan',
            'checked_in' => 'Check-in',
            'no_show' => 'Tidak hadir',
        ][$status] ?? 'Tidak diketahui';
    }

    public function orderTypeLabel(string $orderType): string
    {
        return $orderType === 'dine_in' ? 'Dine-in' : 'Takeaway';
    }
}
