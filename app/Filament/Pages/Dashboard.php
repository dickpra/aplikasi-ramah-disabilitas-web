<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\LocationStatsWidget;      // <-- Import widget statistik
use App\Filament\Widgets\LocationRankingWidget;     // <-- Import widget tabel peringkat
use App\Filament\Widgets\RankDistributionChart;     // <-- Import widget grafik
use App\Filament\Widgets\TopLocationWidget;         // <-- Import widget top lokasi
use App\Filament\Widgets\TopLocationsChartWidget;   // <-- Import widget grafik top lokasi
use App\Filament\Widgets\RecentAssignmentsWidget;   // <-- Import widget tabel aktivitas terkini
use App\Models\Location;
use Filament\Notifications\Notification;
use Filament\Widgets\WidgetConfiguration;
use Filament\Actions\Action; // Import Action


class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculateScores')
                ->label('Hitung Ulang Semua Skor & Peringkat')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang Skor')
                ->modalDescription('Proses ini akan menghitung ulang skor akhir dan peringkat untuk semua lokasi yang penilaiannya sudah disetujui. Ini mungkin memakan waktu beberapa saat. Lanjutkan?')
                ->action(function () {
                    // Ambil semua lokasi yang memiliki setidaknya satu penugasan yang 'approved'
                    $locationsToRecalculate = Location::whereHas('assignments', fn($q) => $q->where('status', 'approved'))->get();
                    $count = 0;

                    foreach ($locationsToRecalculate as $location) {
                        // Panggil metode kalkulasi yang sudah kita buat di model Location
                        $result = $location->calculateFinalScore(); 
                        if ($result) {
                            // Simpan hasilnya ke kolom baru di tabel locations
                            $location->final_score = $result['final_score'];
                            $location->rank = $result['rank'];
                            $location->save();
                            $count++;
                        }
                    }

                    // Beri notifikasi ke admin
                    Notification::make()
                        ->success()
                        ->title('Perhitungan Selesai')
                        ->body("{$count} lokasi telah berhasil dihitung ulang skor dan peringkatnya.")
                        ->send();
                }),
        ];
    }
    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            LocationStatsWidget::class,
            TopLocationsChartWidget::class,
            RankDistributionChart::class,
            // TopLocationWidget::class,
            RecentAssignmentsWidget::class,
            LocationRankingWidget::class,
        ];
    }

    /**
     * (Opsional) Mengatur layout kolom untuk widget di halaman dashboard.
     *
     * @return int | string | array
     */
    public function getColumns(): int | string | array
    {
        return 2; // Atau 'full' untuk satu kolom
    }

    /**
     * (Opsional) Mengatur lebar spesifik untuk setiap widget.
     *
     * @return int | string | array
     */
    public function getWidgetsColumns(): int | string | array
    {
        return [
            // Widget statistik memakai lebar penuh di baris pertama
            LocationStatsWidget::class => 'full',


            LocationRankingWidget::class => 1,

            
            // Widget grafik dan tabel bisa bersebelahan jika ada cukup ruang
            RankDistributionChart::class => 1,
            // TopLocationWidget::class => 1,
            TopLocationsChartWidget::class => 1,
            RecentAssignmentsWidget::class => 'full',
        ];
    }
}