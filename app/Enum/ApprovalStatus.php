<?php

namespace App\Enum;

enum ApprovalStatus: int
{
    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
    case UNDER_REVIEW = 5;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'pending',
            self::APPROVED->value => 'approved',
            self::REJECTED->value => 'rejected',
            self::UNDER_REVIEW->value => 'under_review'
        ];
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;

    }
}