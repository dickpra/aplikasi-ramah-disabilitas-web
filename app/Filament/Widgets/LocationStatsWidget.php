<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Location;
use App\Models\Assignment;
use App\Models\Assessor;

class LocationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Ambil semua lokasi yang memiliki setidaknya satu penugasan yang disetujui
        $assessedLocations = Location::whereHas('assignments', function ($query) {
            $query->where('status', 'approved');
        })->get();
        

        $totalAssessed = $assessedLocations->count();
        $ranks = $assessedLocations->map(fn ($location) => $location->final_assessment['rank'] ?? null)->filter();

        $averageScore = 0;
        if ($totalAssessed > 0) {
            $totalScore = $assessedLocations->sum(fn ($location) => $location->final_assessment['final_score'] ?? 0);
            $averageScore = round($totalScore / $totalAssessed, 2);
        }

        $assessorsCount = Assessor::count();


        return [
            Stat::make('Total Lokasi Dinilai', $totalAssessed)
                ->description('Jumlah lokasi dengan penilaian yang sudah disetujui')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
            Stat::make('Skor Rata-rata Nasional', $averageScore)
                ->description('Rata-rata dari semua skor akhir lokasi')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
            Stat::make('Peringkat GOLD Terbanyak', $ranks->where('rank', '==', 'GOLD')->count())
                ->description('Jumlah lokasi yang mencapai peringkat GOLD')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
            Stat::make('Jumlah Assesor', $assessorsCount)
                ->description('Jumlah Assesor Aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger')
                // ->value(Assessor::count()),
        ];
    }
}