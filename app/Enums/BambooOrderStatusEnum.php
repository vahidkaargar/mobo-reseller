<?php

namespace App\Enums;

enum BambooOrderStatusEnum: string
{
    case CREATED = 'Created';
    case PROCESSED = 'Processed';
    case PENDING = 'Pending';
    case SUCCEEDED = 'Succeeded';
    case FAILED = 'Failed';
    case PARTIAL_FAILED = 'PartialFailed';

    public function color(): string
    {
        return match ($this) {
            self::CREATED => 'cyan',
            self::PROCESSED => 'sky',
            self::PENDING => 'teal',
            self::SUCCEEDED => 'green',
            self::FAILED => 'red',
            self::PARTIAL_FAILED => 'amber',
        };
    }
}
