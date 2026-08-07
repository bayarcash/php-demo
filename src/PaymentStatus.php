<?php

declare(strict_types=1);

namespace BayarcashDemo;

/**
 * Bayarcash transaction status codes.
 */
final class PaymentStatus
{
    public const NEW          = 0;
    public const PENDING      = 1;
    public const UNSUCCESSFUL = 2;
    public const SUCCESSFUL   = 3;
    public const CANCELLED    = 4;
    public const EXPIRED      = 5;

    public const LABELS = [
        self::NEW          => 'New',
        self::PENDING      => 'Pending',
        self::UNSUCCESSFUL => 'Unsuccessful',
        self::SUCCESSFUL   => 'Successful',
        self::CANCELLED    => 'Cancelled',
        self::EXPIRED      => 'Expired',
    ];

    /**
     * Statuses a transaction never leaves. Callbacks can be retried or arrive
     * out of order, so this is what stops a late one re-opening a settled
     * payment. Used by TransactionRepository::recordCallback().
     */
    public const TERMINAL = [
        self::UNSUCCESSFUL,
        self::SUCCESSFUL,
        self::CANCELLED,
        self::EXPIRED,
    ];

    public static function label(int|string|null $status): string
    {
        if ($status === null || $status === '') {
            return 'Unknown';
        }

        return self::LABELS[(int) $status] ?? 'Unknown';
    }

    public static function isTerminal(int|string|null $status): bool
    {
        if ($status === null || $status === '') {
            return false;
        }

        return in_array((int) $status, self::TERMINAL, true);
    }

    public static function isPaid(int|string|null $status): bool
    {
        return $status !== null && $status !== '' && (int) $status === self::SUCCESSFUL;
    }

    public static function tone(int|string|null $status): string
    {
        return match ((int) ($status ?? -1)) {
            self::SUCCESSFUL               => 'success',
            self::PENDING, self::NEW       => 'warning',
            self::UNSUCCESSFUL             => 'danger',
            self::CANCELLED, self::EXPIRED => 'secondary',
            default                        => 'light',
        };
    }
}
