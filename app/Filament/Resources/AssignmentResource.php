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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Infolists; // Untuk View/Infolist
use Filament\Infolists\Infolist;
use App\Models\Indicator; // Diperlukan untuk menampilkan detail indikator
use App\Models\AssessmentScore; // Diperlukan untuk menampilkan skor
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;


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
                ->searchable()
                ->preload()
                ->required()
                ->label('Lokasi')
                // Menjadi non-aktif saat operasi adalah 'edit'
                ->disabled(fn (string $operation): bool => $operation === 'edit'), // <-- TAMBAHKAN ATAU PASTIKAN BARIS INI ADA

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
                                        ->whereIn('status', ['assigned', 'in_progress', 'pending_review_admin', 'revision_needed'])
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
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled()
                    ->label('Asesor')
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
                        'completed' => 'Selesai (dari Asesor)',
                        'pending_review_admin' => 'Menunggu Review Admin', // Jika ada alur review
                        'approved' => 'Disetujui Admin', // Status final
                        'revision_needed' => 'Butuh Revisi',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->default('assigned')
                    ->required()
                    ->label('Status Penugasan')
                    ->reactive(), // Tambahkan reactive agar validasi asesor bisa memicu ulang
                Forms\Components\Textarea::make('notes')->label('Catatan Tambahan')->columnSpanFull(),


                // // FORM CADANGAN
                // Forms\Components\Section::make('Review & Status Penugasan (Admin)')
                //     ->description('Review hasil penilaian dan ubah status jika diperlukan.')
                //     ->columns(2)
                //     ->schema([
                //         Select::make('status')
                //             ->label('Ubah Status Penugasan')
                //             ->options([
                //                 'assigned' => 'Ditugaskan',
                //                 'in_progress' => 'Sedang Berjalan',
                //                 'completed' => 'Selesai (dari Asesor)',
                //                 'pending_review_admin' => 'Menunggu Review Admin',
                //                 'approved' => 'Disetujui Admin (Final)',
                //                 'revision_needed' => 'Butuh Revisi',
                //                 'cancelled' => 'Dibatalkan',
                //             ])
                //             ->required(),
                //         // Anda bisa menambahkan kolom baru di tabel 'assignments' untuk catatan admin
                //         // Misalnya, `admin_review_notes` (TEXT, nullable)
                //         Textarea::make('admin_review_notes')
                //             ->label('Catatan Review Admin')
                //             ->rows(3)
                //             ->columnSpanFull()
                //             ->helperText('Catatan ini hanya bisa dilihat oleh admin lain.'),
                //     ]),


                
                // Forms\Components\Section::make('Detail Tugas (Read-Only)')
                //     ->columns(2)
                //     ->schema([
                //         Placeholder::make('location_name')->label('Lokasi')->content(fn (?Assignment $record): string => $record?->location->name ?? '-'),
                //         Placeholder::make('assessor_name')->label('Asesor')->content(fn (?Assignment $record): string => $record?->assessor->name ?? '-'),
                //         Placeholder::make('assignment_date')->label('Tanggal Penugasan')->content(fn (?Assignment $record): string => $record?->assignment_date?->translatedFormat('d M Y') ?? '-'),
                //         Placeholder::make('due_date')->label('Batas Waktu')->content(fn (?Assignment $record): string => $record?->due_date?->translatedFormat('d M Y') ?? '-'),
                //     ]),

                // Forms\Components\Section::make('Hasil Penilaian dari Asesor (Read-Only)')
                //     ->schema([
                //         Repeater::make('assessmentScores') // Langsung menggunakan relasi
                //             ->relationship()
                //             ->label(false)
                //             ->addable(false)->deletable(false)->reorderable(false)
                //             ->columns(1)->grid(1)
                //             ->disabled() // <--- KUNCI UTAMA: Seluruh repeater menjadi read-only untuk admin
                //             // ->itemLabel(fn (array $state): ?string => AssessmentScore::find($state['id'])?->indicator?->name ?? 'Indikator')
                //             ->schema([
                //                 Placeholder::make('indicator_info') // Placeholder untuk semua info indikator
                //                     ->label(false)
                //                     ->columnSpanFull()
                //                     ->content(function (?AssessmentScore $record): ?HtmlString {
                //                         if (!$record || !$record->indicator) return null;
                //                         $indicator = $record->indicator;
                //                         $html = "<div class='py-2'>";
                //                         $html .= "<div><strong>Indikator:</strong> " . e($indicator->name) . "</div>";
                //                         if ($indicator->category) $html .= "<div><strong>Kategori:</strong> " . e($indicator->category) . "</div>";
                //                         if ($indicator->scoring_criteria_text) $html .= "<div class='mt-2 text-xs p-2 border rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700'><strong>Kriteria Penilaian:</strong><br>" . nl2br(e($indicator->scoring_criteria_text)) . "</div>";
                //                         $html .= "</div>";
                //                         return new HtmlString($html);
                //                     }),

                //                 Radio::make('score')
                //                     ->label('Skor Diberikan')
                //                     ->options(function (?AssessmentScore $record): array {
                //                         // Logika untuk menampilkan opsi skor berdasarkan kriteria
                //                         // agar admin tahu apa arti skor yang dipilih asesor.
                //                         if ($record && $record->indicator && $record->indicator->scoring_criteria_text) {
                //                             $options = [];
                //                             $criteriaLines = preg_split('/\r\n|\r|\n/', $record->indicator->scoring_criteria_text);
                //                             foreach ($criteriaLines as $line) {
                //                                 if (preg_match('/^(\d+)\s*[\.\)]?\s*(.*)/', trim($line), $matches)) {
                //                                     $options[trim($matches[1])] = trim($matches[1].'. '.$matches[2]);
                //                                 }
                //                             }
                //                             if (!empty($options)) return $options;
                //                         }
                //                         // Fallback jika tidak ada kriteria teks
                //                         $score = $record->score ?? 'N/A';
                //                         return [$score => $score];
                //                     })
                //                     ->inline()->inlineLabel(false),

                //                 Textarea::make('assessor_notes')
                //                     ->label('Catatan dari Asesor')
                //                     ->rows(3)->columnSpanFull(),
                                
                //                 Placeholder::make('evidences_display')
                //                     ->label('Bukti Pendukung')
                //                     ->columnSpanFull()
                //                     ->content(function (?AssessmentScore $record): ?HtmlString {
                //                         if (!$record || $record->evidences->isEmpty()) return new HtmlString('<p>Tidak ada bukti yang dilampirkan.</p>');
                                        
                //                         $html = '<div class="flex flex-wrap gap-4 mt-2">';
                //                         foreach ($record->evidences as $evidence) {
                //                             $url = Storage::disk('public')->url($evidence->file_path);
                //                             $fileName = $evidence->original_file_name ?? basename($evidence->file_path);
                //                             $isImage = Str::startsWith($evidence->file_mime_type, 'image/');

                //                             if ($isImage) {
                //                                 $html .= "
                //                                     <a href='{$url}' target='_blank' class='block w-24 h-24'>
                //                                         <img src='{$url}' alt='" . e($fileName) . "' class='object-cover w-full h-full rounded-lg shadow-md hover:shadow-xl transition-shadow'>
                //                                     </a>
                //                                 ";
                //                             } else {
                //                                 $html .= "
                //                                     <a href='{$url}' target='_blank' class='flex items-center gap-2 p-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700'>
                //                                         <svg class='w-6 h-6 text-gray-500' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z' /></svg>
                //                                         <span class='text-sm'>" . e($fileName) . "</span>
                //                                     </a>
                //                                 ";
                //                             }
                //                         }
                //                         $html .= '</div>';
                //                         return new HtmlString($html);
                //                     })
                //                     ->visible(fn (?AssessmentScore $record) => $record && !$record->evidences->isEmpty()),
                //             ])
                //     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')->searchable()->sortable()->label('Lokasi'),
                Tables\Columns\TextColumn::make('assessor.name')->searchable()->sortable()->label('Asesor'), // Diubah dari user.name
                Tables\Columns\TextColumn::make('assignment_date')->date()->sortable()->label('Tgl Penugasan'),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable()->label('Batas Waktu')->placeholder('Tidak Ada Batasan'),
                Tables\Columns\TextColumn::make('status')->badge()->searchable()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'info',
                        'pending_review_admin' => 'warning',
                        'approved' => 'success',
                        'revision_needed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })->label('Status'),
                    Tables\Columns\TextColumn::make('final_score')
                ->label('Hasil Skor')
                ->numeric(3)
                ->placeholder('Belum ada skor')
                ->sortable(false), // Sorting pada accessor perlu query custom, nonaktifkan dulu

                Tables\Columns\TextColumn::make('created_at') // Kolom untuk sorting
                    ->dateTime()
                    ->sortable()
                    ->label('Dibuat Pada')
                    // ->toggleable(isToggledHiddenByDefault: true), 
            ])
            ->defaultSort('created_at', 'asc')
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
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            //     Tables\Actions\ViewAction::make(),
            //     Tables\Actions\DeleteAction::make(),
                
            // ])
            // ->actions([
            //     Action::make('review')
            //         ->label('Review & Aksi')
            //         ->icon('heroicon-o-eye')
            //         ->color('primary')
            //         ->url(fn (Assignment $record): string => self::getUrl('review', ['record' => $record]))
            //         ->visible(fn (Assignment $record): bool => in_array($record->status, ['completed', 'pending_review_admin', 'approved'])),
                
            //     Tables\Actions\EditAction::make()
            //         ->visible(fn (Assignment $record): bool => in_array($record->status, ['assigned', 'in_progress', 'revision_needed'])),
            // ])


            
            ->actions([
                // --- INI ADALAH AKSI BARU UNTUK MENGAKSES HALAMAN REVIEW ---
                Action::make('review')
                    ->label('Review & Aksi')
                    ->icon('heroicon-o-eye')
                    ->color('primary') // Warna tombol
                    // Mengarahkan ke rute 'review' yang kita daftarkan di getPages()
                    ->url(fn (Assignment $record): string => self::getUrl('review', ['record' => $record]))
                    // Tombol ini hanya muncul jika statusnya sudah selesai dari sisi asesor atau sudah disetujui
                    ->visible(fn (Assignment $record): bool => in_array($record->status, ['completed', 'pending_review_admin', 'approved'])),
                
                // Tombol Edit standar, sekarang hanya untuk status aktif
                Tables\Actions\EditAction::make()
                    ->visible(fn (Assignment $record): bool => in_array($record->status, ['assigned', 'in_progress', 'revision_needed'])),

                // Tombol View standar bisa kita hapus karena sudah digantikan oleh aksi 'review'
                // Tables\Actions\ViewAction::make(), 
            ])
    
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function canEdit(Model $record): bool
    {
        // Admin hanya bisa mengedit jika statusnya masih dalam alur kerja aktif.
        // Status final seperti 'completed', 'approved', atau 'cancelled' tidak bisa diedit lagi.
        $editableStatuses = ['assigned', 'in_progress', 'revision_needed', 'pending_review_admin'];

        return in_array($record->status, $editableStatuses);
    }

    // public static function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist
    //         ->schema([
    //             // Infolists\Components\Section::make('Informasi Penugasan')
    //             //     ->columns(3)
    //             //     ->schema([
    //             //         Infolists\Components\TextEntry::make('location.name')->label('Lokasi'),
    //             //         Infolists\Components\TextEntry::make('assessor.name')->label('Asesor'),
    //             //         Infolists\Components\TextEntry::make('status')->label('Status')->badge()
    //             //             ->color(fn (string $state): string => match ($state) {
    //             //                 'assigned' => 'primary',
    //             //                 'in_progress' => 'warning',
    //             //                 'completed' => 'info',
    //             //                 'pending_review_admin' => 'warning',
    //             //                 'approved' => 'success',
    //             //                 'revision_needed' => 'danger',
    //             //                 'cancelled' => 'gray',
    //             //                 default => 'gray',
    //             //             }),
    //             //         Infolists\Components\TextEntry::make('assignment_date')->date('d M Y')->label('Tgl Penugasan'),
    //             //         Infolists\Components\TextEntry::make('due_date')->date('d M Y')->label('Batas Waktu'),
    //             //         Infolists\Components\TextEntry::make('notes')->label('Catatan Penugasan (Admin)')->columnSpanFull()->visible(fn($state) => !empty($state)),
    //             //     ]),
    //             Infolists\Components\Section::make('Hasil Penilaian Indikator')
    //                 ->schema([
    //                     // Kita akan menggunakan custom view atau repeater untuk menampilkan skor
    //                     // Cara sederhana dengan RepeaterEntry jika relasi sudah benar
    //                     Infolists\Components\RepeatableEntry::make('assessmentScores')
    //                         ->label(false)
    //                         ->columns(1)
    //                         ->schema([
    //                             Infolists\Components\TextEntry::make('indicator.name')
    //                                 ->label('Indikator')
    //                                 ->html()
    //                                 ->formatStateUsing(fn (?AssessmentScore $record) => new HtmlString(
    //                                     "<strong>" . e($record?->indicator?->name ?? 'N/A') . "</strong>" .
    //                                     ($record?->indicator?->category ? "<br><small class='text-gray-500'>Kategori: " . e($record->indicator->category) . "</small>" : "") .
    //                                     ($record?->indicator?->scoring_criteria_text ? "<div class='mt-1 text-xs p-1 border rounded bg-gray-100 dark:bg-gray-800 dark:border-gray-700'>" . nl2br(e($record->indicator->scoring_criteria_text)) . "</div>" : "")
    //                                 )),
    //                             Infolists\Components\TextEntry::make('score')
    //                                 ->label('Skor Diberikan')
    //                                 ->badge()
    //                                 ->color('primary'),
    //                             Infolists\Components\TextEntry::make('indicator.weight') // Menampilkan bobot indikator
    //                                 ->label('Bobot Indikator')
    //                                 ->numeric(),
    //                                 // ->alignRight(),
    //                             Infolists\Components\TextEntry::make('assessor_notes')
    //                                 ->label('Catatan Asesor')
    //                                 ->visible(fn ($state) => !empty($state))
    //                                 ->columnSpanFull()
    //                                 ->html()
    //                                 ->formatStateUsing(fn ($state) => $state ? nl2br(e($state)) : '-'),
    //                             Infolists\Components\RepeatableEntry::make('evidences') // Menampilkan bukti
    //                                 ->label('Bukti Pendukung')
    //                                 ->columnSpanFull()
    //                                 ->visible(fn (?AssessmentScore $record) => $record && $record->evidences->count() > 0)
    //                                 ->schema([
    //                                     Infolists\Components\ImageEntry::make('file_path') // Untuk gambar
    //                                         ->label(false)
    //                                         ->disk('public')
    //                                         ->height(100)
    //                                         ->visible(fn ($state) => $state && Str::startsWith(Storage::disk('public')->mimeType($state), 'image/')),
    //                                     Infolists\Components\TextEntry::make('file_path') // Untuk file lain (link)
    //                                         ->label(false)
    //                                         ->url(fn ($state) => $state ? Storage::disk('public')->url($state) : null, true)
    //                                         ->formatStateUsing(fn ($state) => basename($state))
    //                                         ->visible(fn ($state) => $state && !Str::startsWith(Storage::disk('public')->mimeType($state), 'image/')),
    //                                 ])->grid(3), // Tampilkan bukti dalam grid
    //                         ])
    //                         ->columnSpanFull(),
    //                 ]),
    //         ]);
    // }

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
            'review' => Pages\ReviewAssignment::route('/{record}/review'),
        ];
    }
}
