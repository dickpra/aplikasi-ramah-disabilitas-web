<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Pages\Contracts\HasRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components as InfolistComponents;
use Filament\Forms\Components as FormComponents;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Assignment;
use App\Models\AssessmentScore;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\AssessmentApprovedNotification; // <-- 1. IMPORT NOTIFIKASI BARU



class ReviewAssignment extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AssignmentResource::class;
    protected static string $view = 'filament.resources.assignment-resource.pages.review-assignment';
    protected static ?string $title = 'Review Hasil Penilaian';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['location', 'assessor', 'assessmentScores.indicator', 'assessmentScores.evidences']);
        static::authorizeResourceAccess();
    }

    public function assignmentInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                InfolistComponents\Section::make('Informasi Penugasan')
                    ->columns(3)
                    ->schema([
                        InfolistComponents\TextEntry::make('location.name')->label('Lokasi'),
                        InfolistComponents\TextEntry::make('assessor.name')->label('Asesor'),
                        InfolistComponents\TextEntry::make('status')->label('Status')->badge()
                            ->color(fn ($state): string => match ($state ?? '') {
                                'assigned' => 'primary', 'in_progress' => 'warning', 'completed' => 'info',
                                'pending_review_admin' => 'warning', 'approved' => 'success', 'revision_needed' => 'danger',
                                'cancelled' => 'gray', default => 'secondary',
                            }),
                        InfolistComponents\TextEntry::make('assignment_date')->date('d M Y')->label('Tgl Penugasan'),
                        InfolistComponents\TextEntry::make('due_date')->date('d M Y')->label('Batas Waktu')->placeholder('Tidak Ada Batasan'),
                        InfolistComponents\TextEntry::make('updated_at')->dateTime('d M Y H:i')->label('Terakhir Disubmit'),
                        InfolistComponents\TextEntry::make('final_score')
                        ->label('Skor Akhir Penilaian')
                        ->weight('bold') // Buat tebal agar menonjol
                        ->size('lg')     // Buat ukuran font lebih besar
                        ->badge()
                        ->color('primary')
                        ->placeholder('Belum Dihitung'),
                    ]),

                InfolistComponents\Section::make('Hasil Penilaian Indikator oleh Asesor')
                    ->schema([
                        InfolistComponents\RepeatableEntry::make('assessmentScores')
                            ->label(false)
                            ->contained(false) // Membuat latar belakang repeater transparan
                            ->schema([
                                // Menggunakan Grid untuk menata layout per item indikator
                                InfolistComponents\Grid::make(3)->schema([
                                    // Info Indikator dan Catatan di kolom utama
                                    InfolistComponents\Grid::make(1)->columnSpan(2)->schema([
                                        InfolistComponents\TextEntry::make('indicator.name')
                                            ->label('Indikator')
                                            ->formatStateUsing(fn (?AssessmentScore $record) => $record?->indicator?->name)
                                            ->weight('bold'),
                                        InfolistComponents\TextEntry::make('indicator.category')
                                            ->label('Kategori')
                                            ->badge(),
                                        InfolistComponents\TextEntry::make('indicator.scoring_criteria_text')
                                            ->label('Kriteria Penilaian')
                                            ->formatStateUsing(fn ($state) => new HtmlString(nl2br(e($state))))
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'text-xs']),
                                        InfolistComponents\TextEntry::make('assessor_notes')
                                            ->label('Catatan dari Asesor')
                                            ->visible(fn ($state) => !empty($state))
                                            ->formatStateUsing(fn ($state): string => $state ? nl2br(e($state)) : '-'),
                                    ]),
                                    // Skor dan Bobot di kolom samping
                                    InfolistComponents\Grid::make(1)->columnSpan(1)->schema([
                                        InfolistComponents\TextEntry::make('score')->label('Skor Diberikan')->badge()->size('lg'),
                                        InfolistComponents\TextEntry::make('indicator.weight')->label('Bobot Indikator')->badge()->color('gray'),
                                    ]),
                                ]),
                                
                                // Repeater untuk Bukti Pendukung
                                InfolistComponents\RepeatableEntry::make('evidences')
                                    ->label('Bukti Pendukung')
                                    ->columnSpanFull()
                                    ->contained(true)
                                    ->visible(fn (AssessmentScore $record) => $record->evidences->count() > 0)
                                    ->columns(['sm' => 2, 'md' => 3, 'lg' => 4, 'xl' => 5])
                                    ->schema([
                                        // UNTUK FILE GAMBAR
                                        InfolistComponents\ImageEntry::make('file_path')
                                            ->label(false)
                                            ->disk('public')
                                            ->height(100)
                                            ->square()
                                            // Menambahkan URL agar gambar bisa diklik
                                            ->url(fn (?string $state): ?string => $state ? Storage::disk('public')->url($state) : null)
                                            ->openUrlInNewTab() // Buka di tab baru saat diklik
                                            ->visible(function ($state): bool {
                                                if (!$state) return false;
                                                try { return Str::startsWith(Storage::disk('public')->mimeType($state), 'image/'); }
                                                catch (\Exception $e) { return false; }
                                            }),

                                        // UNTUK FILE DOKUMEN/LAINNYA
                                        InfolistComponents\TextEntry::make('file_path') // Sumber data utama adalah path file
                                            ->label(false)
                                            // Tampilkan nama file asli atau basename dari path
                                            ->state(fn (Model $record): string => $record->original_file_name ?? basename($record->file_path))
                                            // Jadikan teks ini sebagai link ke URL file
                                            ->url(fn (Model $record): ?string => $record->file_path ? Storage::disk('public')->url($record->file_path) : null, true)
                                            ->icon('heroicon-o-document-arrow-down')
                                            ->color('primary')
                                            ->visible(function ($state, Model $record): bool {
                                                if (!$record->file_path) return false;
                                                try { return !Str::startsWith(Storage::disk('public')->mimeType($record->file_path), 'image/'); }
                                                catch (\Exception $e) { return true; } // Tampilkan sebagai link jika tipe file tidak dikenali
                                            }),
                                    ]),
                                // Garis Pemisah
                                InfolistComponents\TextEntry::make('separator')->label(false)->columnSpanFull()->view('infolists.components.hr-separator'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveAssessment')
                ->label('Setujui Penilaian')->color('success')->icon('heroicon-o-check-badge')
                ->requiresConfirmation()->modalDescription('Apakah Anda yakin ingin menyetujui hasil penilaian ini? Status akan menjadi "Approved" dan final.')
                ->action(function () {
                    $this->record->update(['status' => 'approved']);
                    Notification::make()->success()->title('Penilaian Disetujui')->send();
                    // 2. Kirim notifikasi ke asesor
                    if ($this->record->assessor) {
                        $this->record->assessor->notify(new AssessmentApprovedNotification($this->record));
                    }
                    
                    // 3. Tampilkan notifikasi pop-up untuk admin
                    Notification::make()
                        ->success()
                        ->title('Penilaian Disetujui')
                        ->body('Status penugasan telah diubah dan skor akhir lokasi telah diperbarui.')
                        ->send();
                    
                    // 4. Redirect kembali ke halaman index
                    $this->redirect(AssignmentResource::getUrl('index'));
                })->visible(fn (): bool => in_array($this->record->status, ['completed', 'pending_review_admin'])),
            Action::make('requestRevision')
                ->label('Minta Revisi')->color('danger')->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->form([
                    FormComponents\Textarea::make('revision_notes')->label('Catatan untuk Revisi')->required()->helperText('Tuliskan catatan untuk asesor mengenai bagian mana yang perlu diperbaiki.'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'revision_needed',
                        'notes' => ($this->record->notes ? $this->record->notes . "\n\n" : "") . "[REVISI " . now()->format('d/m/Y H:i') . "]:\n" . $data['revision_notes'],
                    ]);
                    Notification::make()->success()->title('Permintaan Revisi Terkirim')->send();
                    $this->redirect(AssignmentResource::getUrl('index'));
                })->visible(fn (): bool => in_array($this->record->status, ['completed', 'pending_review_admin'])),
        ];
    }
}