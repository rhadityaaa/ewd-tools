<?php

namespace App\Enum;

enum ReportStatus: int
{
    case DRAFT = 0;
    case SUBMITTED = 1;
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
            self::DRAFT->value => 'draft',
            self::SUBMITTED->value => 'submitted',
            self::APPROVED->value => 'approved',
            self::REJECTED->value => 'rejected',
            self::UNDER_REVIEW->value => 'under_review',
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    public function isInApprovalProcess(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function isFinal(): bool
    {
        return $this === self::APPROVED;
    }

    public function needsRevision(): bool
    {
        return $this === self::REJECTED;
    }
}
