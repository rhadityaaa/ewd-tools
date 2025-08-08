<?php

namespace App\Enum;

enum WorkflowStep: int
{
    case UNIT_BISNIS = 1;
    case RISK_ANALYST = 2;
    case KADEPT_BISNIS = 3;
    case KADEPT_RISK = 4;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::UNIT_BISNIS->value => 'Unit Bisnis',
            self::RISK_ANALYST->value => 'Risk Analyst',
            self::KADEPT_BISNIS->value => 'Kepala Departemen Bisnis',
            self::KADEPT_RISK->value => 'Kepala Departemen Risk',
        ];
    }

    public function getNextStep(): ?self
    {
        return match($this) {
            self::UNIT_BISNIS => self::RISK_ANALYST,
            self::RISK_ANALYST => self::KADEPT_BISNIS,
            self::KADEPT_BISNIS => self::KADEPT_RISK,
            self::KADEPT_RISK => null,
        };
    }

    public function getPreviousStep(): ?self
    {
        return match($this) {
            self::UNIT_BISNIS => null,
            self::RISK_ANALYST => self::UNIT_BISNIS,
            self::KADEPT_BISNIS => self::RISK_ANALYST,
            self::KADEPT_RISK => self::KADEPT_BISNIS,
        };
    }

    public function canOverride(): bool
    {
        return in_array($this, [self::KADEPT_BISNIS, self::KADEPT_RISK]);
    }

    public function getRequiredRole(): string
    {
        return match($this) {
            self::UNIT_BISNIS => 'unit_bisnis',
            self::RISK_ANALYST => 'risk_analyst',
            self::KADEPT_BISNIS => 'kadept_bisnis',
            self::KADEPT_RISK => 'kadept_risk',
        };
    }
}
