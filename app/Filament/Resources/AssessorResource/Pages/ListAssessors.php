<?php

namespace App\Filament\Resources\AssessorResource\Pages;

use App\Filament\Resources\AssessorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessors extends ListRecords
{
    protected static string $resource = AssessorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
