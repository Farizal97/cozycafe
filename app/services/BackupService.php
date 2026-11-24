<?php

declare(strict_types=1);

final class BackupService
{
    private const FORMAT = 'cozycafe-database-backup';
    private const VERSION = 1;
    private const MAX_RESTORE_SIZE = 268435456;
    private const TABLES = [
        'admins', 'admin_login_attempts', 'categories', 'products', 'customers',
        'booking_areas', 'tables', 'payment_methods', 'bookings', 'orders',
        'booking_tables', 'order_items', 'order_status_histories',
        'order_cancellation_requests', 'order_substitutions', 'payments',
        'payment_proofs', 'payment_extensions', 'refunds', 'table_sessions',
        'booking_cancellation_requests', 'promotions', 'vouchers', 'voucher_usages',
        'reviews', 'testimonials', 'activity_logs', 'cafe_settings',
        'operating_hours', 'special_closures', 'backup_logs',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $backupDirectory
    ) {
    }

    public function all(): array
    {
        $backups = $this->pdo->query(
            'SELECT id, filename, backup_type, file_size, status, created_at
             FROM backup_logs ORDER BY created_at DESC, id DESC LIMIT 100'
        )->fetchAll();
        $directory = realpath($this->backupDirectory);
        foreach ($backups as &$backup) {
            $path = $directory === false
                ? false
                : realpath($directory . DIRECTORY_SEPARATOR . basename($backup['filename']));
            if ($backup['status'] === 'completed'
                && ($path === false || !is_file($path) || !is_readable($path))) {
                $backup['status'] = 'missing';
            }
        }
        unset($backup);
        return $backups;
    }

    public function create(): array
    {
        $this->ensureBackupDirectory();
        $filename = 'cozycafe_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.json';
        $temporaryPath = $this->backupDirectory . DIRECTORY_SEPARATOR . $filename . '.tmp';
        $backupPath = $this->backupDirectory . DIRECTORY_SEPARATOR . $filename;
        $handle = null;

        try {
            $handle = fopen($temporaryPath, 'xb');
            if ($handle === false) {
                throw new RuntimeException('File sementara untuk backup tidak dapat dibuat.');
            }
            chmod($temporaryPath, 0600);

            $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->pdo->beginTransaction();
            $this->writeJsonBackup($handle);
            if (!fflush($handle)) {
                throw new RuntimeException('Data backup gagal ditulis sepenuhnya.');
            }
            fclose($handle);
            $handle = null;
            $this->pdo->commit();

            if (!rename($temporaryPath, $backupPath)) {
                throw new RuntimeException('File backup gagal disimpan.');
            }
            chmod($backupPath, 0600);
            $fileSize = filesize($backupPath);
            if ($fileSize === false) {
                throw new RuntimeException('Ukuran file backup tidak dapat dibaca.');
            }
            if ($fileSize > self::MAX_RESTORE_SIZE) {
                throw new RuntimeException('Ukuran backup melebihi batas maksimum pemulihan.');
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO backup_logs (filename, backup_type, file_size, status)
                 VALUES (:filename, \'manual\', :file_size, \'completed\')'
            );
            $insert->execute(['filename' => $filename, 'file_size' => $fileSize]);

            return ['filename' => $filename, 'file_size' => $fileSize];
        } catch (Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            if (is_file($backupPath)) {
                unlink($backupPath);
            }
            throw $exception;
        }
    }

    public function downloadPath(string $filename): ?string
    {
        if (!$this->isValidFilename($filename)) {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM backup_logs
             WHERE filename = :filename AND status = \'completed\' LIMIT 1'
        );
        $statement->execute(['filename' => $filename]);
        if ($statement->fetchColumn() === false) {
            return null;
        }

        $directory = realpath($this->backupDirectory);
        $path = $directory === false ? false : realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($directory === false || $path === false || !is_file($path) || !is_readable($path)
            || strncasecmp($path, rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, strlen(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) !== 0) {
            return null;
        }

        return $path;
    }

    public function restore(string $filename): void
    {
        $path = $this->downloadPath($filename);
        if ($path === null) {
            throw new DomainException('File backup tidak ditemukan atau tidak valid.');
        }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > self::MAX_RESTORE_SIZE) {
            throw new DomainException('Ukuran file backup tidak valid atau melebihi batas.');
        }

        try {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new DomainException('File backup tidak dapat dibaca.');
            }
            $backup = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new DomainException('Format file backup tidak valid.', 0, $exception);
        }
        if (!is_array($backup) || ($backup['format'] ?? null) !== self::FORMAT
            || ($backup['version'] ?? null) !== self::VERSION
            || !is_array($backup['tables'] ?? null)
            || array_keys($backup['tables']) !== self::TABLES) {
            throw new DomainException('File bukan backup CozyCafe yang lengkap.');
        }

        $this->pdo->beginTransaction();
        $foreignKeysDisabled = false;
        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            $foreignKeysDisabled = true;
            foreach (array_reverse(self::TABLES) as $table) {
                $this->pdo->exec('DELETE FROM `' . $table . '`');
            }
            foreach (self::TABLES as $table) {
                $this->restoreTable($table, $backup['tables'][$table]);
            }
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $foreignKeysDisabled = false;
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        } finally {
            if ($foreignKeysDisabled) {
                $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
        }
    }

    private function writeJsonBackup($handle): void
    {
        $this->write($handle, '{"format":' . json_encode(self::FORMAT, JSON_THROW_ON_ERROR)
            . ',"version":' . self::VERSION
            . ',"created_at":' . json_encode(date(DATE_ATOM), JSON_THROW_ON_ERROR)
            . ',"tables":{');

        foreach (self::TABLES as $index => $table) {
            if ($index > 0) {
                $this->write($handle, ',');
            }
            $columns = $this->columns($table);
            $this->write(
                $handle,
                json_encode($table, JSON_THROW_ON_ERROR) . ':{"columns":'
                . json_encode($columns, JSON_THROW_ON_ERROR) . ',"rows":['
            );

            $query = $this->pdo->query('SELECT * FROM `' . $table . '`');
            $firstRow = true;
            while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
                if (!$firstRow) {
                    $this->write($handle, ',');
                }
                $firstRow = false;
                $this->write(
                    $handle,
                    json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
                );
            }
            $this->write($handle, ']}');
        }
        $this->write($handle, '}}');
    }

    private function restoreTable(string $table, mixed $tableData): void
    {
        if (!is_array($tableData) || !is_array($tableData['columns'] ?? null)
            || !is_array($tableData['rows'] ?? null)) {
            throw new DomainException('Struktur backup tidak valid untuk tabel ' . $table . '.');
        }
        $columns = $this->columns($table);
        if ($tableData['columns'] !== $columns) {
            throw new DomainException('Struktur tabel ' . $table . ' berbeda dari skema aplikasi ini.');
        }
        if ($columns === []) {
            throw new DomainException('Kolom tabel ' . $table . ' tidak dapat diverifikasi.');
        }

        $quotedColumns = array_map(static fn (string $column): string => '`' . $column . '`', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $insert = $this->pdo->prepare(
            'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ')
             VALUES (' . $placeholders . ')'
        );
        foreach ($tableData['rows'] as $row) {
            if (!is_array($row) || array_keys($row) !== $columns) {
                throw new DomainException('Salah satu baris backup tabel ' . $table . ' tidak valid.');
            }
            foreach ($row as $value) {
                if (!is_scalar($value) && $value !== null) {
                    throw new DomainException('Nilai backup berisi format yang tidak didukung.');
                }
            }
            $insert->execute(array_values($row));
        }
    }

    private function columns(string $table): array
    {
        $statement = $this->pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private function ensureBackupDirectory(): void
    {
        if (!is_dir($this->backupDirectory)
            && !mkdir($this->backupDirectory, 0700, true)
            && !is_dir($this->backupDirectory)) {
            throw new RuntimeException('Direktori backup tidak dapat dibuat.');
        }
        if (!is_writable($this->backupDirectory)) {
            throw new RuntimeException('Direktori backup tidak dapat ditulisi.');
        }
    }

    private function isValidFilename(string $filename): bool
    {
        return preg_match('/\Acozycafe_[0-9]{8}_[0-9]{6}_[a-f0-9]{12}\.json\z/', $filename) === 1;
    }

    private function write($handle, string $contents): void
    {
        $length = strlen($contents);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($handle, substr($contents, $written));
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('File backup tidak dapat ditulis.');
            }
            $written += $bytes;
        }
    }
}
