<?php

namespace App\Enum;

enum BorrowerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Aktif',
            self::INACTIVE->value => 'Tidak Aktif',
        ];
    }
}
