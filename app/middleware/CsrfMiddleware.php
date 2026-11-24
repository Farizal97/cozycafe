<?php

declare(strict_types=1);

final class CsrfMiddleware
{
    public static function requireValidToken(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!csrfValidate(is_string($token) ? $token : null)) {
            http_response_code(403);
            echo 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
            exit;
        }
    }
}
