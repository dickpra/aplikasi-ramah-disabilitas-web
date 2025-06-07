<?php

namespace App\Filament\Resources\AssessorResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AssessorOverview extends BaseWidget
{
    // Properti ini akan secara otomatis diisi dengan record Asesor yang sedang dilihat
    public ?Model $record = null;

    protected function getStats(): array
    {
        // Jangan tampilkan apapun jika tidak ada record (misal di halaman list)
        if (!$this->record) {
            return [];
        }

        return [
            Stat::make('Tugas Aktif', $this->record->assignments()
                ->whereIn('status', ['assigned', 'in_progress', 'revision_needed'])
                ->count())
                ->description('Tugas yang sedang atau perlu dikerjakan')
                ->color('warning'),

            Stat::make('Tugas Selesai (Disetujui)', $this->record->assignments()
                ->where('status', 'approved')
                ->count())
                ->description('Jumlah penilaian yang sudah final dan disetujui')
                ->color('success'),

            Stat::make('Total Semua Tugas', $this->record->assignments()->count())
                ->description('Total tugas yang pernah diberikan kepada asesor ini')
                ->color('info'),
        ];
    }
}