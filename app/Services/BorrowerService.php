<?php

namespace App\Services;

use App\Models\Borrower;
use Illuminate\Database\Eloquent\Collection;

class BorrowerService
{
    public function getAllBorrowers(): Collection
    {
        $borrowers = Borrower::with('division')->get();

        return $borrowers;
    }

    public function getBorrowerById(int $id): Borrower
    {
        $borrower = Borrower::with('division')->findOrFail($id);

        return $borrower;
    }

    public function createBorrower(array $data): Borrower
    {
        $borrower = Borrower::create($data);

        return $borrower;
    }

    public function updateBorrower(Borrower $borrower, array $data): Borrower
    {
        $borrower->update($data);

        return $borrower;
    }

    public function deleteBorrower(Borrower $borrower): void
    {
        $borrower->delete();
    }
}