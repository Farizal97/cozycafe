<?php

declare(strict_types=1);

function rateLimitKey(string $username, string $ip): string
{
    return 'login_attempt:' . hash('sha256', strtolower(trim($username)) . '|' . $ip);
}

function loginAttempts(string $username, string $ip): int
{
    return (int) ($_SESSION[rateLimitKey($username, $ip)] ?? 0);
}

function recordLoginAttempt(string $username, string $ip): int
{
    $key = rateLimitKey($username, $ip);
    $_SESSION[$key] = ((int) ($_SESSION[$key] ?? 0)) + 1;
    return (int) $_SESSION[$key];
}

function resetLoginAttempts(string $username, string $ip): void
{
    unset($_SESSION[rateLimitKey($username, $ip)]);
}
