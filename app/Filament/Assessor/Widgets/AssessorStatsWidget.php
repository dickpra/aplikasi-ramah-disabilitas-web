<?php

namespace App\Filament\Assessor\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;

class AssessorStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $assessorId = Auth::guard('assessor')->id();

        // Hitung tugas yang masih aktif (baru atau sedang dikerjakan)
        $activeAssignments = Assignment::where('assessor_id', $assessorId)
                                        ->whereIn('status', ['assigned', 'in_progress'])
                                        ->count();

        // Hitung tugas yang perlu direvisi
        $revisionNeededAssignments = Assignment::where('assessor_id', $assessorId)
                                                ->where('status', 'revision_needed')
                                                ->count();

        // Hitung tugas yang sudah selesai (termasuk yang sudah disetujui)
        $completedAssignments = Assignment::where('assessor_id', $assessorId)
                                            ->whereIn('status', ['completed', 'approved'])
                                            ->count();

        return [
            Stat::make('Tugas Aktif', $activeAssignments)
                ->description('Tugas baru atau yang sedang dikerjakan')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Perlu Revisi', $revisionNeededAssignments)
                ->description('Tugas yang dikembalikan oleh admin untuk diperbaiki')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),
            Stat::make('Tugas Selesai', $completedAssignments)
                ->description('Total tugas yang telah Anda selesaikan')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}