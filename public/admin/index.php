<?php

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/admin';
$_SERVER['REQUEST_URI'] = str_contains($requestUri, '/admin')
    ? $requestUri
    : '/admin';

require dirname(__DIR__) . '/index.php';
