<?php

namespace App\Filament\Assessor\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Assessor\Widgets\AssessorStatsWidget;
use App\Filament\Assessor\Widgets\UrgentAssignmentsWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AssessorStatsWidget::class,
            UrgentAssignmentsWidget::class,
        ];
    }
}