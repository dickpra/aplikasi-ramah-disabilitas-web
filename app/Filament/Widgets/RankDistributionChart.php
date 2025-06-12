<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Location;
use Illuminate\Support\Arr;

class RankDistributionChart extends ChartWidget
{
    // protected static ?string $heading = null;

    public function getHeading(): string
    {
        return __('Distribusi Peringkat Lokasi');
    }


    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $assessedLocations = Location::whereHas('assignments', function ($query) {
            $query->where('status', 'approved');
        })->get();

        // Hitung jumlah untuk setiap peringkat
        $rankCounts = $assessedLocations
            ->map(fn ($location) => $location->final_assessment['rank'] ?? 'N/A')
            ->countBy();

        $allRanks = ['DIAMOND', 'GOLD', 'SILVER', 'BRONZE', 'PARTICIPANT', 'N/A'];
        $data = [];
        $labels = [];

        foreach ($allRanks as $rank) {
            $labels[] = $rank;
            $data[] = $rankCounts->get($rank, 0); // Ambil count, jika tidak ada, defaultnya 0
        }

        return [
            'datasets' => [
                [
                    'label' => __('Jumlah Lokasi'),
                    'data' => $data,
                    'backgroundColor' => [ // Warna untuk setiap bar
                        'rgba(54, 162, 235, 0.5)', // DIAMOND (biru)
                        'rgba(75, 192, 192, 0.5)', // GOLD (hijau)
                        'rgba(255, 206, 86, 0.5)', // SILVER (kuning)
                        'rgba(201, 203, 207, 0.5)', // BRONZE (abu-abu)
                        'rgba(255, 99, 132, 0.5)',  // PARTICIPANT (merah)
                        'rgba(153, 102, 255, 0.5)',// N/A (ungu)
                    ],
                    'borderColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(75, 192, 192)',
                        'rgb(255, 206, 86)',
                        'rgb(201, 203, 207)',
                        'rgb(255, 99, 132)',
                        'rgb(153, 102, 255)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // atau 'pie', 'doughnut', 'line'
    }
}