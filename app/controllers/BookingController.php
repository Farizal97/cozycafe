<?php

declare(strict_types=1);

final class BookingController
{
    private const SLOTS = [
        ['start' => '09:00:00', 'end' => '12:00:00', 'label' => '09.00–12.00'],
        ['start' => '12:00:00', 'end' => '15:00:00', 'label' => '12.00–15.00'],
        ['start' => '15:00:00', 'end' => '18:00:00', 'label' => '15.00–18.00'],
        ['start' => '18:00:00', 'end' => '21:00:00', 'label' => '18.00–21.00'],
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function areas(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, description FROM booking_areas WHERE is_active = 1 ORDER BY name'
        );

        return $statement->fetchAll();
    }

    public function slotsForToday(): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta'));
        $dayOfWeek = (int) $today->format('N');
        $hours = $this->pdo->prepare(
            'SELECT opening_time, closing_time, is_open
             FROM operating_hours
             WHERE day_of_week = :day_of_week
             LIMIT 1'
        );
        $hours->execute(['day_of_week' => $dayOfWeek]);
        $operatingHours = $hours->fetch();

        if ($operatingHours !== false && (int) $operatingHours['is_open'] !== 1) {
            return [];
        }

        $closure = $this->pdo->prepare(
            'SELECT 1 FROM special_closures
             WHERE closure_date = :closure_date AND is_closed = 1
             LIMIT 1'
        );
        $closure->execute(['closure_date' => $today->format('Y-m-d')]);
        if ($closure->fetchColumn() !== false) {
            return [];
        }

        $openingTime = $operatingHours === false ? '09:00:00' : $operatingHours['opening_time'];
        $closingTime = $operatingHours === false ? '22:00:00' : $operatingHours['closing_time'];
        $currentTime = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('H:i:s');

        return array_values(array_filter(self::SLOTS, static fn (array $slot): bool =>
            $slot['start'] > $currentTime
            && $slot['start'] >= $openingTime
            && $slot['end'] <= $closingTime
        ));
    }

    public function tables(int $areaId, int $guestCount, array $slot): array
    {
        $statement = $this->pdo->prepare(
            "SELECT t.id, t.table_number, t.capacity, t.position_x, t.position_y,
                    NOT EXISTS (
                        SELECT 1
                        FROM booking_tables bt
                        INNER JOIN bookings b ON b.id = bt.booking_id
                        WHERE bt.table_id = t.id
                          AND b.booking_date = :booking_date
                          AND b.slot_start < :booking_end
                          AND b.slot_end > :booking_start
                          AND b.status IN ('pending', 'confirmed', 'cancellation_requested', 'checked_in')
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM table_sessions ts
                        WHERE ts.table_id = t.id
                          AND ts.status = 'active'
                          AND ts.started_at < :session_end
                          AND ts.expected_end_at > :session_start
                    ) AS is_available
             FROM tables t
             WHERE t.area_id = :area_id
               AND t.is_active = 1
               AND t.capacity >= :guest_count
             ORDER BY t.table_number"
        );
        $date = (new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $statement->execute([
            'booking_date' => $date,
            'booking_end' => $slot['end'],
            'booking_start' => $slot['start'],
            'session_end' => $date . ' ' . $slot['end'],
            'session_start' => $date . ' ' . $slot['start'],
            'area_id' => $areaId,
            'guest_count' => $guestCount,
        ]);

        return $statement->fetchAll();
    }

    public function create(array $input): array
    {
        $name = trim(is_string($input['customer_name'] ?? null) ? $input['customer_name'] : '');
        $phone = trim(is_string($input['phone'] ?? null) ? $input['phone'] : '');
        $phone = preg_replace('/\s+/', '', $phone) ?? '';
        $areaId = filter_var($input['area_id'] ?? null, FILTER_VALIDATE_INT);
        $tableId = filter_var($input['table_id'] ?? null, FILTER_VALIDATE_INT);
        $guestCount = filter_var($input['guest_count'] ?? null, FILTER_VALIDATE_INT);
        $slot = $this->findSlot(is_string($input['slot_start'] ?? null) ? $input['slot_start'] : '');

        if ($name === '' || mb_strlen($name) > 160) {
            throw new DomainException('Masukkan nama lengkap (maksimal 160 karakter).');
        }
        if (preg_match('/\A\+?[0-9][0-9().-]{7,20}\z/', $phone) !== 1) {
            throw new DomainException('Masukkan nomor telepon yang valid.');
        }
        if ($areaId === false || $areaId < 1 || $tableId === false || $tableId < 1
            || $guestCount === false || $guestCount < 1 || $guestCount > 255 || $slot === null) {
            throw new DomainException('Pilihan reservasi tidak valid. Silakan pilih area, slot, jumlah tamu, dan meja kembali.');
        }

        $availableSlots = $this->slotsForToday();
        if (!in_array($slot['start'], array_column($availableSlots, 'start'), true)) {
            throw new DomainException('Slot reservasi tersebut sudah lewat atau kafe tutup.');
        }

        $date = (new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $lockName = 'cozycafe_booking_' . str_replace('-', '', $date);
        $lock = $this->pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
        $lock->execute(['lock_name' => $lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new DomainException('Reservasi sedang ramai diproses. Silakan coba kembali.');
        }

        try {
            $this->pdo->beginTransaction();

            $tableQuery = $this->pdo->prepare(
                'SELECT t.id, t.capacity
                 FROM tables t
                 INNER JOIN booking_areas a ON a.id = t.area_id
                 WHERE t.id = :table_id AND t.area_id = :area_id
                   AND t.is_active = 1 AND a.is_active = 1
                 FOR UPDATE'
            );
            $tableQuery->execute(['table_id' => $tableId, 'area_id' => $areaId]);
            $table = $tableQuery->fetch();
            if ($table === false || (int) $table['capacity'] < $guestCount) {
                throw new DomainException('Meja tidak tersedia untuk jumlah tamu tersebut.');
            }
            if ($this->tableIsBooked($tableId, $date, $slot)) {
                throw new DomainException('Meja baru saja dipesan. Silakan pilih meja lain.');
            }

            $customerQuery = $this->pdo->prepare(
                'INSERT INTO customers (name, phone)
                 VALUES (:name, :phone)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), is_anonymized = 0'
            );
            $customerQuery->execute(['name' => $name, 'phone' => $phone]);
            $customerQuery = $this->pdo->prepare('SELECT id FROM customers WHERE phone = :phone');
            $customerQuery->execute(['phone' => $phone]);
            $customerId = $customerQuery->fetchColumn();
            if ($customerId === false) {
                throw new RuntimeException('Data pelanggan tidak dapat disimpan.');
            }

            $nextNumberQuery = $this->pdo->prepare(
                'SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(booking_code, \'-\', -1) AS UNSIGNED)), 0)
                 FROM bookings
                 WHERE booking_code LIKE :prefix'
            );
            $nextNumberQuery->execute(['prefix' => 'BK-' . str_replace('-', '', $date) . '-%']);
            $nextNumber = (int) $nextNumberQuery->fetchColumn() + 1;
            $bookingCode = 'BK-' . str_replace('-', '', $date) . '-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $modeQuery = $this->pdo->prepare(
                "SELECT setting_value FROM cafe_settings WHERE setting_key = 'booking_confirmation_mode' LIMIT 1"
            );
            $modeQuery->execute();
            $mode = $modeQuery->fetchColumn() === 'auto' ? 'auto' : 'manual';
            $status = $mode === 'auto' ? 'confirmed' : 'pending';
            $qrToken = bin2hex(random_bytes(32));

            $bookingQuery = $this->pdo->prepare(
                'INSERT INTO bookings
                    (booking_code, customer_id, booking_date, slot_start, slot_end, guest_count,
                     status, qr_token, confirmation_mode)
                 VALUES (:booking_code, :customer_id, :booking_date, :slot_start, :slot_end,
                         :guest_count, :status, :qr_token, :confirmation_mode)'
            );
            $bookingQuery->execute([
                'booking_code' => $bookingCode,
                'customer_id' => $customerId,
                'booking_date' => $date,
                'slot_start' => $slot['start'],
                'slot_end' => $slot['end'],
                'guest_count' => $guestCount,
                'status' => $status,
                'qr_token' => $qrToken,
                'confirmation_mode' => $mode,
            ]);

            $bookingId = (int) $this->pdo->lastInsertId();
            $bookingTableQuery = $this->pdo->prepare(
                'INSERT INTO booking_tables (booking_id, table_id) VALUES (:booking_id, :table_id)'
            );
            $bookingTableQuery->execute(['booking_id' => $bookingId, 'table_id' => $tableId]);
            $tableNameQuery = $this->pdo->prepare(
                'SELECT t.table_number, a.name AS area_name
                 FROM tables t
                 INNER JOIN booking_areas a ON a.id = t.area_id
                 WHERE t.id = :table_id'
            );
            $tableNameQuery->execute(['table_id' => $tableId]);
            $tableDetails = $tableNameQuery->fetch();

            $this->pdo->commit();

            return [
                'booking_code' => $bookingCode,
                'qr_token' => $qrToken,
                'status' => $status,
                'booking_date' => $date,
                'slot_label' => $slot['label'],
                'guest_count' => $guestCount,
                'table_number' => (string) $tableDetails['table_number'],
                'area_name' => (string) $tableDetails['area_name'],
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        } finally {
            $releaseLock = $this->pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
            $releaseLock->execute(['lock_name' => $lockName]);
        }
    }

    public function saveDraft(array $input): void
    {
        $_SESSION['booking_draft'] = [
            'customer_name' => substr(is_string($input['customer_name'] ?? null) ? $input['customer_name'] : '', 0, 160),
            'phone' => substr(is_string($input['phone'] ?? null) ? $input['phone'] : '', 0, 32),
            'area_id' => (int) ($input['area_id'] ?? 0),
            'table_id' => (int) ($input['table_id'] ?? 0),
            'guest_count' => (int) ($input['guest_count'] ?? 0),
            'slot_start' => is_string($input['slot_start'] ?? null) ? $input['slot_start'] : '',
        ];
    }

    public function consumeDraft(): array
    {
        $draft = $_SESSION['booking_draft'] ?? [];
        unset($_SESSION['booking_draft']);

        return is_array($draft) ? $draft : [];
    }

    public function consumeConfirmation(): ?array
    {
        $confirmation = $_SESSION['booking_confirmation'] ?? null;
        unset($_SESSION['booking_confirmation']);

        return is_array($confirmation) ? $confirmation : null;
    }

    public function slot(string $start): ?array
    {
        return $this->findSlot($start);
    }

    private function findSlot(string $start): ?array
    {
        foreach (self::SLOTS as $slot) {
            if ($slot['start'] === $start) {
                return $slot;
            }
        }

        return null;
    }

    private function tableIsBooked(int $tableId, string $date, array $slot): bool
    {
        $bookingQuery = $this->pdo->prepare(
            "SELECT 1
             FROM booking_tables bt
             INNER JOIN bookings b ON b.id = bt.booking_id
             WHERE bt.table_id = :table_id
               AND b.booking_date = :booking_date
               AND b.slot_start < :booking_end
               AND b.slot_end > :booking_start
               AND b.status IN ('pending', 'confirmed', 'cancellation_requested', 'checked_in')
             LIMIT 1"
        );
        $bookingQuery->execute([
            'table_id' => $tableId,
            'booking_date' => $date,
            'booking_end' => $slot['end'],
            'booking_start' => $slot['start'],
        ]);
        if ($bookingQuery->fetchColumn() !== false) {
            return true;
        }

        $sessionQuery = $this->pdo->prepare(
            "SELECT 1 FROM table_sessions
             WHERE table_id = :table_id AND status = 'active'
               AND started_at < :session_end AND expected_end_at > :session_start
             LIMIT 1"
        );
        $sessionQuery->execute([
            'table_id' => $tableId,
            'session_end' => $date . ' ' . $slot['end'],
            'session_start' => $date . ' ' . $slot['start'],
        ]);

        return $sessionQuery->fetchColumn() !== false;
    }
}
