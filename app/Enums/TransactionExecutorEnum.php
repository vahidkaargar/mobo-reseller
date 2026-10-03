<?php

namespace App\Enums;

enum TransactionExecutorEnum: string
{
    case USER = 'User';
    case API = 'API';

    public function color(): string
    {
        return match ($this) {
            self::USER => 'cyan',
            self::API => 'teal',
        };
    }
}
