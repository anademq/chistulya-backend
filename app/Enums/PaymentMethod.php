<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case DEFAULT = 'default';

    public function title(): string
    {
        return match ($this) {
            self::DEFAULT => 'Default',
        };
    }
}
