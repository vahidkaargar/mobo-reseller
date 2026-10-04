<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TransactionTypeEnum: string
{
    case withdraw = 'withdraw';
    case deposit = 'deposit';
    case lock = 'lock';
    case unlock = 'unlock';
    case credit_grant = 'credit_grant';
    case credit_revoke = 'credit_revoke';
    case credit_repay = 'credit_repay';
    case interest_charge = 'interest_charge';

    public function color(): string
    {
        return match ($this) {
            self::withdraw => 'red',
            self::deposit => 'green',
            default => ''
        };
    }

    public function headline(): string
    {
        return Str::headline($this->value);
    }
}
