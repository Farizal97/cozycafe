<?php

declare(strict_types=1);

function formatRupiah(int|float|string $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function formatRupiahCents(int $cents): string
{
    $formatted = 'Rp' . number_format(intdiv($cents, 100), 0, ',', '.');
    $fraction = $cents % 100;

    return $fraction === 0 ? $formatted : $formatted . ',' . str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
}

function formatDateTimeWib(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $date = new DateTimeImmutable($value, new DateTimeZone('Asia/Jakarta'));
    return $date->format('d/m/Y H:i') . ' WIB';
}
