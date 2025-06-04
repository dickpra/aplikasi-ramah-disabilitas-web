<?php

namespace App\Filament\Resources\PenugasanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Assessor;
use App\Models\Assignment; // Model untuk tabel pivot kita
use Illuminate\Database\Eloquent\Model; // Untuk type hint ownerRecord
use Closure;
use Filament\Notifications\Notification;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments'; // Nama relasi di model City

    // Opsional: jika ingin judul kustom untuk tabel RM
    // protected static ?string $title = 'Detail Penugasan Asesor';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('assessor_id')
                    ->label('Asesor')
                    ->options(function (?Model $ownerRecord, ?Assignment $record) { // $ownerRecord adalah City
                        $options = [];
                        if (!$ownerRecord) return $options; // Jika belum ada owner (City), kosongkan

                        // Jika edit, tampilkan asesor saat ini
                        if ($record && $record->assessor) {
                            $options[$record->assessor_id] = $record->assessor->name;
                        }

                        // Tampilkan asesor yang belum ditugaskan ke City ini
                        $assignedAssessorIds = $ownerRecord->assignments()
                            ->where('assessor_id', '!=', $record?->assessor_id) // Kecualikan diri sendiri jika edit
                            ->pluck('assessor_id')->all();

                        $availableAssessors = Assessor::whereNotIn('id', $assignedAssessorIds)
                            ->pluck('name', 'id');

                        return $options + $availableAssessors->all(); // Gabungkan
                    })
                    ->searchable()
                    ->required()
                    ->createOptionForm([ // Memungkinkan membuat asesor baru dari sini (opsional)
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(table: Assessor::class, column: 'email'),
                        Forms\Components\TextInput::make('password')->password()->required()->minLength(8),
                    ])
                    ->rules([
                        // Validasi agar tidak duplikat asesor untuk kota yang sama
                        fn (?Model $ownerRecord, ?Assignment $record): Closure => function (string $attribute, $value, Closure $fail) use ($ownerRecord, $record) {
                            if (!$ownerRecord) return;
                            $query = $ownerRecord->assignments()->where('assessor_id', $value);
                            if ($record) { // Jika sedang edit, kecualikan record saat ini
                                $query->where('id', '!=', $record->id);
                            }
                            if ($query->exists()) {
                                $fail('Asesor ini sudah ditugaskan untuk kota tersebut.');
                            }
                        },
                        // Validasi maksimal 3 asesor per kota
                        fn (?Model $ownerRecord, ?Assignment $record): Closure => function (string $attribute, $value, Closure $fail) use ($ownerRecord, $record) {
                            if (!$ownerRecord) return;
                            $countQuery = $ownerRecord->assignments();
                            if ($record) { // Jika sedang edit, jangan hitung diri sendiri
                                $countQuery->where('id', '!=', $record->id);
                            }
                            $existingCount = $countQuery->count();
                            // Karena ini form untuk 1 assignment, kita cek apakah sudah ada 2 atau lebih (jadi ini akan jadi yang ke-3 atau kurang)
                            // Saat membuat baru, $record adalah null.
                            if (is_null($record) && $existingCount >= 3) { // Saat membuat baru dan sudah ada 3
                                $fail('Kota ini sudah memiliki 3 asesor.');
                            } elseif ($existingCount >= 3) { // Saat edit, jika sudah ada 3 lain (tidak mungkin kecuali bug)
                                 $fail('Kota ini sudah memiliki 3 asesor.');
                            }
                        }
                    ]),
                Forms\Components\DatePicker::make('assignment_date')
                    ->label('Tanggal Penugasan')
                    ->default(now())
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('assessor.name') // Atau definisikan accessor di Assignment
            ->columns([
                Tables\Columns\TextColumn::make('assessor.name')
                    ->label('Nama Asesor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_date')
                    ->date('d M Y')
                    ->label('Tanggal Penugasan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(fn (Assignment $record) => $record->description),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make() // Tombol "New" di tabel RM
                    ->mutateFormDataUsing(function (array $data, Model $ownerRecord): array {
                         // Validasi max 3 sebelum membuat
                        if ($ownerRecord->assignments()->count() >= 3) {
                            Notification::make()
                                ->title('Batas Maksimal Tercapai')
                                ->body('Kota ini sudah memiliki 3 asesor. Tidak dapat menambahkan lagi.')
                                ->danger()
                                ->send();
                            throw \Illuminate\Validation\ValidationException::withMessages(['Failed']); // Hentikan proses
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Hapus satu link assignment
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
