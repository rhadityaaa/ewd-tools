<?php

namespace App\Services;

use App\Models\Period;
use App\Enum\Status;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        if (isset($data['status']) && Status::from($data['status']) !== $period->status) {
            $newStatus = Status::from($data['status']);

            match ($newStatus) {
                Status::ACTIVE  => $this->markAsActive($period),
                Status::DRAFT   => $this->markAsDraft($period),
                Status::ENDED   => $this->markAsEnded($period),
                Status::EXPIRED => $this->markAsExpired($period),
            };
        }

        $updateData = $data;
        unset($updateData['status']);

        if (isset($updateData['start_date'])) {
            $updateData['start_date'] = $this->combineDateTime($updateData['start_date'], $updateData['start_time'] ?? '00:00:00');
        }
        if (isset($updateData['end_date'])) {
            $updateData['end_date'] = $this->combineDateTime($updateData['end_date'], $updateData['end_time'] ?? '23:59:59');
        }

        unset($updateData['start_time']);
        unset($updateData['end_time']);

         if (!empty($updateData)) {
        $period->update($updateData);
    }   

        return $period->fresh();
    }

    public function deletePeriod(Period $period): void
    {
        $period->delete();
    }

    public function markAsActive(Period $period): void
    {
        $activePeriodExists = Period::where('status', Status::ACTIVE)
                                    ->where('id', '!=', $period->id)
                                    ->exists();

        if ($activePeriodExists) {
            throw ValidationException::withMessages([
                'error' => 'Tidak bisa memulai periode baru. Harap selesaikan periode yang sedang aktif terlebih dahulu.',
            ]);
        }
        
        $period->status = Status::ACTIVE;
        $period->save();

        Cache::forget('active_period');
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

    public function checkAndMarkExpiredPeriods(): int
    {
        $expiredPeriods = Period::where('status', Status::ACTIVE)
            ->where('end_date', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredPeriods as $period) {
            $this->markAsExpired($period);
            $count++;
            Log::info("Period '{$period->name}' (ID: {$period->id}) automatically marked as expired");
        }

        return $count;
    }

    public function combineDateTime(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}");
    }

    public function getActivePeriod()
    {
        return Period::where('status', Status::ACTIVE)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->select(['id', 'name', 'start_date', 'end_date'])
            ->first();
    }
}