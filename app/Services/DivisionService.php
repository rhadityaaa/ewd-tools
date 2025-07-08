<?php

namespace App\Services;

use App\Models\Division;
use Illuminate\Database\Eloquent\Collection;

class DivisionService
{
    public function getAllDivisions(): Collection
    {
        $divisions = Division::all();

        return $divisions;
    }

    public function getDivisionById(int $id): Division
    {
        $division = Division::findOrFail($id);

        return $division;
    }

    public function createDivision(array $data): Division
    {
        $division = Division::create($data);

        return $division;
    }

    public function updateDivision(Division $division, array $data): Division
    {
        $division->update($data);

        return $division;
    }

    public function deleteDivision(Division $division): void
    {
        $division->delete();
    }
}