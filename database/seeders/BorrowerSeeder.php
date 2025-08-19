<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Borrower;
use Carbon\Carbon;

class BorrowerSeeder extends Seeder
{
    public function run(): void
    {
        $borrowers = [
            // Corporate borrowers
            ['name' => 'PT Astra International Tbk', 'division_id' => 1, 'status' => 'active'],
            ['name' => 'PT Unilever Indonesia Tbk', 'division_id' => 1, 'status' => 'active'],
            ['name' => 'PT Telkom Indonesia Tbk', 'division_id' => 1, 'status' => 'active'],
            ['name' => 'PT Bank Central Asia Tbk', 'division_id' => 1, 'status' => 'active'],
            ['name' => 'PT Gudang Garam Tbk', 'division_id' => 1, 'status' => 'active'],
            
            // Commercial borrowers
            ['name' => 'PT Sinar Mas Agro Resources', 'division_id' => 2, 'status' => 'active'],
            ['name' => 'PT Indofood Sukses Makmur', 'division_id' => 2, 'status' => 'active'],
            ['name' => 'PT Mayora Indah Tbk', 'division_id' => 2, 'status' => 'active'],
            ['name' => 'PT Kalbe Farma Tbk', 'division_id' => 2, 'status' => 'active'],
            ['name' => 'PT Sido Muncul', 'division_id' => 2, 'status' => 'active'],
            
            // SME borrowers
            ['name' => 'CV Berkah Jaya Mandiri', 'division_id' => 3, 'status' => 'active'],
            ['name' => 'PT Maju Bersama Sejahtera', 'division_id' => 3, 'status' => 'active'],
            ['name' => 'CV Sumber Rejeki', 'division_id' => 3, 'status' => 'active'],
            ['name' => 'PT Karya Utama Sentosa', 'division_id' => 3, 'status' => 'active'],
            ['name' => 'CV Mitra Usaha Bersama', 'division_id' => 3, 'status' => 'active'],
            
            // Consumer borrowers
            ['name' => 'Budi Santoso', 'division_id' => 4, 'status' => 'active'],
            ['name' => 'Siti Nurhaliza', 'division_id' => 4, 'status' => 'active'],
            ['name' => 'Ahmad Wijaya', 'division_id' => 4, 'status' => 'active'],
            ['name' => 'Dewi Sartika', 'division_id' => 4, 'status' => 'active'],
            ['name' => 'Rudi Hermawan', 'division_id' => 4, 'status' => 'active'],
            
            // Additional borrowers
            ['name' => 'PT Surya Gemilang', 'division_id' => 5, 'status' => 'active'],
            ['name' => 'CV Bumi Persada', 'division_id' => 6, 'status' => 'active'],
            ['name' => 'PT Mitra Sejati', 'division_id' => 7, 'status' => 'active'],
            ['name' => 'PT Cipta Karya', 'division_id' => 8, 'status' => 'active'],
            ['name' => 'PT Anugerah Mulia', 'division_id' => 9, 'status' => 'active'],
            
            // Some inactive borrowers for testing
            ['name' => 'PT Bangkrut Sejahtera', 'division_id' => 2, 'status' => 'inactive', 'inactive_reason' => 'written_off', 'inactive_date' => '2023-12-31'],
            ['name' => 'CV Lunas Mandiri', 'division_id' => 3, 'status' => 'inactive', 'inactive_reason' => 'paid_off', 'inactive_date' => '2024-06-30'],
        ];
        
        // Add more borrowers for performance testing
        for ($i = 1; $i <= 100; $i++) {
            $borrowers[] = [
                'name' => 'PT Test Company ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'division_id' => (($i - 1) % 10) + 1,
                'status' => $i % 20 == 0 ? 'inactive' : 'active',
                'inactive_reason' => $i % 20 == 0 ? ($i % 40 == 0 ? 'written_off' : 'paid_off') : null,
                'inactive_date' => $i % 20 == 0 ? Carbon::now()->subDays(rand(30, 365))->format('Y-m-d') : null,
            ];
        }
        
        foreach ($borrowers as $borrower) {
            Borrower::firstOrCreate(
                ['name' => $borrower['name']],
                $borrower
            );
        }
    }
}
