<?php

namespace App\Modules\Payment\Support;

final class CodStatus
{
    public const NOT_APPLICABLE = 'not_applicable';
    public const PENDING = 'pending';
    public const COLLECTED = 'collected';
    public const FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::NOT_APPLICABLE,
            self::PENDING,
            self::COLLECTED,
            self::FAILED,
        ];
    }
}
