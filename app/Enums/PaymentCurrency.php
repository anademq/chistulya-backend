<?php

namespace App\Enums;

enum PaymentCurrency: string
{
    case RUB = 'rub';

    public function toISO4217(): string
    {
        return match ($this) {
            self::RUB => 'RUB',
        };
    }
}
