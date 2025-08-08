<?php

namespace App\Enum;

enum ReportStatus: int
{
    case DRAFT = 1;
    case SUBMITTED = 2;
    case UNDER_REVIEW = 3;
    case APPROVED = 4;
    case REJECTED = 5;
    case REVISION_REQUIRED = 6;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::DRAFT->value => 'draft',
            self::SUBMITTED->value => 'submitted',
            self::UNDER_REVIEW->value => 'under review',
            self::APPROVED->value => 'approved',
            self::REJECTED->value => 'rejected',
            self::REVISION_REQUIRED->value => 'revision required',
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REVISION_REQUIRED]);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this, [self::DRAFT, self::REVISION_REQUIRED]);
    }

    public function isInApprovalProcess(): bool
    {
        return in_array($this, [self::SUBMITTED, self::UNDER_REVIEW]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED]);
    }
}
