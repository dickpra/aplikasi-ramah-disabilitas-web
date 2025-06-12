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

        $semualokasi = Location::count(); // Total lokasi yang ada

        $totalAssessed = $assessedLocations->count();

        $averageScore = 0;
        if ($totalAssessed > 0) {
            $totalScore = $assessedLocations->sum(fn ($location) => $location->final_assessment['final_score'] ?? 0);
            $averageScore = round($totalScore / $totalAssessed, 2);
        }

        // --- Logika baru untuk menghitung penilaian yang belum selesai ---
        $unfinishedAssignments = Assignment::whereIn('status', ['assigned', 'in_progress'])->count();
        
        $assessorsCount = Assessor::count();

        return [
            // Stat::make('Total Semua Lokasi', $semualokasi)
            //     ->description('Jumlah total lokasi yang terdaftar')
            //     ->descriptionIcon('heroicon-m-map-pin')
            //     ->color('primary'),
            Stat::make(__('Total Lokasi Dinilai'), $totalAssessed)
                ->description(__('Jumlah lokasi dengan penilaian yang sudah disetujui'))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
            Stat::make(__('Skor Rata-rata Nasional'), $averageScore)
                ->description(__('Rata-rata dari semua skor akhir lokasi'))
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            // --- KARTU STATISTIK YANG DIGANTI ---
            Stat::make(__('Penilaian Belum Selesai'), $unfinishedAssignments)
                ->description(__('Jumlah tugas yang masih aktif atau sedang dikerjakan'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make(__('Jumlah Asesor'), $assessorsCount)
                ->description(__('Total asesor yang terdaftar'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
        ];
    }
}