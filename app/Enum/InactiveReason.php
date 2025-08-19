<?php

namespace App\Enum;

enum InactiveReason: string
{
    case PAID_OFF = 'paid_off';
    case WRITTEN_OFF = 'written_off';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PAID_OFF->value => 'Lunas',
            self::WRITTEN_OFF->value => 'Write Off',
        ];
    }
}