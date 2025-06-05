<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Assignment;
use App\Filament\Resources\AssignmentResource; // Penting untuk link aksi
use Filament\Tables\Actions\Action;

class RecentAssignmentsWidget extends BaseWidget
{
    protected static ?int $sort = 5; // Atur urutan agar tampil paling bawah
    protected int | string | array $columnSpan = 'full'; // Widget ini akan memakai lebar penuh

    protected static ?string $heading = 'Aktivitas Penugasan Terkini';

    public function table(Table $table): Table
    {
        return $table
            // Mengambil data assignment, diurutkan berdasarkan kapan terakhir di-update
            ->query(Assignment::query()->latest('updated_at')->limit(5))
            ->paginated(true) // Tidak perlu paginasi untuk widget
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Lokasi'),
                Tables\Columns\TextColumn::make('assessor.name')
                    ->label('Asesor'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Terkini')
                    ->badge()
                    ->color(fn (string $state): string => match ($state ?? '') {
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'info',
                        'pending_review_admin' => 'warning',
                        'approved' => 'success',
                        'revision_needed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->since() // Tampilkan dalam format "x menit yang lalu"
                    ->sortable(),
            ])
            ->actions([
                // Aksi ini akan mengarahkan admin ke halaman yang tepat (Review atau Edit)
                Action::make('goTo')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (Assignment $record): string {
                        // Jika status sudah selesai/disetujui, arahkan ke halaman Review
                        if (in_array($record->status, ['completed', 'pending_review_admin', 'approved'])) {
                            return AssignmentResource::getUrl('review', ['record' => $record]);
                        }
                        // Jika masih aktif, arahkan ke halaman Edit
                        return AssignmentResource::getUrl('edit', ['record' => $record]);
                    }),
            ]);
    }
}