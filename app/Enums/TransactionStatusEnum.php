<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TransactionStatusEnum: string
{
    case pending = 'pending';
    case approved = 'approved';
    case rejected = 'rejected';
    case reversed = 'reversed';

    public function color(): string
    {
        return match ($this) {
            self::pending => '',
            self::approved => 'green',
            self::rejected => 'red',
            self::reversed => 'yellow'
        };
    }

    public function headline(): string
    {
        return Str::headline($this->value);
    }
}
