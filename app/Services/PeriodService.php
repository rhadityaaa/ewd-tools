<?php

namespace App\Services;

use App\Models\Period;
use App\Enum\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PeriodService
{
    public function getAllPeriods(): Collection
    {
        $periods = Period::with('createdBy')->orderBy('start_date', 'desc')->get();

        return $periods;
    }

    public function getPeriodById(int $id): Period
    {
        $period = Period::findOrFail($id);

        return $period;
    }

    public function createPeriod(array $data): Period
    {
        $startDate = $this->combineDateTime($data['start_date'], $data['start_time'] ?? '00:00:00');
        $endDate = isset($data['end_date']) ? $this->combineDateTime($data['end_date'], $data['end_time'] ?? '23:59:59') : null;

        $period = Period::create([
            'name' => $data['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_by' => auth()->id(),
            'status' => Status::from($data['status']),
        ]);

        return $period;
    }

    public function updatePeriod(Period $period, array $data): Period
    {
        $startDate = $this->combineDateTime($data['start_date'], $data['start_time'] ?? '00:00:00');
        $endDate = isset($data['end_date']) ? $this->combineDateTime($data['end_date'], $data['end_time'] ?? '23:59:59') : null;

        $period->update([
            'name' => $data['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Status::from($data['status']),
        ]);

        return $period;
    }

    public function deletePeriod(Period $period): void
    {
        $period->delete();
    }

    public function markAsActive(Period $period): void
    {
        $period->status = Status::ACTIVE;
        $period->start_date = now();
        $period->save();
    }

    public function markAsDraft(Period $period): void
    {
        $period->status = Status::DRAFT;
        $period->save();
    }

    public function markAsEnded(Period $period): void
    {
        $period->status = Status::ENDED;
        $period->end_date = now();
        $period->save();
    }

    public function markAsExpired(Period $period): void
    {
        $period->status = Status::EXPIRED;
        $period->save();
    }

    public function isActive(Period $period): bool
    {
        return $period->status === Status::ACTIVE;
    }

    public function isExpired(Period $period): bool
    {
        return $period->end_date && now()->gt($period->end_date) && $period->status === Status::ACTIVE;
    }

    public function getRemainingTime(Period $period): ?string
    {
        if (!$period->end_date) {
            return null;
        }

        $diff = now()->diff($period->end_date);

        return $diff->format('%a hari, %h jam, %i menit');
    }

    public function extendPeriod(Period $period, string $newEndDate): Period
    {
        $period->end_date = Carbon::parse($newEndDate);
        $period->save();

        return $period;
    }

    public function checkAndMarkExpiredPeriods(): void
    {
        $periods = Period::where('status', Status::ACTIVE)->get();

        foreach ($periods as $period) {
            if ($period->end_date && $period->end_date->lt(now())) {
                $this->markAsExpired($period);
            }
        }
    }

    public function combineDateTime(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}");
    }
}