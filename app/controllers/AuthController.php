<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/LogService.php';

final class AuthController
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private readonly LogService $logs;

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $basePath
    ) {
        $this->logs = new LogService($pdo);
    }

    public function login(): never
    {
        $username = $_POST['username'] ?? '';
        $username = is_string($username) ? trim($username) : '';
        $username = substr($username, 0, 80);
        $password = $_POST['password'] ?? '';
        $password = is_string($password) ? $password : '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!is_string($ipAddress) || filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            $ipAddress = '0.0.0.0';
        }

        if (!$this->acquireAttemptLock($username, $ipAddress)) {
            setFlash('warning', 'Login sedang diproses. Silakan coba kembali.');
            redirect($this->url('admin/login'));
        }

        $redirectPath = 'admin/login';
        try {
            $lock = $this->activeLock($username, $ipAddress);
            if ($lock !== null) {
                $unlockTime = new DateTimeImmutable($lock, new DateTimeZone('Asia/Jakarta'));
                setFlash('danger', 'Terlalu banyak percobaan masuk. Coba lagi setelah ' . formatDateTimeWib($unlockTime->format('Y-m-d H:i:s')) . '.');
            } else {
                $statement = $this->pdo->prepare(
                    'SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1'
                );
                $statement->execute(['username' => $username]);
                $admin = $statement->fetch();

                if ($admin !== false && password_verify($password, $admin['password_hash'])) {
                    $this->recordSuccessfulLogin((int) $admin['id'], $username, $ipAddress);

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_last_activity'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    setFlash('success', 'Berhasil masuk ke dashboard CozyCafe.');
                    $redirectPath = 'admin';
                } else {
                    [$failedAttempts, $lockedUntil] = $this->recordFailedLogin($username, $ipAddress);
                    if ($lockedUntil !== null) {
                        setFlash('danger', 'Username atau password tidak tepat. Akun dikunci selama 15 menit karena terlalu banyak percobaan.');
                    } else {
                        $remaining = self::MAX_FAILED_ATTEMPTS - $failedAttempts;
                        setFlash('danger', 'Username atau password tidak tepat. Sisa percobaan: ' . $remaining . '.');
                    }
                }
            }
        } finally {
            $this->releaseAttemptLock($username, $ipAddress);
        }

        redirect($this->url($redirectPath));
    }

    public function logout(): never
    {
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $username = (string) ($_SESSION['admin_username'] ?? '');
        $this->logs->record(
            $adminId > 0 ? $adminId : null,
            'admin_logout',
            'admin',
            $adminId > 0 ? $adminId : null,
            ['username' => $username],
            null
        );

        $_SESSION = [];
        session_regenerate_id(true);
        setFlash('success', 'Anda telah keluar dari dashboard.');
        redirect($this->url('admin/login'));
    }

    public function updateProfile(): never
    {
        $adminId = (int) $_SESSION['admin_id'];
        $username = $_POST['username'] ?? '';
        $username = is_string($username) ? trim($username) : '';
        $currentPassword = $_POST['current_password'] ?? '';
        $currentPassword = is_string($currentPassword) ? $currentPassword : '';

        if (!preg_match('/\A[A-Za-z0-9._-]{3,80}\z/', $username)) {
            setFlash('danger', 'Username harus berisi 3–80 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.');
            redirect($this->url('admin/profile'));
        }

        $admin = $this->adminById($adminId);
        if (!password_verify($currentPassword, $admin['password_hash'])) {
            setFlash('danger', 'Password saat ini tidak sesuai.');
            redirect($this->url('admin/profile'));
        }

        try {
            $this->pdo->beginTransaction();
            $statement = $this->pdo->prepare(
                'UPDATE admins SET username = :username WHERE id = :id'
            );
            $statement->execute(['username' => $username, 'id' => $adminId]);
            $this->logs->record(
                $adminId,
                'admin_profile_updated',
                'admin',
                $adminId,
                ['username' => $admin['username']],
                ['username' => $username]
            );
            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception->getCode() === '23000') {
                setFlash('danger', 'Username tersebut sudah digunakan.');
                redirect($this->url('admin/profile'));
            }

            throw $exception;
        }

        $_SESSION['admin_username'] = $username;
        setFlash('success', 'Username berhasil diperbarui.');
        redirect($this->url('admin/profile'));
    }

    public function updatePassword(): never
    {
        $adminId = (int) $_SESSION['admin_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        $currentPassword = is_string($currentPassword) ? $currentPassword : '';
        $newPassword = is_string($newPassword) ? $newPassword : '';
        $confirmation = is_string($confirmation) ? $confirmation : '';

        if (strlen($newPassword) < 12) {
            setFlash('danger', 'Password baru harus terdiri dari minimal 12 karakter.');
            redirect($this->url('admin/password'));
        }

        if (!hash_equals($newPassword, $confirmation)) {
            setFlash('danger', 'Konfirmasi password baru tidak cocok.');
            redirect($this->url('admin/password'));
        }

        $admin = $this->adminById($adminId);
        if (!password_verify($currentPassword, $admin['password_hash'])) {
            setFlash('danger', 'Password saat ini tidak sesuai.');
            redirect($this->url('admin/password'));
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Password tidak dapat diproses.');
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE admins SET password_hash = :password_hash WHERE id = :id'
            );
            $statement->execute(['password_hash' => $passwordHash, 'id' => $adminId]);
            $this->logs->record(
                $adminId,
                'admin_password_updated',
                'admin',
                $adminId,
                ['password_changed' => false],
                ['password_changed' => true]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        setFlash('success', 'Password berhasil diperbarui.');
        redirect($this->url('admin/password'));
    }

    public function currentAdmin(int $adminId): array
    {
        return $this->adminById($adminId);
    }

    private function adminById(int $adminId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, password_hash FROM admins WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $adminId]);
        $admin = $statement->fetch();
        if ($admin === false) {
            throw new RuntimeException('Akun admin pada sesi tidak ditemukan.');
        }

        return $admin;
    }

    private function activeLock(string $username, string $ipAddress): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT locked_until
             FROM admin_login_attempts
             WHERE username = :username
               AND ip_address = :ip_address
               AND locked_until > NOW()
             ORDER BY locked_until DESC
             LIMIT 1'
        );
        $statement->execute(['username' => $username, 'ip_address' => $ipAddress]);
        $lockedUntil = $statement->fetchColumn();

        return $lockedUntil === false ? null : (string) $lockedUntil;
    }

    private function acquireAttemptLock(string $username, string $ipAddress): bool
    {
        $lockName = 'cozycafe_login_' . substr(
            hash('sha256', strtolower($username) . '|' . $ipAddress),
            0,
            48
        );
        $statement = $this->pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
        $statement->execute(['lock_name' => $lockName]);

        return (int) $statement->fetchColumn() === 1;
    }

    private function releaseAttemptLock(string $username, string $ipAddress): void
    {
        $lockName = 'cozycafe_login_' . substr(
            hash('sha256', strtolower($username) . '|' . $ipAddress),
            0,
            48
        );
        $statement = $this->pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $statement->execute(['lock_name' => $lockName]);
    }

    private function recordSuccessfulLogin(int $adminId, string $username, string $ipAddress): void
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO admin_login_attempts (username, ip_address, success)
                 VALUES (:username, :ip_address, 1)'
            );
            $statement->execute(['username' => $username, 'ip_address' => $ipAddress]);
            $this->logs->record(
                $adminId,
                'admin_login',
                'admin',
                $adminId,
                null,
                ['username' => $username, 'ip_address' => $ipAddress]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function recordFailedLogin(string $username, string $ipAddress): array
    {
        $latestSuccess = $this->pdo->prepare(
            'SELECT MAX(attempted_at)
             FROM admin_login_attempts
             WHERE username = :username AND ip_address = :ip_address AND success = 1'
        );
        $latestSuccess->execute(['username' => $username, 'ip_address' => $ipAddress]);
        $lastSuccessAt = $latestSuccess->fetchColumn();

        $count = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM admin_login_attempts
             WHERE username = :username
               AND ip_address = :ip_address
               AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
               AND attempted_at > :last_success_at'
        );
        $count->execute([
            'username' => $username,
            'ip_address' => $ipAddress,
            'last_success_at' => $lastSuccessAt ?: '1970-01-01 00:00:00',
        ]);
        $failedAttempts = (int) $count->fetchColumn() + 1;
        $lockedUntil = null;
        if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS) {
            $lockedUntil = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))
                ->modify('+' . self::LOCK_MINUTES . ' minutes')
                ->format('Y-m-d H:i:s');
        }

        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO admin_login_attempts (username, ip_address, success, locked_until)
                 VALUES (:username, :ip_address, 0, :locked_until)'
            );
            $insert->execute([
                'username' => $username,
                'ip_address' => $ipAddress,
                'locked_until' => $lockedUntil,
            ]);
            $this->logs->record(
                null,
                $lockedUntil === null ? 'admin_login_failed' : 'admin_login_locked',
                'admin',
                null,
                null,
                [
                    'username' => $username,
                    'ip_address' => $ipAddress,
                    'failed_attempts' => $failedAttempts,
                    'locked_until' => $lockedUntil,
                ]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return [$failedAttempts, $lockedUntil];
    }

    private function url(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
