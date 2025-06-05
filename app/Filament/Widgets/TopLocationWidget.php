<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Location;

class TopLocationWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Ambil semua lokasi yang penilaiannya sudah disetujui (approved)
        $assessedLocations = Location::whereHas('assignments', function ($query) {
            $query->where('status', 'approved');
        })->get();

        // 2. Urutkan lokasi berdasarkan skor akhir (dari accessor) secara descending, lalu ambil yang pertama
        $topLocation = $assessedLocations->sortByDesc(function ($location) {
            // Gunakan accessor final_assessment untuk mendapatkan skor
            return $location->final_assessment['final_score'] ?? -1;
        })->first();

        // 3. Buat kartu statistik berdasarkan data lokasi teratas
        if ($topLocation && isset($topLocation->final_assessment)) {
            $scoreData = $topLocation->final_assessment;
            return [
                Stat::make('Peringkat Tertinggi', $topLocation->name)
                    ->description("Skor: {$scoreData['final_score']} ({$scoreData['rank']})")
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('success'),
            ];
        }

        // Tampilkan pesan default jika tidak ada data
        return [
            Stat::make('Peringkat Tertinggi', 'N/A')
                ->description('Belum ada lokasi yang penilaiannya disetujui.')
                ->color('gray'),
        ];
    }
}