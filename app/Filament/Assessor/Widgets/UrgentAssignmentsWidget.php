<?php

namespace App\Filament\Assessor\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;
use App\Filament\Assessor\Resources\MyAssignmentResource; // Untuk link

class UrgentAssignmentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = null;

    public static function getHeading(): ?string
    {
        return __('Tugas Baru (Berdasarkan Batas Waktu)');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Assignment::query()
                    ->where('assessor_id', Auth::guard('assessor')->id())
                    ->whereIn('status', ['assigned', 'in_progress', 'revision_needed']) // Hanya tugas aktif
            )
            ->defaultSort('due_date', 'asc')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('location.name')->label(__('Lokasi')),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->label(__('Batas Waktu')),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'primary', 'revision_needed' => 'danger', default => 'warning',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('kerjakan')
                    ->label(__('Kerjakan'))
                    ->url(fn (Assignment $record): string => MyAssignmentResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-arrow-right'),
            ]);
    }
}