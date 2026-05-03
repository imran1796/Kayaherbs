<?php

namespace App\Modules\Payment\Support;

final class PaymentStatus
{
    public const UNPAID = 'unpaid';
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';
    public const PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::UNPAID,
            self::PENDING,
            self::PAID,
            self::FAILED,
            self::REFUNDED,
            self::PARTIALLY_REFUNDED,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::UNPAID => [self::PENDING, self::PAID],
            self::PENDING => [self::PAID, self::FAILED, self::UNPAID],
            self::FAILED => [self::PENDING],
            self::PAID => [self::PARTIALLY_REFUNDED, self::REFUNDED],
            self::PARTIALLY_REFUNDED => [self::REFUNDED],
            self::REFUNDED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }
}
