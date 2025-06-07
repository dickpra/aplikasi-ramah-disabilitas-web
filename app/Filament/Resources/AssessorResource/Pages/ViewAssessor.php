<?php
namespace App\Filament\Resources\AssessorResource\Pages;

use App\Filament\Resources\AssessorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAssessor extends ViewRecord
{
    protected static string $resource = AssessorResource::class;

    // --- TAMBAHKAN METODE INI ---
    protected function getHeaderWidgets(): array
    {
        return [
            AssessorResource\Widgets\AssessorOverview::class,
        ];
    }
}