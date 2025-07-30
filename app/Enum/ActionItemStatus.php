<?php

namespace App\Enum;

enum ActionItemStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Menunggu',
            self::IN_PROGRESS->value => 'Sedang Dikerjakan',
            self::COMPLETED->value => 'Selesai',
            self::OVERDUE->value => 'Terlambat',
        ];
    }
}