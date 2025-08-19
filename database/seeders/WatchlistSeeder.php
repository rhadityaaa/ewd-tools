<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Watchlist;
use App\Models\Borrower;
use App\Models\Report;
use App\Models\User;
use App\Enum\WatchlistStatus;
use Carbon\Carbon;

class WatchlistSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data yang diperlukan
        $borrowers = Borrower::all();
        $reports = Report::all();
        $users = User::all();
        
        if ($borrowers->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Tidak ada data borrower atau user. Pastikan seeder lain sudah dijalankan.');
            return;
        }

        // Data watchlist dengan berbagai skenario
        $watchlistData = [
            // Watchlist aktif - debitur bermasalah
            [
                'borrower_name' => 'PT Bangkrut Sejahtera',
                'status' => WatchlistStatus::ACTIVE->value,
                'reason' => 'Kolektibilitas menurun drastis dalam 3 bulan terakhir',
                'has_report' => true,
            ],
            [
                'borrower_name' => 'CV Bermasalah Utama',
                'status' => WatchlistStatus::ACTIVE->value,
                'reason' => 'Tunggakan pembayaran pokok dan bunga lebih dari 90 hari',
                'has_report' => true,
            ],
            [
                'borrower_name' => 'PT Krisis Likuiditas',
                'status' => WatchlistStatus::ACTIVE->value,
                'reason' => 'Rasio likuiditas di bawah standar minimum bank',
                'has_report' => false,
            ],
            
            // Watchlist yang sudah diselesaikan
            [
                'borrower_name' => 'PT Pulih Kembali',
                'status' => WatchlistStatus::RESOLVED->value,
                'reason' => 'Restrukturisasi kredit berhasil dilakukan',
                'has_report' => true,
                'resolved_notes' => 'Debitur telah melakukan pembayaran sesuai jadwal restrukturisasi. Kondisi keuangan membaik.',
                'resolved_days_ago' => 30,
            ],
            [
                'borrower_name' => 'CV Bangkit Mandiri',
                'status' => WatchlistStatus::RESOLVED->value,
                'reason' => 'Pelunasan dipercepat setelah penjualan aset',
                'has_report' => true,
                'resolved_notes' => 'Debitur melakukan pelunasan dipercepat dari hasil penjualan properti.',
                'resolved_days_ago' => 15,
            ],
            
            // Watchlist yang diarsipkan
            [
                'borrower_name' => 'PT Tutup Usaha',
                'status' => WatchlistStatus::ARCHIVED->value,
                'reason' => 'Perusahaan tutup, kredit di-write off',
                'has_report' => false,
                'resolved_notes' => 'Perusahaan resmi tutup. Kredit telah di-write off sesuai kebijakan bank.',
                'resolved_days_ago' => 90,
            ],
        ];

        foreach ($watchlistData as $data) {
            // Cari borrower berdasarkan nama atau ambil random jika tidak ada
            $borrower = $borrowers->firstWhere('name', 'like', '%' . explode(' ', $data['borrower_name'])[1] . '%')
                     ?? $borrowers->random();
            
            // Ambil report jika diperlukan
            $report = null;
            if ($data['has_report'] && $reports->isNotEmpty()) {
                $report = $reports->where('borrower_id', $borrower->id)->first()
                       ?? $reports->random();
            }
            
            // Tentukan user yang menambahkan (random user)
            $addedBy = $users->random();
            
            // Tentukan resolver jika status bukan active
            $resolvedBy = null;
            $resolvedAt = null;
            $resolverNotes = null;
            
            if ($data['status'] !== WatchlistStatus::ACTIVE->value) {
                $resolvedBy = $users->where('id', '!=', $addedBy->id)->random();
                $resolvedAt = isset($data['resolved_days_ago']) 
                    ? Carbon::now()->subDays($data['resolved_days_ago'])
                    : Carbon::now()->subDays(rand(1, 60));
                $resolverNotes = $data['resolved_notes'] ?? 'Masalah telah diselesaikan sesuai prosedur bank.';
            }
            
            Watchlist::firstOrCreate(
                [
                    'borrower_id' => $borrower->id,
                    'status' => $data['status'],
                ],
                [
                    'report_id' => $report?->id,
                    'added_by' => $addedBy->id,
                    'resolved_by' => $resolvedBy?->id,
                    'resolver_notes' => $resolverNotes,
                    'resolved_at' => $resolvedAt,
                ]
            );
        }
        
        // Tambahkan beberapa watchlist random untuk testing
        $this->createRandomWatchlists($borrowers, $reports, $users, 15);
        
        $this->command->info('Watchlist seeder berhasil dijalankan!');
    }
    
    private function createRandomWatchlists($borrowers, $reports, $users, $count)
    {
        $statuses = WatchlistStatus::values();
        $reasons = [
            'Penurunan kualitas kredit',
            'Keterlambatan pembayaran berulang',
            'Kondisi industri yang memburuk',
            'Masalah manajemen internal',
            'Penurunan omzet signifikan',
            'Rasio keuangan di bawah standar',
            'Gagal memenuhi covenant kredit',
            'Restrukturisasi kredit diperlukan',
            'Masalah likuiditas perusahaan',
            'Penurunan nilai agunan',
        ];
        
        $resolverNotes = [
            'Masalah telah diselesaikan melalui restrukturisasi.',
            'Debitur telah memenuhi kewajiban pembayaran.',
            'Kondisi keuangan debitur membaik.',
            'Agunan tambahan telah diterima.',
            'Pelunasan dipercepat telah dilakukan.',
            'Rencana pemulihan bisnis berhasil diimplementasi.',
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $borrower = $borrowers->random();
            $status = $statuses[array_rand($statuses)];
            $addedBy = $users->random();
            
            // Cek apakah borrower sudah ada di watchlist
            if (Watchlist::where('borrower_id', $borrower->id)->exists()) {
                continue;
            }
            
            $report = null;
            if (rand(1, 3) <= 2 && $reports->isNotEmpty()) { // 66% chance ada report
                $report = $reports->where('borrower_id', $borrower->id)->first()
                       ?? $reports->random();
            }
            
            $resolvedBy = null;
            $resolvedAt = null;
            $resolverNotesText = null;
            
            if ($status !== WatchlistStatus::ACTIVE->value) {
                $resolvedBy = $users->where('id', '!=', $addedBy->id)->random();
                $resolvedAt = Carbon::now()->subDays(rand(1, 180));
                $resolverNotesText = $resolverNotes[array_rand($resolverNotes)];
            }
            
            Watchlist::create([
                'borrower_id' => $borrower->id,
                'report_id' => $report?->id,
                'status' => $status,
                'added_by' => $addedBy->id,
                'resolved_by' => $resolvedBy?->id,
                'resolver_notes' => $resolverNotesText,
                'resolved_at' => $resolvedAt,
            ]);
        }
    }
}