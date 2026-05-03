<?php

namespace App\Modules\Order\Support;

final class OrderStatus
{
    public const PENDING = 'pending';
    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const CONFIRMED = 'confirmed';
    public const PROCESSING = 'processing';
    public const PACKED = 'packed';
    public const SHIPPED = 'shipped';
    public const DELIVERED = 'delivered';
    public const FAILED_DELIVERY = 'failed_delivery';
    public const RETURN_REQUESTED = 'return_requested';
    public const RETURNED = 'returned';
    public const REFUNDED = 'refunded';
    public const CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::PENDING,
            self::AWAITING_CONFIRMATION,
            self::CONFIRMED,
            self::PROCESSING,
            self::PACKED,
            self::SHIPPED,
            self::DELIVERED,
            self::FAILED_DELIVERY,
            self::RETURN_REQUESTED,
            self::RETURNED,
            self::REFUNDED,
            self::CANCELLED,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::PENDING => [self::AWAITING_CONFIRMATION, self::CONFIRMED, self::CANCELLED],
            self::AWAITING_CONFIRMATION => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::PACKED, self::CANCELLED],
            self::PACKED => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED, self::FAILED_DELIVERY, self::RETURNED],
            self::FAILED_DELIVERY => [self::SHIPPED, self::RETURNED, self::CANCELLED],
            self::DELIVERED => [self::RETURN_REQUESTED],
            self::RETURN_REQUESTED => [self::RETURNED, self::DELIVERED],
            self::RETURNED => [self::REFUNDED],
            self::REFUNDED => [],
            self::CANCELLED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }

    public static function timestampColumn(string $status): ?string
    {
        return match ($status) {
            self::CONFIRMED => 'confirmed_at',
            self::PROCESSING => 'processing_at',
            self::PACKED => 'packed_at',
            self::SHIPPED => 'shipped_at',
            self::DELIVERED => 'delivered_at',
            self::FAILED_DELIVERY => 'failed_delivery_at',
            self::RETURN_REQUESTED => 'return_requested_at',
            self::RETURNED => 'returned_at',
            self::REFUNDED => 'refunded_at',
            self::CANCELLED => 'cancelled_at',
            default => null,
        };
    }

    public static function fulfillmentStatus(string $status): string
    {
        return match ($status) {
            self::PENDING,
            self::AWAITING_CONFIRMATION,
            self::CONFIRMED => 'unfulfilled',
            self::PROCESSING => 'processing',
            self::PACKED => 'packed',
            self::SHIPPED => 'shipped',
            self::DELIVERED => 'delivered',
            self::FAILED_DELIVERY => 'failed_delivery',
            self::RETURN_REQUESTED => 'return_requested',
            self::RETURNED => 'returned',
            self::REFUNDED => 'refunded',
            self::CANCELLED => 'cancelled',
            default => 'unfulfilled',
        };
    }
}
