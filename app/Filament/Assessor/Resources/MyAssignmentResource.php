<?php

namespace App\Filament\Assessor\Resources;

use App\Filament\Assessor\Resources\MyAssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\Indicator; // Kita akan butuh ini nanti untuk form penilaian
use App\Models\AssessmentScore; // Untuk menyimpan skor
use App\Models\Assessor; // Untuk memastikan kita mengambil ID asesor yang benar
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan ID asesor yang login
use Illuminate\Database\Eloquent\Model; // Untuk type hinting di form
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio; // Untuk skor jika opsinya sedikit
use Filament\Forms\Components\Select as FormSelect; // Alias untuk Select Form
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get; // Untuk mengambil nilai field lain
use Filament\Forms\Set; // Untuk mengatur nilai field lain
use Illuminate\Support\HtmlString; // <--- IMPORT HtmlString




class MyAssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Tugas Penilaian Saya';
    protected static ?string $pluralModelLabel = 'Tugas Penilaian Saya';
    // protected static ?string $navigationGroup = 'Penilaian'; // Jika Anda ingin grup di panel asesor

    // 1. Hanya tampilkan penugasan untuk asesor yang sedang login
    public static function getEloquentQuery(): Builder
    {
        // Pastikan guard 'assessor' aktif dan mengambil ID yang benar
        // Jika Anda mengonfigurasi guard 'assessor' sebagai default untuk panel ini, Auth::id() mungkin cukup.
        // Jika tidak, gunakan Auth::guard('assessor')->id()
        $assessorId = Auth::guard('assessor')->id();
        if (!$assessorId && app()->environment('local')) {
            // Fallback untuk development jika login asesor belum sempurna,
            // atau jika ingin melihat semua saat development. Hapus atau sesuaikan untuk produksi.
            // $assessor = Assessor::first(); // Ambil asesor pertama sebagai contoh
            // if ($assessor) $assessorId = $assessor->id;
        }
        
        return parent::getEloquentQuery()->where('assessor_id', $assessorId);
    }

    // 2. Asesor tidak bisa membuat penugasan baru dari panel mereka
    public static function canCreate(): bool
    {
        return false;
    }

    // 3. Asesor tidak bisa menghapus penugasan (biasanya)
    // Anda bisa override canDelete() atau canForceDelete() jika perlu
    // public static function canDelete(Model $record): bool
    // {
    //     return false;
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tugas')
                    ->columns(2)
                    ->disabled(fn(string $operation) => $operation === 'view') // Disable semua di section ini saat view
                    ->schema([
                        Placeholder::make('location_name')
                            ->label('Lokasi yang Dinilai')
                            ->content(fn (?Assignment $record): string => $record?->location->name ?? '-'),
                        Placeholder::make('location_type')
                            ->label('Tipe Lokasi')
                            ->content(fn (?Assignment $record): string => $record?->location->location_type ?? '-'),
                        Placeholder::make('assignment_date_display') // Ganti nama agar tidak konflik jika ada field 'assignment_date'
                            ->label('Tanggal Penugasan')
                            ->content(fn (?Assignment $record): string => $record?->assignment_date?->translatedFormat('d M Y') ?? '-'),
                        Placeholder::make('due_date_display')
                            ->label('Batas Waktu Penilaian')
                            ->content(fn (?Assignment $record): string => $record?->due_date?->translatedFormat('d M Y') ?? '-'),
                        // Asesor mungkin bisa mengubah status tugasnya ke 'in_progress'
                        FormSelect::make('status')
                            ->options([
                                // 'assigned' => 'Ditugaskan', // Mungkin tidak diubah oleh asesor
                                'in_progress' => 'Sedang Dikerjakan',
                                // Status 'completed' akan dihandle oleh tombol submit khusus
                            ])
                            ->label('Status Tugas Saat Ini')
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status === 'assigned')
                            ->helperText('Ubah status menjadi "Sedang Dikerjakan" jika Anda memulai penilaian.'),
                        Placeholder::make('current_status_display')
                            ->label('Status Tugas Saat Ini')
                            ->content(fn (?Assignment $record): string => ucfirst(str_replace('_', ' ', $record?->status ?? 'assigned')))
                            ->visible(fn (string $operation, ?Assignment $record): bool => $operation === 'edit' && $record?->status !== 'assigned'),
                        Textarea::make('notes')
                            ->label('Catatan Umum Penugasan (dari Admin)')
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn(string $operation, $state) => !empty($state)), // Hanya tampil jika ada isinya
                    ]),

                Forms\Components\Section::make('Penilaian Indikator')
                    ->description('Isi skor, catatan, dan unggah bukti untuk setiap indikator yang relevan.')
                    ->visible(fn (string $operation): bool => $operation === 'edit') // Hanya tampil di mode edit
                    ->schema([
                        Repeater::make('indicator_scores')
                            ->label(false)
                            ->relationship('assessmentScores') // Ini penting
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([ // Skema untuk setiap item (setiap AssessmentScore)
                                Placeholder::make('indicator_name')
                                    ->label('Indikator')
                                    ->content(fn (?AssessmentScore $record): string => $record?->indicator->name ?? 'Indikator tidak ditemukan'),

                                    Placeholder::make('indicator_details')
                                    ->label(false)
                                    ->columnSpanFull()
                                    ->content(function (?AssessmentScore $record): ?HtmlString { // Return type diubah ke ?HtmlString
                                        if (!$record || !$record->indicator) {
                                            return null; // Kembalikan null jika tidak ada data
                                        }
                                        $indicator = $record->indicator;
                                        $htmlOutput = '';
                                        if ($indicator->category) {
                                            $htmlOutput .= "<div><strong>Kategori:</strong> " . e($indicator->category) . "</div>";
                                        }
                                        if ($indicator->keywords) {
                                            $htmlOutput .= "<div><strong>Kata Kunci:</strong> " . e($indicator->keywords) . "</div>";
                                        }
                                        if ($indicator->measurement_method) {
                                            $htmlOutput .= "<div><strong>Cara Pengukuran:</strong> " . e($indicator->measurement_method) . "</div>";
                                        }
                                        if ($indicator->scoring_criteria_text) {
                                            // Gunakan e() untuk data, nl2br() aman untuk output HTML yang diinginkan
                                            $htmlOutput .= "<div class='mt-2 p-2 border rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700'><strong>Kriteria Penilaian:</strong><br>" . nl2br(e($indicator->scoring_criteria_text)) . "</div>";
                                        }
                                        
                                        if (empty($htmlOutput)) {
                                            return null; // Kembalikan null jika tidak ada konten untuk ditampilkan
                                        }
                                        return new HtmlString($htmlOutput); // <--- Bungkus dengan HtmlString
                                    }),

                                // Input Skor berdasarkan Tipe Skala Indikator
                                Forms\Components\Radio::make('score')
                                    ->label('Skor')
                                    ->options(function (?AssessmentScore $record): array {
                                        // Logika untuk membuat opsi radio button berdasarkan indicator->scale_type atau scoring_criteria_text
                                        // Contoh sederhana untuk skala numerik
                                        if ($record && $record->indicator && str_starts_with(strtolower($record->indicator->scale_type ?? ''), 'skala ')) {
                                            // Misal scale_type "Skala 1-3" atau "Skala 1-5"
                                            $parts = explode('-', str_replace('Skala ', '', $record->indicator->scale_type));
                                            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                                                return array_combine(range((int)$parts[0], (int)$parts[1]), range((int)$parts[0], (int)$parts[1]));
                                            }
                                        }
                                        // Default atau jika tipe skala lain
                                        return [ '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']; // Sesuaikan
                                    })
                                    ->required() // Setiap indikator harus diberi skor
                                    ->inline()
                                    ->inlineLabel(false),

                                Textarea::make('assessor_notes')
                                    ->label('Catatan Asesor untuk Indikator Ini')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                FileUpload::make('evidences') // Akan menjadi array file jika multiple
                                    ->label('Unggah Bukti Pendukung')
                                    ->multiple()
                                    ->directory(fn(?AssessmentScore $record) => $record ? "assessment-evidences/{$record->assignment_id}/{$record->id}" : "assessment-evidences-temp")
                                    ->reorderable()
                                    ->appendFiles() // Penting jika asesor mengedit dan menambah file
                                    // ->relationship('evidences') // Relasi AssessmentScore ke AssessmentEvidence. Ini perlu setup khusus untuk repeater.
                                    // Untuk FileUpload dalam repeater yang menyimpan ke tabel terpisah, biasanya perlu custom save logic.
                                    ->helperText('Anda bisa mengunggah beberapa file jika perlu.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1) // Setiap item repeater (indikator) menggunakan 1 kolom penuh
                            ->grid(1), // Pastikan grid untuk repeater item
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_date')
                    ->label('Tgl Penugasan')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Batas Waktu')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
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
                // Aksi "Lakukan Penilaian" akan mengarah ke halaman edit resource ini
                Tables\Actions\Action::make('assess')
                    ->label('Mulai/Lanjutkan Penilaian')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Assignment $record): string => static::getUrl('edit', ['record' => $record]))
                    // Hanya aktif jika statusnya memungkinkan untuk dinilai
                    ->visible(fn (Assignment $record): bool => in_array($record->status, ['assigned', 'in_progress'])),
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail Tugas'), // Halaman view akan menggunakan form() yang sama tapi disabled
            ])
            ->bulkActions([
                // Biasanya tidak ada bulk actions untuk asesor di daftar tugasnya
            ])
            ->defaultSort('due_date', 'asc'); // Urutkan berdasarkan batas waktu terdekat
    }

    public static function getRelations(): array
    {
        return [
            // Tidak ada relation manager standar di sini untuk asesor
        ];
    }

    public static function getPages(): array
    {
        // Sesuaikan nama class Pages dengan yang digenerate oleh Filament
        // Biasanya ada di App\Filament\Assessor\Resources\MyAssignmentResource\Pages
        return [
            'index' => Pages\ListMyAssignments::route('/'),
            // 'create' => Pages\CreateMyAssignment::route('/create'), // Sudah di-disable via canCreate()
            'edit' => Pages\EditMyAssignment::route('/{record}/edit'), // Ini akan jadi halaman utama penilaian
            // 'view' => Pages\ViewMyAssignment::route('/{record}'),
        ];
    }
}