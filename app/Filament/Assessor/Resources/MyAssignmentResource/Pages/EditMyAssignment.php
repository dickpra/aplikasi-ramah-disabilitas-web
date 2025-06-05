<?php

namespace App\Filament\Assessor\Resources\MyAssignmentResource\Pages;

use App\Filament\Assessor\Resources\MyAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Assignment;
use App\Models\Indicator;
use App\Models\AssessmentScore;
use App\Models\AssessmentEvidence;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EditMyAssignment extends EditRecord
{
    protected static string $resource = MyAssignmentResource::class;
    
    public array $newlyUploadedFilesMetadata = []; // Untuk menyimpan metadata file baru sementara

    protected function getFormId(): ?string
    {
        return 'editAssignmentFormAssessor';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->getRecord();
        Log::info("[MUTATE_FILL] Memulai untuk Assignment ID: " . ($assignment?->id ?? 'NULL'));

        if (!$assignment || !$assignment->location) {
            Log::error("[MUTATE_FILL] Assignment atau Lokasi tidak valid.");
            $data['indicator_scores_repeater'] = [];
            return $data;
        }
        $location = $assignment->location;

        $relevantIndicators = Indicator::where(function ($query) use ($location) {
            $query->where('target_location_type', $location->location_type)
                  ->orWhere('target_location_type', 'all');
        })->where('is_active', true)->orderBy('category')->orderBy('id')->get();
        Log::info("[MUTATE_FILL] Ditemukan {$relevantIndicators->count()} indikator relevan.");

        // Eager load relasi yang ada untuk efisiensi
        $assignment->load(['assessmentScores.indicator', 'assessmentScores.evidences']);
        $existingScoresByIndicatorId = $assignment->assessmentScores->keyBy('indicator_id');

        $indicatorScoresData = [];
        foreach ($relevantIndicators as $indicator) {
            $scoreRecord = $existingScoresByIndicatorId->get($indicator->id);
            if (!$scoreRecord) {
                 $scoreRecord = $assignment->assessmentScores()->firstOrCreate(
                    ['indicator_id' => $indicator->id],
                    ['score' => null, 'assessor_notes' => null]
                );
                // Untuk item baru, relasi indicator dan evidences perlu di-set agar bisa diakses di form
                $scoreRecord->setRelation('indicator', $indicator); 
                $scoreRecord->setRelation('evidences', collect()); 
            }
            
            $indicatorScoresData[] = [
                'indicator_id' => $indicator->id,
                'assessment_score_id' => $scoreRecord->id,
                'score' => $scoreRecord->score,
                'assessor_notes' => $scoreRecord->assessor_notes,
                'evidences_upload' => $scoreRecord->evidences->pluck('file_path')->all(),
            ];
        }
        
        $data['indicator_scores_repeater'] = $indicatorScoresData;
        Log::debug("[MUTATE_FILL] Data yang disiapkan untuk repeater: ", $data['indicator_scores_repeater']);
        if ($assignment->status === 'assigned') {
            // Anda bisa uncomment ini jika ingin status otomatis berubah saat form dibuka pertama kali
            $assignment->status = 'in_progress';
            $assignment->saveQuietly();
            $this->refreshFormData(['status']); 
            $data['status'] = 'in_progress'; 
        }
        // if ($assignment->status === 'assigned') {
        //     $data['status'] = 'in_progress';
        // } else {
        //     $data['status'] = $assignment->status;
        // }
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Assignment $assignment */
        $assignment = $record;

        DB::beginTransaction();
        try {
            Log::info("[HANDLE_UPDATE] Memulai untuk Assignment ID {$assignment->id}.");
            // Log::debug("[HANDLE_UPDATE] Data form diterima:", $data);

            if (isset($data['status']) && $data['status'] !== $assignment->getOriginal('status') && in_array($assignment->getOriginal('status'), MyAssignmentResource::ACTIVE_STATUSES) ) {
                 $assignment->status = $data['status'];
            }
            $assignment->save();

            $repeaterData = $data['indicator_scores_repeater'] ?? [];
            Log::info("[HANDLE_UPDATE] Jumlah item repeater: " . count($repeaterData));

            foreach ($repeaterData as $itemData) {
                if (!isset($itemData['indicator_id']) || !isset($itemData['assessment_score_id'])) {
                    Log::warning("[HANDLE_UPDATE] Item repeater tidak memiliki indicator_id atau assessment_score_id.", $itemData);
                    continue;
                }

                $assessmentScore = AssessmentScore::find($itemData['assessment_score_id']);
                if (!$assessmentScore) {
                     Log::error("[HANDLE_UPDATE] AssessmentScore ID {$itemData['assessment_score_id']} tidak ditemukan untuk Indicator ID {$itemData['indicator_id']}. Ini seharusnya tidak terjadi jika mutateFormDataBeforeFill bekerja.");
                     continue; // Lewati item ini jika AssessmentScore tidak ada
                }
                
                $assessmentScore->update([
                    'score' => $itemData['score'] ?? null,
                    'assessor_notes' => $itemData['assessor_notes'] ?? null,
                ]);
                Log::info("[HANDLE_UPDATE] AssessmentScore ID {$assessmentScore->id} diupdate. Skor: {$itemData['score']}");

                // PENANGANAN BUKTI (EVIDENCES)
                $submittedFilePaths = $itemData['evidences_upload'] ?? [];
                if (!is_array($submittedFilePaths)) $submittedFilePaths = [];
                Log::info("[HANDLE_UPDATE] AssessmentScore ID {$assessmentScore->id}: Submitted file paths (evidences_upload): ", $submittedFilePaths);

                $existingEvidenceRecords = $assessmentScore->evidences()->get();
                
                // 1. Hapus bukti yang ada di DB tapi tidak ada lagi di $submittedFilePaths
                foreach ($existingEvidenceRecords as $existingEvidence) {
                    if (!in_array($existingEvidence->file_path, $submittedFilePaths)) {
                        Log::info("[HANDLE_UPDATE] Menghapus bukti: {$existingEvidence->file_path} (ID: {$existingEvidence->id})");
                        if (Storage::disk('public')->exists($existingEvidence->file_path)) {
                            Storage::disk('public')->delete($existingEvidence->file_path);
                        }
                        $existingEvidence->delete();
                    }
                }

                // 2. Tambah bukti baru
                foreach ($submittedFilePaths as $submittedPath) {
                    if (is_string($submittedPath) && !empty($submittedPath)) {
                        $isAlreadyInDb = $assessmentScore->evidences()->where('file_path', $submittedPath)->exists();
                        if (!$isAlreadyInDb) {
                            // Ini file baru. Ambil metadatanya dari properti yang kita stash.
                            $metadata = $this->newlyUploadedFilesMetadata[$submittedPath] ?? [];
                            Log::info("[HANDLE_UPDATE] Membuat AssessmentEvidence: {$submittedPath}, Metadata: ", $metadata);
                            AssessmentEvidence::create([
                                'assessment_score_id' => $assessmentScore->id,
                                'file_path' => $submittedPath,
                                'original_file_name' => $metadata['original_file_name'] ?? basename($submittedPath),
                                'file_mime_type' => $metadata['file_mime_type'] ?? null,
                                'file_size' => $metadata['file_size'] ?? null,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            Log::info("[HANDLE_UPDATE] Transaksi BERHASIL untuk Assignment ID: {$assignment->id}");
            // Notifikasi sukses akan ditangani oleh getSavedNotification() atau action submitAssessment
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[HANDLE_UPDATE] EXCEPTION: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            Notification::make()->danger()->title('Gagal Menyimpan Data Penilaian')->body('Terjadi kesalahan: '.$e->getMessage())->send();
        }
        // Reset metadata yang di-stash setelah berhasil atau gagal
        $this->newlyUploadedFilesMetadata = [];
        return $assignment;
    }

    protected function getHeaderActions(): array
    {
        /** @var Assignment $record */
        $record = $this->getRecord();
        return [
            Actions\Action::make('saveProgress')
                ->label('Simpan Progres')
                ->action('save') 
                ->color('gray')
                ->visible(fn (): bool => in_array($this->getRecord()->status, MyAssignmentResource::ACTIVE_STATUSES)),

            Actions\Action::make('submitAssessment')
                ->label('Selesaikan & Kirim Penilaian')
                ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                ->modalHeading('Kirim Hasil Penilaian?')->modalDescription('Pastikan semua indikator sudah terisi dengan benar...')
                ->action(function () {
                    $this->save(); 
                    $currentRecord = $this->getRecord();
                    $currentRecord->refresh()->load('assessmentScores'); 
                    
                    $location = $currentRecord->location;
                    if (!$location) { Notification::make()->danger()->title('Error')->body('Lokasi tidak ditemukan.')->send(); return; }
                    
                    $relevantIndicatorIds = Indicator::where(function ($q) use ($location) {
                        $q->where('target_location_type', $location->location_type)->orWhere('target_location_type', 'all');
                    })->where('is_active', true)->pluck('id');

                    $scoredCount = $currentRecord->assessmentScores()
                        ->whereIn('indicator_id', $relevantIndicatorIds)
                        ->where(function($q){ $q->whereNotNull('score')->where('score', '!=', ''); })
                        ->count();
                    
                    if ($scoredCount < $relevantIndicatorIds->count()) {
                        Notification::make()->danger()->title('Penilaian Belum Lengkap')
                            ->body("Harap isi skor untuk semua ({$relevantIndicatorIds->count()}) indikator relevan. Baru terisi {$scoredCount}.")
                            ->send();
                        return;
                    }

                    $currentRecord->status = 'completed';
                    $currentRecord->save(); // Simpan perubahan status
                    Notification::make()->success()->title('Penilaian Terkirim')->body('Hasil penilaian berhasil dikirim.')->send();
                    $this->redirect(MyAssignmentResource::getUrl('index'));
                })
                ->visible(fn (): bool => in_array($this->getRecord()->status, MyAssignmentResource::ACTIVE_STATUSES)),
        ];
    }

    protected function getFormActions(): array
    {
        /** @var \App\Models\Assignment $record */
        $record = $this->getRecord();
        if (!in_array($record->status, MyAssignmentResource::ACTIVE_STATUSES)) {
            return [];
        }
        return parent::getFormActions();
    }

     protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Progres Disimpan')
            ->body('Perubahan pada penilaian Anda telah berhasil disimpan.');
    }
}