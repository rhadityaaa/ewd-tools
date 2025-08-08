<?php

namespace App\Enum;

enum ApprovalAction: int
{
    case SUBMIT = 1;
    case APPROVE = 2;
    case REJECT = 3;
    case OVERRIDE = 4;
    case REVISION = 5;
    case WITHDRAW = 6;
    case REASSIGN = 7;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::SUBMIT->value => 'submit',
            self::APPROVE->value => 'approve',
            self::REJECT->value => 'reject',
            self::OVERRIDE->value => 'override',
            self::REVISION->value => 'revision',
            self::WITHDRAW->value => 'withdraw',
            self::REASSIGN->value => 'reassign',
        ];
    }

    public function isPositiveAction(): bool
    {
        return in_array($this, [self::SUBMIT, self::APPROVE, self::OVERRIDE]);
    }

    public function isNegativeAction(): bool
    {
        return in_array($this, [self::REJECT, self::REVISION, self::WITHDRAW]);
    }

    public function requiresComment(): bool
    {
        return in_array($this, [self::REJECT, self::REVISION, self::OVERRIDE]);
    }

    public function getColor(): string
    {
        return match($this) {
            self::SUBMIT => 'blue',
            self::APPROVE => 'green',
            self::REJECT => 'red',
            self::OVERRIDE => 'orange',
            self::REVISION => 'yellow',
            self::WITHDRAW => 'gray',
            self::REASSIGN => 'purple',
        };
    }
}