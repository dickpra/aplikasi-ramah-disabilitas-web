<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // public function getFormActions(): array
    // {
    //     return [
    //         Forms\Components\Select::make('assessor_ids')
    //             ->label('Assessor')
    //             ->disabled()
    //     ];
    // }
}
