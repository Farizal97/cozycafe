<?php

declare(strict_types=1);

function redirect(string $location, int $status = 302): never
{
    http_response_code($status);
    header('Location: ' . $location);
    exit;
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
