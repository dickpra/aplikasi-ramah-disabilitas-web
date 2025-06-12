<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Location;
use Illuminate\Support\Facades\Log; // Import Log untuk debugging

class TopLocationsChartWidget extends ChartWidget
{
    // protected static ?string $heading = '';
    protected static ?int $sort = 3; // Sesuaikan urutan jika perlu

    public function getHeading(): ?string
    {
        return __('5 Lokasi dengan Skor Tertinggi');
    }

    protected function getData(): array
    {
        Log::info('[ChartWidget] Memulai getData untuk TopLocationsChartWidget.');

        // 1. Ambil semua lokasi yang penilaiannya sudah disetujui
        $assessedLocations = Location::whereHas('assignments', function ($query) {
            $query->where('status', 'approved');
        })->get();

        Log::info('[ChartWidget] Jumlah lokasi dengan penilaian approved: ' . $assessedLocations->count());

        // Jika tidak ada data, kembalikan dataset kosong agar tidak error
        if ($assessedLocations->isEmpty()) {
            Log::warning('[ChartWidget] Tidak ada lokasi yang dinilai dan disetujui. Grafik akan kosong.');
            return [
                'datasets' => [['label' => __('Skor Akhir'), 'data' => []]],
                'labels' => [],
            ];
        }

        // 2. Urutkan lokasi berdasarkan skor akhir secara descending, lalu ambil 5 teratas.
        // Kita harus memastikan 'final_assessment' tidak null saat sorting
        $topLocations = $assessedLocations
            ->filter(function ($location) {
                // Hanya proses lokasi yang memiliki hasil kalkulasi skor
                return isset($location->final_assessment['final_score']);
            })
            ->sortByDesc(function ($location) {
                return $location->final_assessment['final_score'];
            })
            ->take(5);

        Log::info('[ChartWidget] Jumlah lokasi setelah disortir dan diambil 5 teratas: ' . $topLocations->count());

        // 3. Siapkan data untuk grafik
        $labels = $topLocations->pluck('name')->all();
        $data = $topLocations->pluck('final_assessment.final_score')->all();

        Log::info('[ChartWidget] Labels untuk grafik:', $labels);
        Log::info('[ChartWidget] Data untuk grafik:', $data);

        return [
            'datasets' => [
                [
                    'label' => __('Skor Akhir'),
                    'data' => $data,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}