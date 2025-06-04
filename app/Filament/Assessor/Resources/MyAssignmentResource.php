<?php

namespace App\Filament\Assessor\Resources;

use App\Filament\Assessor\Resources\MyAssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\Indicator;
use App\Models\AssessmentScore;
use App\Models\Assessor; // Meskipun tidak secara langsung dipakai di form ini, mungkin berguna di tempat lain
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get; // Untuk mengambil nilai field lain di dalam Closure Form
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage; // Untuk URL bukti
use Illuminate\Validation\Rules\Unique;
use Closure;


class MyAssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Tugas Penilaian Saya';
    protected static ?string $pluralModelLabel = 'Tugas Penilaian Saya';

    public const ACTIVE_STATUSES = ['assigned', 'in_progress'];

    public static function getEloquentQuery(): Builder
    {
        $assessorId = Auth::guard('assessor')->id();
        // Fallback sederhana jika assessorId null saat development (HAPUS ATAU SESUAIKAN DI PRODUKSI)
        // if (!$assessorId && app()->environment('local')) {
        //     $firstAssessor = Assessor::first();
        //     if ($firstAssessor) $assessorId = $firstAssessor->id;
        //     \Illuminate\Support\Facades\Log::warning("[MyAssignmentResource] Fallback: Menggunakan Assessor ID: {$assessorId} untuk query.");
        // }
        return parent::getEloquentQuery()->where('assessor_id', $assessorId);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tugas')
                    ->columns(2)
                    ->disabled(fn(string $operation) => $operation === 'view')
                    ->schema([
                        Placeholder::make('location_name')
                            ->label('Lokasi yang Dinilai')
                            ->content(fn (Assignment $record): string => $record?->location->name ?? '-'),
                        Placeholder::make('location_type')
                            ->label('Tipe Lokasi')
                            ->content(fn (Assignment $record): string => $record?->location->location_type ?? '-'),
                        Placeholder::make('assignment_date_display')
                            ->label('Tanggal Penugasan')
                            ->content(fn (?Assignment $record): string => $record?->assignment_date?->translatedFormat('d M Y') ?? '-'),
                        Placeholder::make('due_date_display')
                            ->label('Batas Waktu Penilaian')
                            ->content(fn (?Assignment $record): string => $record?->due_date?->translatedFormat('d M Y') ?? '-'),
                        FormSelect::make('status')
                            ->label('Ubah Status Tugas Menjadi:')
                            ->options(['in_progress' => 'Sedang Dikerjakan'])
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status === 'assigned')
                            ->helperText('Pilih "Sedang Dikerjakan" jika Anda memulai penilaian ini.'),
                        Placeholder::make('current_status_display')
                            ->label('Status Tugas Saat Ini')
                            ->content(fn (?Assignment $record): string => ucfirst(str_replace('_', ' ', $record?->status ?? 'assigned')))
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status !== 'assigned'),
                        Textarea::make('notes')
                            ->label('Catatan Umum Penugasan (dari Admin)')
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn(string $operation, $state) => !empty($state)),
                    ]),

                Forms\Components\Section::make('Penilaian Indikator')
                    ->description('Isi skor, catatan, dan unggah bukti untuk setiap indikator yang relevan.')
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->schema([
                        Repeater::make('indicator_scores_repeater') // Nama yang akan digunakan di mutateFormDataBeforeFill dan handleRecordUpdate
                            ->label(false)
                            // TIDAK menggunakan ->relationship() agar kita bisa kelola data secara manual
                            // Ini memungkinkan kita menampilkan semua indikator relevan, bukan hanya yang sudah punya AssessmentScore
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(1)
                            ->grid(1)
                            ->itemLabel(function (array $state): ?string {
                                // $state adalah data untuk satu item repeater
                                $indicatorId = $state['indicator_id'] ?? null;
                                if ($indicatorId) {
                                    $indicator = Indicator::find($indicatorId);
                                    return $indicator?->name ?? 'Indikator Tidak Ditemukan';
                                }
                                return 'Indikator Baru';
                            })
                            ->schema([
                                Forms\Components\Hidden::make('indicator_id'), // Untuk menyimpan ID Indikator
                                Forms\Components\Hidden::make('assessment_score_id'), // Untuk menyimpan ID AssessmentScore jika sudah ada

                                Placeholder::make('indicator_display')
                                    ->label(false)
                                    ->columnSpanFull()
                                    ->content(function (Get $get): ?HtmlString {
                                        $indicatorId = $get('indicator_id');
                                        if (!$indicatorId) return null;
                                        $indicator = Indicator::find($indicatorId);
                                        if (!$indicator) return new HtmlString('<div>Indikator tidak valid atau tidak ditemukan.</div>');

                                        $htmlOutput = "<div class='mb-2'><strong>Indikator:</strong> " . e($indicator->name) . "</div>";
                                        if ($indicator->category) $htmlOutput .= "<div><strong>Kategori:</strong> " . e($indicator->category) . "</div>";
                                        if ($indicator->keywords) $htmlOutput .= "<div><strong>Kata Kunci:</strong> " . e($indicator->keywords) . "</div>";
                                        if ($indicator->measurement_method) $htmlOutput .= "<div><strong>Cara Pengukuran:</strong> " . e($indicator->measurement_method) . "</div>";
                                        if ($indicator->scoring_criteria_text) {
                                            $htmlOutput .= "<div class='mt-2 p-2 border rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700'><strong>Kriteria Penilaian:</strong><br>" . nl2br(e($indicator->scoring_criteria_text)) . "</div>";
                                        }
                                        return new HtmlString($htmlOutput);
                                    }),

                                Radio::make('score')
                                    ->label('Skor Pilihan')
                                    ->options(function (Get $get): array {
                                        $indicatorId = $get('indicator_id');
                                        if (!$indicatorId) return ['0' => 'N/A'];
                                        $indicator = Indicator::find($indicatorId);
                                        if ($indicator && $indicator->scale_type) {
                                            $scaleType = strtolower($indicator->scale_type);
                                            if (str_starts_with($scaleType, 'skala ')) {
                                                $parts = explode('-', str_replace('skala ', '', $scaleType));
                                                if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                                                    $range = range((int)$parts[0], (int)$parts[1]);
                                                    $options = [];
                                                    foreach ($range as $value) {
                                                        $options[(string)$value] = (string)$value; // Value dan Label adalah angka skor
                                                    }
                                                    return $options;
                                                }
                                            } elseif ($scaleType === 'ya/tidak') {
                                                return ['1' => 'Ya', '0' => 'Tidak']; // Simpan sebagai 1 atau 0
                                            } elseif ($scaleType === 'ada/tidak ada') {
                                                return ['1' => 'Ada', '0' => 'Tidak Ada'];
                                            }
                                            // TODO: Tambahkan parsing untuk 'Kustom' jika scoring_criteria_text punya format baku dan bisa diparsing menjadi opsi
                                            // Sementara, jika 'Kustom', asesor bisa input manual jika field diubah jadi TextInput, atau kita sediakan opsi umum.
                                        }
                                        // Fallback jika tidak ada scale_type atau tidak dikenali
                                        return ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '0' => 'N/A'];
                                    })
                                    // ->required() // Validasi kelengkapan akan dilakukan saat "Selesaikan Penilaian"
                                    ->inline()->inlineLabel(false),

                                Textarea::make('assessor_notes')
                                    ->label('Catatan Asesor untuk Indikator Ini')
                                    ->rows(3)->columnSpanFull(),

                                FileUpload::make('evidences_upload')
                                    ->label('Unggah Bukti Baru')
                                    ->multiple()
                                    ->directory(function (Get $get, Model $record) { // $record di sini adalah Assignment
                                        $assignmentId = $record?->id;
                                        $assessmentScoreId = $get('assessment_score_id'); // ID AssessmentScore dari item repeater ini
                                        return ($assignmentId && $assessmentScoreId) ? "assessment-evidences/{$assignmentId}/{$assessmentScoreId}" : "assessment-evidences-temp/" . uniqid();
                                    })
                                    ->reorderable()
                                    ->appendFiles() // Biarkan ini aktif agar bisa manage file lama dan baru
                                    ->helperText('File yang sudah ada akan tetap tersimpan kecuali dihapus dari daftar ini. File baru akan ditambahkan.')
                                    ->columnSpanFull(),
                                
                                // Placeholder untuk menampilkan bukti yang sudah ada dan opsi menghapusnya
                                // Ini adalah bagian yang lebih kompleks untuk diintegrasikan dengan state FileUpload
                                // Solusi sebelumnya dengan checkbox HTML manual adalah salah satu cara.
                                // Filament v3 mungkin memiliki cara yang lebih baik untuk menangani ini di dalam FileUpload.
                                // Untuk saat ini, fokus pada upload dan penyimpanan dasar.
                                // Jika FileUpload dengan appendFiles berfungsi baik, ia akan menampilkan file lama
                                // dan user bisa menghapusnya dari komponen FileUpload itu sendiri.
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')->label('Lokasi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('assignment_date')->label('Tgl Penugasan')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label('Batas Waktu')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })->searchable()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'assigned' => 'Ditugaskan',
                        'in_progress' => 'Sedang Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])->label('Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('assess')
                    ->label('Mulai/Lanjutkan Penilaian')->icon('heroicon-o-pencil-square')
                    ->url(fn (Assignment $record): string => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn (Assignment $record): bool => in_array($record->status, self::ACTIVE_STATUSES)),
                Tables\Actions\ViewAction::make()->label('Lihat Detail Tugas'),
            ])
            ->bulkActions([])
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyAssignments::route('/'),
            'edit' => Pages\EditMyAssignment::route('/{record}/edit'),
            // 'view' => Pages\ViewMyAssignment::route('/{record}'),
        ];
    }
}