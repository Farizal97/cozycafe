<?php

declare(strict_types=1);

final class AuthMiddleware
{
    private const IDLE_TIMEOUT_SECONDS = 1800;

    public static function requireAdmin(string $loginUrl = '/admin/login'): void
    {
        $now = time();
        $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);

        if (isset($_SESSION['admin_id']) && $lastActivity > 0
            && ($now - $lastActivity) > self::IDLE_TIMEOUT_SECONDS) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            }
            session_destroy();
            redirect($loginUrl);
        }

        if (empty($_SESSION['admin_id'])) {
            redirect($loginUrl);
        }

        $_SESSION['admin_last_activity'] = $now;
    }
}
