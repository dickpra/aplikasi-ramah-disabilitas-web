<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
// Tidak perlu lagi App\Models\User, kecuali untuk hal lain
use App\Models\Assessor; // Import Assessor model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; // Tidak perlu jika tidak ada modifyQueryUsing
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Closure;
use Filament\Tables\Actions\Action;
use App\Models\Location;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Support\Facades\Notification;



class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $modelLabel = 'Penugasan Asesor';
    protected static ?string $pluralModelLabel = 'Data Penugasan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('location_id')
                    ->relationship('location', 'name')
                    ->searchable()->preload()->required()->reactive()->label('Lokasi'),

                // Field untuk CREATE - Multi-select Asesor
                Forms\Components\Select::make('assessor_ids')
                    ->multiple()
                    ->maxItems(3)
                    // ->options(Assessor::pluck('name', 'id')->all())
                    // Mengambil asesor yang TIDAK memiliki tugas aktif
                    ->options(function (): array {
                        return Assessor::query()
                            ->whereDoesntHave('assignments', function (Builder $query) {
                                $query->whereIn('status', ['assigned', 'in_progress']);
                            })
                            ->pluck('name', 'id')
                            ->all();
                    },)
                    ->preload()->required()->label('Pilih Asesor (Max 3)')
                    ->rules([
                        'array',
                        // Validasi Max 3 per lokasi (seperti sebelumnya)
                        function (Forms\Get $get, string $operation): Closure {
                            return function (string $attribute, array $value, Closure $fail) use ($get, $operation) {
                                if ($operation !== 'create') return;
                                $locationId = $get('location_id');
                                if (!$locationId) { $fail("Pilih lokasi terlebih dahulu."); return; }
                                $selectedAssessorIds = $value;
                                $location = Location::find($locationId);
                                if (!$location) { $fail("Lokasi tidak valid."); return; }
                                $currentlyAssignedCount = $location->assignments()->count();
                                $newlySelectedCount = count($selectedAssessorIds);
                                if (($currentlyAssignedCount + $newlySelectedCount) > 3) {
                                    $fail("Total asesor untuk lokasi ini akan menjadi " . ($currentlyAssignedCount + $newlySelectedCount) . ", melebihi batas 3. Saat ini sudah ada {$currentlyAssignedCount} asesor.");
                                }
                                if (count(array_unique($selectedAssessorIds)) !== $newlySelectedCount) {
                                    $fail("Ada asesor yang sama dipilih lebih dari sekali.");
                                }
                            };
                        },
                        // Validasi: Setiap asesor yang dipilih tidak boleh punya tugas aktif lain
                        function (string $operation): Closure {
                            return function (string $attribute, array $value, Closure $fail) use ($operation) {
                                if ($operation !== 'create') return;
                                $selectedAssessorIds = $value;
                                foreach ($selectedAssessorIds as $assessorId) {
                                    $hasActiveAssignment = Assignment::where('assessor_id', $assessorId)
                                        ->whereIn('status', ['assigned', 'in_progress'])
                                        ->exists();
                                    if ($hasActiveAssignment) {
                                        $assessor = Assessor::find($assessorId);
                                        $fail("Asesor '{$assessor->name}' sudah memiliki tugas aktif lain. Setiap asesor hanya boleh memiliki satu tugas aktif.");
                                        // Anda bisa memutuskan untuk menghentikan validasi setelah menemukan satu error
                                        // atau mengumpulkan semua error. Untuk kesederhanaan, kita hentikan.
                                        return;
                                    }
                                }
                            };
                        }
                    ])
                    ->helperText('Maks. total 3 asesor per lokasi. Setiap asesor hanya boleh 1 tugas aktif.')
                    ->visible(fn (string $operation): bool => $operation === 'create'),

                // Field untuk EDIT - Single-select Asesor
                Forms\Components\Select::make('assessor_id')
                    ->relationship('assessor', 'name')
                    ->searchable()->preload()->required()->label('Asesor')
                    ->reactive() // Penting agar status bisa divalidasi ulang jika asesor berubah
                    ->rules([
                        // Validasi unique (seperti sebelumnya)
                        function (Forms\Get $get, ?Model $record, string $operation): ?Unique {
                            if ($operation !== 'edit' || !$record) return null;
                            return (new Unique('assignments', 'assessor_id'))
                                ->where('location_id', $get('location_id'))
                                ->ignore($record->id);
                        },
                        // Validasi Max 3 per lokasi untuk EDIT (seperti sebelumnya)
                        function (Forms\Get $get, ?Model $record, string $operation): ?Closure {
                            if ($operation !== 'edit' || !$record) return null;
                            return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                $locationId = $get('location_id');
                                $newlySelectedAssessorIdForThisSlot = $value;
                                if (!$locationId || !$newlySelectedAssessorIdForThisSlot) return;
                                $otherAssignmentsCount = Assignment::where('location_id', $locationId)
                                    ->where('id', '!=', $record->id)->count();
                                if ($otherAssignmentsCount >= 3) {
                                    $fail("Lokasi ini sudah memiliki 3 asesor lain.");
                                }
                            };
                        },
                        // Validasi: Asesor yang dipilih tidak boleh punya tugas aktif lain (selain yang sedang diedit ini jika statusnya juga aktif)
                        function (Forms\Get $get, ?Model $record, string $operation): ?Closure {
                            if ($operation !== 'edit' || !$record) return null;
                            return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                $targetAssessorId = $value; // asesor_id yang baru/tetap untuk record ini
                                $targetStatus = $get('status'); // status yang baru/tetap untuk record ini

                                if (!$targetAssessorId) return;

                                // Cek apakah asesor ini punya tugas aktif LAIN
                                $otherActiveAssignmentsCount = Assignment::where('assessor_id', $targetAssessorId)
                                    ->whereIn('status', ['assigned', 'in_progress'])
                                    ->where('id', '!=', $record->id) // Abaikan record yang sedang diedit
                                    ->count();

                                // Jika asesor ini sudah punya tugas aktif lain, DAN tugas yang sedang diedit ini statusnya juga akan aktif
                                if ($otherActiveAssignmentsCount > 0 && in_array($targetStatus, ['assigned', 'in_progress'])) {
                                    $assessor = Assessor::find($targetAssessorId);
                                    $fail("Asesor '{$assessor->name}' sudah memiliki tugas aktif lain. Tidak dapat menetapkan tugas ini sebagai aktif juga.");
                                }
                            };
                        }
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                Forms\Components\DatePicker::make('assignment_date')->label('Tanggal Penugasan')->default(now()),
                Forms\Components\DatePicker::make('due_date')->label('Batas Waktu'),
                Forms\Components\Select::make('status')
                    ->options([
                        'assigned' => 'Ditugaskan',
                        'in_progress' => 'Sedang Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])->default('assigned')->required()->label('Status Penugasan')->reactive(), // Tambahkan reactive agar validasi asesor bisa memicu ulang
                Forms\Components\Textarea::make('notes')->label('Catatan Tambahan')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')->searchable()->sortable()->label('Lokasi'),
                Tables\Columns\TextColumn::make('assessor.name')->searchable()->sortable()->label('Asesor'), // Diubah dari user.name
                Tables\Columns\TextColumn::make('assignment_date')->date()->sortable()->label('Tgl Penugasan'),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable()->label('Batas Waktu'),
                Tables\Columns\TextColumn::make('status')->badge()->searchable()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })->label('Status'),
                Tables\Columns\TextColumn::make('created_at') // Kolom untuk sorting
                    ->dateTime()
                    ->sortable()
                    ->label('Dibuat Pada')
                    // ->toggleable(isToggledHiddenByDefault: true), 
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')->relationship('location', 'name')->label('Filter Lokasi'),
                Tables\Filters\SelectFilter::make('assessor_id')->relationship('assessor', 'name')->label('Filter Asesor'), // Diubah
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'assigned' => 'Ditugaskan',
                        'in_progress' => 'Sedang Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])->label('Filter Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                
            ])
    
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}
