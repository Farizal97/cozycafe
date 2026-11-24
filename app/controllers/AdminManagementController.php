<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/LogService.php';
require_once dirname(__DIR__) . '/services/BackupService.php';

final class AdminManagementController
{
    private const WEEKDAYS = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];
    private readonly LogService $logs;
    private readonly BackupService $backups;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath,
        string $root
    ) {
        $this->logs = new LogService($pdo);
        $this->backups = new BackupService(
            $pdo,
            $root . DIRECTORY_SEPARATOR . 'backups'
        );
    }

    public function promotions(): array
    {
        $vouchers = $this->pdo->query(
            'SELECT v.*,
                    (SELECT COUNT(*) FROM voucher_usages vu WHERE vu.voucher_id = v.id) AS usage_count
             FROM vouchers v ORDER BY v.created_at DESC, v.id DESC'
        )->fetchAll();
        $promotions = $this->pdo->query(
            'SELECT * FROM promotions ORDER BY created_at DESC, id DESC'
        )->fetchAll();

        return ['vouchers' => $vouchers, 'promotions' => $promotions];
    }

    public function voucher(int $id): ?array
    {
        return $this->findById('vouchers', $id);
    }

    public function promotion(int $id): ?array
    {
        return $this->findById('promotions', $id);
    }

    public function saveVoucher(?int $id): never
    {
        $code = strtoupper(trim(is_string($_POST['code'] ?? null) ? $_POST['code'] : ''));
        $discountType = $_POST['discount_type'] ?? '';
        $discountValue = $this->decimalInput($_POST['discount_value'] ?? null);
        $minimumPurchase = $this->decimalInput($_POST['minimum_purchase'] ?? null);
        $totalLimit = $this->optionalPositiveInt($_POST['total_usage_limit'] ?? null);
        $phoneLimit = $this->optionalPositiveInt($_POST['per_phone_limit'] ?? null);
        $startDate = $this->dateTimeInput($_POST['start_date'] ?? null);
        $endDate = $this->dateTimeInput($_POST['end_date'] ?? null);
        $isActive = ($_POST['is_active'] ?? '') === '1' ? 1 : 0;
        if (preg_match('/\A[A-Z0-9_-]{3,64}\z/', $code) !== 1
            || !in_array($discountType, ['percentage', 'fixed'], true)
            || $discountValue === null || $discountValue <= 0
            || ($discountType === 'percentage' && $discountValue > 100)
            || $minimumPurchase === null || $minimumPurchase < 0
            || $totalLimit === false || $phoneLimit === false
            || $startDate === null || $endDate === null || $startDate >= $endDate) {
            setFlash('danger', 'Data voucher tidak valid. Periksa kode, diskon, batas penggunaan, dan masa berlaku.');
            $this->redirect($id === null ? 'admin/vouchers/create' : 'admin/vouchers/edit?id=' . $id);
        }

        $this->saveDiscount('vouchers', $id, [
            'code' => $code,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'minimum_purchase' => $minimumPurchase,
            'total_usage_limit' => $totalLimit,
            'per_phone_limit' => $phoneLimit,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isActive,
        ], 'voucher');
    }

    public function savePromotion(?int $id): never
    {
        $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
        $discountType = $_POST['discount_type'] ?? '';
        $discountValue = $this->decimalInput($_POST['discount_value'] ?? null);
        $minimumPurchase = $this->decimalInput($_POST['minimum_purchase'] ?? null);
        $startDate = $this->dateTimeInput($_POST['start_date'] ?? null);
        $endDate = $this->dateTimeInput($_POST['end_date'] ?? null);
        $isActive = ($_POST['is_active'] ?? '') === '1' ? 1 : 0;
        if ($name === '' || mb_strlen($name) > 160
            || !in_array($discountType, ['percentage', 'fixed'], true)
            || $discountValue === null || $discountValue <= 0
            || ($discountType === 'percentage' && $discountValue > 100)
            || $minimumPurchase === null || $minimumPurchase < 0
            || $startDate === null || $endDate === null || $startDate >= $endDate) {
            setFlash('danger', 'Data promosi tidak valid. Periksa nama, nilai diskon, dan masa berlaku.');
            $this->redirect($id === null ? 'admin/promotions/create' : 'admin/promotions/edit?id=' . $id);
        }

        $this->saveDiscount('promotions', $id, [
            'name' => $name,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'minimum_purchase' => $minimumPurchase,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isActive,
        ], 'promotion');
    }

    public function deleteDiscount(string $table, string $entityType): never
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($table, ['vouchers', 'promotions'], true)
            || $id === false || $id < 1) {
            setFlash('danger', 'Data diskon yang akan dihapus tidak valid.');
            $this->redirect('admin/promotions');
        }
        try {
            $this->pdo->beginTransaction();
            $before = $this->findById($table, (int) $id, true);
            if ($before === null) {
                throw new DomainException('Data diskon tidak ditemukan.');
            }
            if ($table === 'vouchers') {
                $usage = $this->pdo->prepare('SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = :id');
                $usage->execute(['id' => $id]);
                if ((int) $usage->fetchColumn() > 0) {
                    throw new DomainException('Voucher yang sudah pernah digunakan tidak dapat dihapus. Nonaktifkan voucher.');
                }
            }
            $delete = $this->pdo->prepare('DELETE FROM `' . $table . '` WHERE id = :id');
            $delete->execute(['id' => $id]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $entityType . '_deleted',
                $entityType,
                (int) $id,
                $before,
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/promotions');
            }
            throw $exception;
        }
        setFlash('success', 'Data diskon berhasil dihapus.');
        $this->redirect('admin/promotions');
    }

    public function reviews(string $status): array
    {
        if ($status !== '' && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new DomainException('Filter status ulasan tidak valid.');
        }
        $where = $status === '' ? '' : 'WHERE r.moderation_status = :status';
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.order_id, r.customer_id, r.review_type, r.rating, r.comment,
                    r.moderation_status, r.created_at, c.name AS customer_name,
                    o.order_code, p.name AS product_name
             FROM reviews r
             INNER JOIN customers c ON c.id = r.customer_id
             INNER JOIN orders o ON o.id = r.order_id
             LEFT JOIN products p ON p.id = r.product_id
             ' . $where . '
             ORDER BY CASE WHEN r.moderation_status = \'pending\' THEN 0 ELSE 1 END,
                      r.created_at DESC, r.id DESC
             LIMIT 200'
        );
        $statement->execute($status === '' ? [] : ['status' => $status]);
        $rating = $this->pdo->query(
            "SELECT COUNT(*) AS review_count, COALESCE(AVG(rating), 0) AS average_rating
             FROM reviews WHERE moderation_status = 'approved'"
        )->fetch();

        return [
            'reviews' => $statement->fetchAll(),
            'status' => $status,
            'stats' => $rating,
        ];
    }

    public function moderateReview(): never
    {
        $reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        $newStatus = match ($decision) {
            'approve' => 'approved',
            'hide', 'reject' => 'rejected',
            default => '',
        };
        if ($reviewId === false || $reviewId < 1 || $newStatus === '') {
            setFlash('danger', 'Tindakan moderasi ulasan tidak valid.');
            $this->redirect('admin/reviews');
        }

        $this->pdo->beginTransaction();
        try {
            $before = $this->findById('reviews', (int) $reviewId, true);
            if ($before === null) {
                throw new DomainException('Ulasan tidak ditemukan.');
            }
            $update = $this->pdo->prepare(
                'UPDATE reviews SET moderation_status = :status WHERE id = :id'
            );
            $update->execute(['status' => $newStatus, 'id' => $reviewId]);
            if ($newStatus !== 'approved') {
                $hideTestimonial = $this->pdo->prepare(
                    'UPDATE testimonials SET is_visible = 0 WHERE review_id = :review_id'
                );
                $hideTestimonial->execute(['review_id' => $reviewId]);
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'review_' . $newStatus,
                'review',
                (int) $reviewId,
                ['moderation_status' => $before['moderation_status']],
                ['moderation_status' => $newStatus]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/reviews');
            }
            throw $exception;
        }
        setFlash('success', $newStatus === 'approved' ? 'Ulasan disetujui.' : 'Ulasan disembunyikan dari pelanggan.');
        $this->redirect('admin/reviews');
    }

    public function deleteReview(): never
    {
        $reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reviewId === false || $reviewId < 1) {
            setFlash('danger', 'Ulasan yang akan dihapus tidak valid.');
            $this->redirect('admin/reviews');
        }
        $this->pdo->beginTransaction();
        try {
            $before = $this->findById('reviews', (int) $reviewId, true);
            if ($before === null) {
                throw new DomainException('Ulasan tidak ditemukan.');
            }
            $delete = $this->pdo->prepare('DELETE FROM reviews WHERE id = :id');
            $delete->execute(['id' => $reviewId]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'review_deleted',
                'review',
                (int) $reviewId,
                ['review_type' => $before['review_type'], 'rating' => (int) $before['rating']],
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/reviews');
            }
            throw $exception;
        }
        setFlash('success', 'Ulasan berhasil dihapus.');
        $this->redirect('admin/reviews');
    }

    public function testimonials(): array
    {
        return $this->pdo->query(
            'SELECT t.id, t.review_id, t.is_approved, t.is_visible, t.created_at,
                    r.review_type, r.rating, r.comment, r.moderation_status,
                    c.name AS customer_name, p.name AS product_name
             FROM testimonials t
             INNER JOIN reviews r ON r.id = t.review_id
             INNER JOIN customers c ON c.id = r.customer_id
             LEFT JOIN products p ON p.id = r.product_id
             ORDER BY t.created_at DESC, t.id DESC'
        )->fetchAll();
    }

    public function availableTestimonialReviews(): array
    {
        return $this->pdo->query(
            "SELECT r.id, r.review_type, r.rating, r.comment, c.name AS customer_name,
                    p.name AS product_name
             FROM reviews r
             INNER JOIN customers c ON c.id = r.customer_id
             LEFT JOIN products p ON p.id = r.product_id
             WHERE r.moderation_status = 'approved'
               AND NOT EXISTS (SELECT 1 FROM testimonials t WHERE t.review_id = r.id)
             ORDER BY r.created_at DESC, r.id DESC LIMIT 200"
        )->fetchAll();
    }

    public function createTestimonial(): never
    {
        $reviewId = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reviewId === false || $reviewId < 1) {
            setFlash('danger', 'Pilih ulasan yang valid untuk testimonial.');
            $this->redirect('admin/testimonials');
        }
        $this->pdo->beginTransaction();
        try {
            $review = $this->pdo->prepare(
                'SELECT id, moderation_status FROM reviews WHERE id = :id FOR UPDATE'
            );
            $review->execute(['id' => $reviewId]);
            $reviewData = $review->fetch();
            if ($reviewData === false || $reviewData['moderation_status'] !== 'approved') {
                throw new DomainException('Testimonial hanya dapat dibuat dari ulasan yang disetujui.');
            }
            $insert = $this->pdo->prepare(
                'INSERT INTO testimonials (review_id, is_approved, is_visible)
                 VALUES (:review_id, 0, 0)'
            );
            $insert->execute(['review_id' => $reviewId]);
            $testimonialId = (int) $this->pdo->lastInsertId();
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'testimonial_created',
                'testimonial',
                $testimonialId,
                null,
                ['review_id' => (int) $reviewId, 'is_approved' => false, 'is_visible' => false]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException || ($exception instanceof PDOException && $exception->getCode() === '23000')) {
                setFlash('danger', $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'Ulasan tersebut sudah menjadi testimonial.');
                $this->redirect('admin/testimonials');
            }
            throw $exception;
        }
        setFlash('success', 'Ulasan ditambahkan sebagai testimonial. Setujui untuk menampilkannya.');
        $this->redirect('admin/testimonials');
    }

    public function updateTestimonial(): never
    {
        $id = filter_var($_POST['testimonial_id'] ?? null, FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? '';
        if ($id === false || $id < 1 || !in_array($action, ['approve', 'show', 'hide'], true)) {
            setFlash('danger', 'Tindakan testimonial tidak valid.');
            $this->redirect('admin/testimonials');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM testimonials WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $id]);
            $before = $select->fetch();
            if ($before === false) {
                throw new DomainException('Testimonial tidak ditemukan.');
            }
            if ($action === 'show' && (int) $before['is_approved'] !== 1) {
                throw new DomainException('Setujui testimonial sebelum menampilkannya.');
            }
            $isApproved = $action === 'approve' ? 1 : (int) $before['is_approved'];
            $isVisible = in_array($action, ['approve', 'show'], true) ? 1 : 0;
            $update = $this->pdo->prepare(
                'UPDATE testimonials SET is_approved = :is_approved, is_visible = :is_visible WHERE id = :id'
            );
            $update->execute(['is_approved' => $isApproved, 'is_visible' => $isVisible, 'id' => $id]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'testimonial_' . $action,
                'testimonial',
                (int) $id,
                ['is_approved' => (int) $before['is_approved'], 'is_visible' => (int) $before['is_visible']],
                ['is_approved' => $isApproved, 'is_visible' => $isVisible]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/testimonials');
            }
            throw $exception;
        }
        setFlash('success', 'Status testimonial berhasil diperbarui.');
        $this->redirect('admin/testimonials');
    }

    public function deleteTestimonial(): never
    {
        $id = filter_var($_POST['testimonial_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            setFlash('danger', 'Testimonial yang akan dihapus tidak valid.');
            $this->redirect('admin/testimonials');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM testimonials WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $id]);
            $before = $select->fetch();
            if ($before === false) {
                throw new DomainException('Testimonial tidak ditemukan.');
            }
            $delete = $this->pdo->prepare('DELETE FROM testimonials WHERE id = :id');
            $delete->execute(['id' => $id]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'testimonial_deleted',
                'testimonial',
                (int) $id,
                ['review_id' => (int) $before['review_id']],
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/testimonials');
            }
            throw $exception;
        }
        setFlash('success', 'Testimonial berhasil dihapus.');
        $this->redirect('admin/testimonials');
    }

    public function settings(): array
    {
        $settings = $this->pdo->query(
            'SELECT setting_key, setting_value, updated_at FROM cafe_settings'
        )->fetchAll();
        $settingsByKey = [];
        foreach ($settings as $setting) {
            $settingsByKey[$setting['setting_key']] = $setting;
        }
        $closures = $this->pdo->query(
            'SELECT id, closure_date, reason, is_closed FROM special_closures
             ORDER BY closure_date DESC LIMIT 100'
        )->fetchAll();

        return [
            'settings' => $settingsByKey,
            'hours' => $this->pdo->query('SELECT * FROM operating_hours ORDER BY day_of_week')->fetchAll(),
            'closures' => $closures,
            'weekdays' => self::WEEKDAYS,
        ];
    }

    public function saveSettings(): never
    {
        $values = [];
        $percentageKeys = ['tax_percentage', 'service_charge_percentage'];
        foreach ($percentageKeys as $key) {
            $value = $this->decimalInput($_POST[$key] ?? null);
            if ($value === null || $value < 0 || $value > 100) {
                setFlash('danger', 'Persentase pajak dan layanan harus berada di antara 0 dan 100.');
                $this->redirect('admin/settings');
            }
            $values[$key] = number_format($value, 2, '.', '');
        }
        $mode = $_POST['booking_confirmation_mode'] ?? '';
        $duration = filter_var($_POST['table_session_duration_minutes'] ?? null, FILTER_VALIDATE_INT);
        $address = trim(is_string($_POST['cafe_address'] ?? null) ? $_POST['cafe_address'] : '');
        $phone = trim(is_string($_POST['cafe_phone'] ?? null) ? $_POST['cafe_phone'] : '');
        if (!in_array($mode, ['auto', 'manual'], true)
            || $duration === false || $duration < 30 || $duration > 1440
            || mb_strlen($address) > 255
            || ($phone !== '' && preg_match('/\A\+?[0-9 ()-]{8,24}\z/', $phone) !== 1)) {
            setFlash('danger', 'Pengaturan kafe tidak valid.');
            $this->redirect('admin/settings');
        }
        $values['booking_confirmation_mode'] = $mode;
        $values['table_session_duration_minutes'] = (string) $duration;
        $values['tax_enabled'] = ($_POST['tax_enabled'] ?? '') === '1' ? '1' : '0';
        $values['service_charge_enabled'] = ($_POST['service_charge_enabled'] ?? '') === '1' ? '1' : '0';
        $values['cafe_address'] = $address;
        $values['cafe_phone'] = $phone;

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare(
                'SELECT setting_value FROM cafe_settings WHERE setting_key = :setting_key LIMIT 1'
            );
            $upsert = $this->pdo->prepare(
                'INSERT INTO cafe_settings (setting_key, setting_value)
                 VALUES (:setting_key, :setting_value)
                 ON DUPLICATE KEY UPDATE setting_value = :updated_value'
            );
            foreach ($values as $key => $value) {
                $select->execute(['setting_key' => $key]);
                $before = $select->fetchColumn();
                $upsert->execute([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'updated_value' => $value,
                ]);
                $this->logs->record(
                    (int) $_SESSION['admin_id'],
                    'cafe_setting_updated',
                    'cafe_setting',
                    null,
                    $before === false ? null : ['setting_key' => $key, 'setting_value' => $before],
                    ['setting_key' => $key, 'setting_value' => $value]
                );
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
        setFlash('success', 'Pengaturan kafe berhasil disimpan.');
        $this->redirect('admin/settings');
    }

    public function saveOperatingHours(): never
    {
        $submitted = $_POST['hours'] ?? null;
        if (!is_array($submitted)) {
            setFlash('danger', 'Data jam operasional tidak valid.');
            $this->redirect('admin/settings');
        }
        $normalized = [];
        foreach (self::WEEKDAYS as $day => $label) {
            $dayInput = $submitted[$day] ?? null;
            if (!is_array($dayInput)) {
                setFlash('danger', 'Data jam operasional ' . $label . ' tidak lengkap.');
                $this->redirect('admin/settings');
            }
            $isOpen = ($dayInput['is_open'] ?? '') === '1' ? 1 : 0;
            $open = $isOpen ? $this->timeInput($dayInput['opening_time'] ?? null) : null;
            $close = $isOpen ? $this->timeInput($dayInput['closing_time'] ?? null) : null;
            if ($isOpen && ($open === null || $close === null || $open >= $close)) {
                setFlash('danger', 'Jam buka dan tutup ' . $label . ' tidak valid.');
                $this->redirect('admin/settings');
            }
            $normalized[$day] = ['is_open' => $isOpen, 'opening_time' => $open, 'closing_time' => $close];
        }

        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM operating_hours WHERE day_of_week = :day LIMIT 1');
            $insert = $this->pdo->prepare(
                'INSERT INTO operating_hours (day_of_week, opening_time, closing_time, is_open)
                 VALUES (:day, :opening_time, :closing_time, :is_open)'
            );
            $update = $this->pdo->prepare(
                'UPDATE operating_hours SET opening_time = :opening_time,
                 closing_time = :closing_time, is_open = :is_open WHERE day_of_week = :day'
            );
            foreach ($normalized as $day => $hours) {
                $select->execute(['day' => $day]);
                $before = $select->fetch();
                $params = $hours + ['day' => $day];
                if ($before === false) {
                    $insert->execute($params);
                } else {
                    $update->execute($params);
                }
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'operating_hours_updated',
                'operating_hours',
                null,
                null,
                $normalized
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
        setFlash('success', 'Jam operasional berhasil diperbarui.');
        $this->redirect('admin/settings');
    }

    public function addSpecialClosure(): never
    {
        $date = $_POST['closure_date'] ?? null;
        $reason = trim(is_string($_POST['reason'] ?? null) ? $_POST['reason'] : '');
        if (!is_string($date) || !$this->validDate($date) || $reason === '' || mb_strlen($reason) > 255) {
            setFlash('danger', 'Tanggal dan alasan penutupan khusus wajib diisi dengan benar.');
            $this->redirect('admin/settings');
        }
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO special_closures (closure_date, reason, is_closed)
                 VALUES (:closure_date, :reason, 1)'
            );
            $insert->execute(['closure_date' => $date, 'reason' => $reason]);
            $id = (int) $this->pdo->lastInsertId();
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'special_closure_created',
                'special_closure',
                $id,
                null,
                ['closure_date' => $date, 'reason' => $reason]
            );
            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                setFlash('danger', 'Tanggal tersebut sudah memiliki pengaturan penutupan.');
                $this->redirect('admin/settings');
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
        setFlash('success', 'Tanggal penutupan khusus berhasil ditambahkan.');
        $this->redirect('admin/settings');
    }

    public function deleteSpecialClosure(): never
    {
        $id = filter_var($_POST['closure_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            setFlash('danger', 'Tanggal penutupan tidak valid.');
            $this->redirect('admin/settings');
        }
        $this->pdo->beginTransaction();
        try {
            $select = $this->pdo->prepare('SELECT * FROM special_closures WHERE id = :id FOR UPDATE');
            $select->execute(['id' => $id]);
            $before = $select->fetch();
            if ($before === false) {
                throw new DomainException('Tanggal penutupan tidak ditemukan.');
            }
            $delete = $this->pdo->prepare('DELETE FROM special_closures WHERE id = :id');
            $delete->execute(['id' => $id]);
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                'special_closure_deleted',
                'special_closure',
                (int) $id,
                ['closure_date' => $before['closure_date'], 'reason' => $before['reason']],
                null
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/settings');
            }
            throw $exception;
        }
        setFlash('success', 'Tanggal penutupan berhasil dihapus.');
        $this->redirect('admin/settings');
    }

    public function backups(): array
    {
        return $this->backups->all();
    }

    public function createBackup(): never
    {
        $backup = $this->backups->create();
        $this->logs->record(
            (int) $_SESSION['admin_id'],
            'database_backup_created',
            'backup',
            null,
            null,
            ['filename' => $backup['filename'], 'file_size' => $backup['file_size']]
        );
        setFlash('success', 'Backup database berhasil dibuat.');
        $this->redirect('admin/backups');
    }

    public function downloadBackup(string $filename): ?string
    {
        return $this->backups->downloadPath($filename);
    }

    public function restoreBackup(): never
    {
        $filename = $_POST['filename'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        $password = $_POST['current_password'] ?? '';
        if (!is_string($filename) || !is_string($confirmation) || $confirmation !== 'RESTORE'
            || !is_string($password) || $password === '') {
            setFlash('danger', 'Ketik RESTORE dan masukkan password admin untuk melanjutkan.');
            $this->redirect('admin/backups');
        }
        $adminQuery = $this->pdo->prepare(
            'SELECT password_hash FROM admins WHERE id = :id LIMIT 1'
        );
        $adminQuery->execute(['id' => (int) $_SESSION['admin_id']]);
        $passwordHash = $adminQuery->fetchColumn();
        if (!is_string($passwordHash) || !password_verify($password, $passwordHash)) {
            setFlash('danger', 'Password admin tidak sesuai. Database tidak diubah.');
            $this->redirect('admin/backups');
        }

        $backupPath = $this->backups->downloadPath($filename);
        if ($backupPath === null || !is_file($backupPath)) {
            setFlash('danger', 'File backup tidak ditemukan atau tidak valid.');
            $this->redirect('admin/backups');
        }
        $backupSize = filesize($backupPath);
        if ($backupSize === false) {
            setFlash('danger', 'Ukuran file backup tidak dapat dibaca.');
            $this->redirect('admin/backups');
        }
        try {
            $this->backups->restore($filename);
        } catch (DomainException $exception) {
            setFlash('danger', $exception->getMessage());
            $this->redirect('admin/backups');
        }
        $backupLog = $this->pdo->prepare(
            'SELECT 1 FROM backup_logs WHERE filename = :filename LIMIT 1'
        );
        $backupLog->execute(['filename' => $filename]);
        if ($backupLog->fetchColumn() === false) {
            $insertBackupLog = $this->pdo->prepare(
                'INSERT INTO backup_logs (filename, backup_type, file_size, status)
                 VALUES (:filename, \'manual\', :file_size, \'completed\')'
            );
            $insertBackupLog->execute(['filename' => $filename, 'file_size' => $backupSize]);
        }
        $adminId = (int) $_SESSION['admin_id'];
        $adminExists = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $adminExists->execute(['id' => $adminId]);
        $logAdminId = $adminExists->fetchColumn() === false ? null : $adminId;
        $this->logs->record(
            $logAdminId,
            'database_backup_restored',
            'backup',
            null,
            null,
            ['filename' => $filename]
        );
        session_regenerate_id(true);
        setFlash('success', 'Backup database berhasil dipulihkan. Periksa kembali data operasional dan file upload.');
        $this->redirect('admin/backups');
    }

    private function saveDiscount(string $table, ?int $id, array $values, string $entityType): never
    {
        $this->pdo->beginTransaction();
        try {
            $before = $id === null ? null : $this->findById($table, $id, true);
            if ($id !== null && $before === null) {
                throw new DomainException('Data diskon tidak ditemukan.');
            }
            $columns = array_keys($values);
            if ($id === null) {
                $quotedColumns = array_map(static fn (string $column): string => '`' . $column . '`', $columns);
                $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
                $statement = $this->pdo->prepare(
                    'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ')
                     VALUES (' . implode(', ', $placeholders) . ')'
                );
                $statement->execute($values);
                $id = (int) $this->pdo->lastInsertId();
                $action = $entityType . '_created';
            } else {
                $assignments = array_map(
                    static fn (string $column): string => '`' . $column . '` = :' . $column,
                    $columns
                );
                $statement = $this->pdo->prepare(
                    'UPDATE `' . $table . '` SET ' . implode(', ', $assignments) . ' WHERE id = :id'
                );
                $statement->execute($values + ['id' => $id]);
                $action = $entityType . '_updated';
            }
            $this->logs->record(
                (int) $_SESSION['admin_id'],
                $action,
                $entityType,
                $id,
                $before,
                $values
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                setFlash('danger', $table === 'vouchers' ? 'Kode voucher sudah digunakan.' : 'Promosi tidak dapat disimpan.');
                $this->redirect($table === 'vouchers'
                    ? ($id === null ? 'admin/vouchers/create' : 'admin/vouchers/edit?id=' . $id)
                    : ($id === null ? 'admin/promotions/create' : 'admin/promotions/edit?id=' . $id));
            }
            if ($exception instanceof DomainException) {
                setFlash('danger', $exception->getMessage());
                $this->redirect('admin/promotions');
            }
            throw $exception;
        }
        setFlash('success', $entityType === 'voucher' ? 'Voucher berhasil disimpan.' : 'Promosi berhasil disimpan.');
        $this->redirect('admin/promotions');
    }

    private function findById(string $table, int $id, bool $forUpdate = false): ?array
    {
        if (!in_array($table, ['vouchers', 'promotions', 'reviews'], true)) {
            throw new LogicException('Tabel tidak diizinkan.');
        }
        $statement = $this->pdo->prepare(
            'SELECT * FROM `' . $table . '` WHERE id = :id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    private function decimalInput(mixed $value): ?float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        $value = trim((string) $value);
        if (preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/', $value) !== 1) {
            return null;
        }
        return (float) $value;
    }

    private function optionalPositiveInt(mixed $value): int|null|false
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            return false;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT);
        return $number !== false && $number > 0 ? $number : false;
    }

    private function dateTimeInput(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Jakarta'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d\TH:i') !== $value) {
            return null;
        }
        return $date->format('Y-m-d H:i:s');
    }

    private function timeInput(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/', $value) !== 1) {
            return null;
        }
        return $value . ':00';
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Asia/Jakarta'));
        $errors = DateTimeImmutable::getLastErrors();
        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date;
    }

    private function redirect(string $path): never
    {
        redirect(rtrim($this->basePath, '/') . '/' . ltrim($path, '/'));
    }
}
