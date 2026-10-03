<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case CREATED = 'CREATED';
    case PROCESSING = 'PROCESSING';
    case SUCCEEDED = 'SUCCEEDED';
    case FAILED = 'FAILED';
    case PARTIAL_FAILED = 'PARTIAL_FAILED';

    public function color(): string
    {
        return match ($this) {
            self::CREATED => 'cyan',
            self::PROCESSING => 'sky',
            self::SUCCEEDED => 'green',
            self::FAILED => 'red',
            self::PARTIAL_FAILED => 'amber',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::PROCESSING => 'Processing',
            self::SUCCEEDED => 'Succeed',
            self::FAILED => 'Failed',
            self::PARTIAL_FAILED => 'Partial Failed',
        };
    }
}
