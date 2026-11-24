<?php

declare(strict_types=1);

function formatRupiah(int|float|string $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function formatDateTimeWib(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $date = new DateTimeImmutable($value, new DateTimeZone('Asia/Jakarta'));
    return $date->format('d/m/Y H:i') . ' WIB';
}
