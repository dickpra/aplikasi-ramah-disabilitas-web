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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile; // Penting untuk saveUploadedFileUsing
use App\Filament\Assessor\Resources\MyAssignmentResource\Pages\EditMyAssignment; // Penting untuk type hint $livewire


class MyAssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = null;
    protected static ?string $pluralModelLabel = null;

    public static function getModelLabel(): string
    {
        return __('Tugas Penilaian Saya');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tugas Penilaian Saya');
    }

    public const ACTIVE_STATUSES = ['assigned', 'in_progress', 'revision_needed'];

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
                Forms\Components\Section::make(__('Detail Tugas'))
                    ->columns(2)
                    ->disabled(fn(string $operation) => $operation === 'view')
                    ->schema([
                        Placeholder::make('location_name')
                            ->label(__('Lokasi yang Dinilai'))
                            ->content(fn (Assignment $record): string => $record?->location->name ?? '-'),
                        Placeholder::make('location_type')
                            ->label(__('Tipe Lokasi'))
                            ->content(fn (Assignment $record): string => $record?->location->location_type ?? '-'),
                        Placeholder::make('assignment_date_display')
                            ->label(__('Tanggal Penugasan'))
                            ->content(fn (?Assignment $record): string => $record?->assignment_date?->translatedFormat('d M Y') ?? '-'),
                        Placeholder::make('due_date_display')
                            ->label(__('Batas Waktu Penilaian'))
                            ->content(fn (?Assignment $record): string => $record?->due_date?->translatedFormat('d M Y') ?? '-'),
                        FormSelect::make('status')
                            ->label(__('Ubah Status Tugas Menjadi:'))
                            ->options(['in_progress' => __('Sedang Dikerjakan')])
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status === 'assigned')
                            ->helperText(__('Pilih "Sedang Dikerjakan" jika Anda memulai penilaian ini.')),
                        // Placeholder::make('current_status_display')
                        //     ->label('Status Tugas Saat Ini')
                        //     ->content(fn (?Assignment $record): string => ucfirst(str_replace('_', ' ', $record?->status ?? 'assigned')))
                        //     ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status !== 'assigned'),
                        Forms\Components\Placeholder::make('current_status_display')
                            ->label(__('Status Tugas Saat Ini'))
                            ->content(function (?Assignment $record): ?HtmlString {
                                if (!$record || !$record->status) {
                                    return null;
                                }
                                $status = $record->status;
                                $statusText = ucfirst(str_replace('_', ' ', $status));

                                // Tentukan nama warna Filament berdasarkan status
                                $colorName = match ($status) {
                                    'assigned' => 'primary',
                                    'in_progress' => 'warning',
                                    'completed' => 'info',
                                    'pending_review_admin' => 'warning',
                                    'approved' => 'success',
                                    'revision_needed' => 'danger',
                                    'cancelled' => 'gray',
                                    default => 'gray',
                                };

                                // Bangun kelas CSS untuk warna teks
                                $colorClasses = "text-{$colorName}-600 dark:text-{$colorName}-400";
                                
                                // Buat elemen HTML span dengan kelas warna
                                $html = "<span class='font-semibold {$colorClasses}'>" . e($statusText) . "</span>";
                                
                                return new HtmlString($html);
                            })
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status !== 'assigned'),
                        Textarea::make('notes')
                            ->label(__('Catatan Umum Penugasan (dari Admin)'))
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn(string $operation, $state) => !empty($state)),
                    ]),

                Forms\Components\Section::make(__('Penilaian Indikator'))
                    ->description(__('Isi skor, catatan, dan unggah bukti untuk setiap indikator yang relevan.'))
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
                                    return $indicator?->name ?? (__('Indikator Tidak Ditemukan'));
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
                                        if (!$indicator) return new HtmlString('<div>(' . __('Indikator tidak valid atau tidak ditemukan.') . ')</div>');

                                        $htmlOutput = "<div class='mb-2'><strong>Indikator:</strong> " . e($indicator->name) . "</div>";
                                        if ($indicator->category) $htmlOutput .= "<div><strong> " . __('Kategori:') . "</strong> " . e($indicator->category) . "</div>";
                                        if ($indicator->keywords) $htmlOutput .= "<div><strong> " . __('Kata Kunci:') . "</strong> " . e($indicator->keywords) . "</div>";
                                        if ($indicator->measurement_method) $htmlOutput .= "<div><strong> " . __('Cara Pengukuran:') . "</strong> " . e($indicator->measurement_method) . "</div>";
                                        if ($indicator->keywords) $htmlOutput .= "<div><strong> " . __('Kata Kunci:') . "</strong> " . e($indicator->keywords) . "</div>";
                                        if ($indicator->measurement_method) $htmlOutput .= "<div><strong> " . __('Cara Pengukuran:') . "</strong> " . e($indicator->measurement_method) . "</div>";
                                        if ($indicator->scoring_criteria_text) {
                                            $htmlOutput .= "<div class='mt-2 p-2 border rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700'><strong> " . __('Kriteria Penilaian:') . "</strong><br>" . nl2br(e($indicator->scoring_criteria_text)) . "</div>";
                                        }
                                        return new HtmlString($htmlOutput);
                                    }),

                                    Radio::make('score')
                                    ->label(__('Pilih Skor:'))
                                    ->options(function (Get $get): array {
                                        $indicatorId = $get('indicator_id');
                                        if (!$indicatorId) {
                                            return ['0' => __('N/A (Indikator tidak valid)')]; // Fallback
                                        }
                                        
                                        $indicator = Indicator::find($indicatorId);
                                        if (!$indicator) {
                                            return ['0' => __('N/A (Indikator tidak ditemukan)')]; // Fallback
                                        }

                                        $options = [];
                                        $scaleType = strtolower($indicator->scale_type ?? '');
                                        $criteriaText = $indicator->scoring_criteria_text ?? '';

                                        // 1. Prioritaskan parsing dari scoring_criteria_text jika ada dan formatnya baku
                                        // Asumsi format di scoring_criteria_text: "1. Deskripsi Level 1\n2. Deskripsi Level 2\n..."
                                        if (!empty($criteriaText)) {
                                            $criteriaLines = preg_split('/\r\n|\r|\n/', $criteriaText);
                                            foreach ($criteriaLines as $line) {
                                                // Mencocokkan format seperti "1. Teks Kriteria" atau "1 Teks Kriteria"
                                                if (preg_match('/^(\d+)\s*[\.\)]?\s*(.*)/', trim($line), $matches)) {
                                                    $scoreValue = trim($matches[1]);
                                                    $scoreLabel = trim($matches[2]);
                                                    // Gunakan skor sebagai value dan labelnya adalah skor + deskripsi kriteria
                                                    $options[$scoreValue] = $scoreValue . '. ' . $scoreLabel;
                                                }
                                            }
                                            // Jika berhasil parsing dari kriteria, gunakan itu
                                            if (!empty($options)) {
                                                return $options;
                                            }
                                        }

                                        // 2. Jika scoring_criteria_text tidak ada atau tidak bisa diparsing, gunakan scale_type
                                        if (str_starts_with($scaleType, 'skala ')) { // Contoh: "Skala 1-3", "Skala 1-5"
                                            $parts = explode('-', str_replace('skala ', '', $scaleType));
                                            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                                                $start = (int)$parts[0];
                                                $end = (int)$parts[1];
                                                if ($start <= $end) { // Pastikan rentang valid
                                                    $range = range($start, $end);
                                                    // Label dan value sama (angka skor)
                                                    return array_combine(array_map('strval', $range), array_map('strval', $range));
                                                }
                                            }
                                        } elseif ($scaleType === 'ya/tidak') {
                                            return ['1' => 'Ya (1)', '0' => 'Tidak (0)']; // Simpan sebagai 1 atau 0
                                        } elseif ($scaleType === 'ada/tidak ada') {
                                            return ['1' => 'Ada (1)', '0' => 'Tidak Ada (0)'];
                                        }

                                        // 3. Fallback jika tidak ada scale_type yang cocok atau tidak ada kriteria yang bisa diparsing
                                        // Anda bisa set default yang paling umum atau error/pesan
                                        return ['0' => 'N/A (Skala tidak terdefinisi)'];
                                    })
                                    ->inline()
                                    ->inlineLabel(false)
                                    // ->required() // Dihapus agar bisa simpan progres
                                ,
                                Textarea::make('assessor_notes')
                                    ->label(__('Catatan Asesor untuk Indikator Ini'))
                                    ->rows(3)->columnSpanFull(),

                                FileUpload::make('evidences_upload') // Nama field konsisten
                                    ->label(__('Unggah atau Kelola Bukti Pendukung'))
                                    ->multiple()->disk('public')
                                    ->visibility('public')
                                    // ->maxSize(10000) // 1GB
                                    ->directory(function (Get $get, Model $record /* Assignment */) {
                                        $assignmentId = $record?->id;
                                        $assessmentScoreId = $get('assessment_score_id');
                                        return ($assignmentId && $assessmentScoreId) ? "assessment-evidences/{$assignmentId}/{$assessmentScoreId}" : "assessment-evidences-temp/" . uniqid();
                                    })
                                    ->reorderable()->appendFiles()->openable()->downloadable()->previewable(true)
                                    ->saveUploadedFileUsing(static function (TemporaryUploadedFile $file, Get $get, Model $record /* Assignment */, EditMyAssignment $livewire /* Halaman EditMyAssignment */): string {
                                        $assignmentId = $record?->id;
                                        // $get('assessment_score_id') di sini mungkin tidak selalu reliable untuk path karena ini saat file diupload,
                                        // assessment_score_id mungkin belum final jika item repeater baru.
                                        // Lebih aman menggunakan path yang lebih umum di sini, atau path sementara,
                                        // lalu di handleRecordUpdate kita pindahkan/rename jika perlu saat AssessmentScore ID sudah pasti.
                                        // Untuk direktori, kita bisa buat berdasarkan assignmentId saja dulu.
                                        $tempDirSuffix = $get('assessment_score_id') ?? $get('indicator_id') ?? 'temp';
                                        $directory = "assessment-evidences/{$assignmentId}/{$tempDirSuffix}";
                                        
                                        $path = $file->store($directory, 'public');

                                        if (property_exists($livewire, 'newlyUploadedFilesMetadata')) {
                                            $livewire->newlyUploadedFilesMetadata[$path] = [
                                                'original_file_name' => $file->getClientOriginalName(),
                                                'file_mime_type' => $file->getMimeType(),
                                                'file_size' => $file->getSize(),
                                                'assessment_score_indicator_id' => $get('indicator_id'), // Simpan indicator_id untuk mencocokkan nanti
                                            ];
                                        }
                                        return $path; 
                                    })
                                    ->helperText(__('File lama akan tampil. Unggah file baru atau hapus dari daftar.'))
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
                Tables\Columns\TextColumn::make('location.name')->label(__('Lokasi'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('assignment_date')->label(__('Tgl Penugasan'))->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label(__('Batas Waktu'))->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()
                    ->color(fn (string $state): string => match ($state) {
                        // 'assigned' => 'primary',
                        // 'in_progress' => 'warning',
                        // 'completed' => 'success',
                        // 'cancelled' => 'danger',
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'info',
                        'pending_review_admin' => 'warning',
                        'approved' => 'success',
                        'revision_needed' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })->searchable()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'assigned' => __('Ditugaskan'),
                        'in_progress' => __('Sedang Berjalan'),
                        'completed' => __('Selesai'),
                        'cancelled' => __('Dibatalkan'),
                    ])->label(__('Status')),
            ])
            ->actions([
                Tables\Actions\Action::make('assess')
                    ->label(__('Mulai/Lanjutkan Penilaian'))->icon('heroicon-o-pencil-square')
                    ->url(fn (Assignment $record): string => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn (Assignment $record): bool => in_array($record->status, self::ACTIVE_STATUSES)),
                Tables\Actions\ViewAction::make()->label(__('Lihat Detail Tugas'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Assignment $record): string => static::getUrl('edit', ['record' => $record, 'view' => true]))
                    ->visible(fn (Assignment $record): bool => !in_array($record->status, self::ACTIVE_STATUSES)),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            // ->defaultSort('due_date', 'asc')
            ;
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