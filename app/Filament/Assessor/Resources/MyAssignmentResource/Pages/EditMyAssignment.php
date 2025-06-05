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
// Pastikan UploadedFile di-import jika Anda melakukan type-hinting atau pengecekan instanceof
// use Illuminate\Http\UploadedFile; 

class EditMyAssignment extends EditRecord
{
    protected static string $resource = MyAssignmentResource::class;

    // Memberikan ID unik ke form jika diperlukan oleh elemen HTML kustom (seperti checkbox)
    protected function getFormId(): ?string
    {
        return 'edit-assignment-form';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->getRecord();
        Log::info("[MUTATE_FILL] Memulai untuk Assignment ID: " . ($assignment?->id ?? 'NULL'));

        if (!$assignment || !$assignment->location) {
            Log::error("[MUTATE_FILL] Assignment atau Lokasi tidak valid untuk Assignment ID: " . ($assignment?->id ?? 'NULL'));
            // Kembalikan array dengan kunci repeater kosong agar tidak error jika repeater mengharapkannya
            $data['indicator_scores_repeater'] = []; 
            return $data;
        }
        $location = $assignment->location;
        Log::info("[MUTATE_FILL] Lokasi: ID {$location->id}, Tipe: '{$location->location_type}'");

        $relevantIndicators = Indicator::where(function ($query) use ($location) {
            $query->where('target_location_type', $location->location_type)
                  ->orWhere('target_location_type', 'all');
        })->where('is_active', true)->orderBy('category')->orderBy('id')->get();
        Log::info("[MUTATE_FILL] Ditemukan {$relevantIndicators->count()} indikator relevan.");

        foreach ($relevantIndicators as $indicator) {
            $assignment->assessmentScores()->firstOrCreate(
                ['indicator_id' => $indicator->id],
                ['score' => null, 'assessor_notes' => null]
            );
        }
        
        $this->record->load(['assessmentScores.indicator', 'assessmentScores.evidences']);

        $indicatorScoresData = [];
        if ($this->record->assessmentScores) { // Pastikan assessmentScores tidak null
            foreach ($this->record->assessmentScores as $score) {
                if (!is_numeric($score->id)) { // Seharusnya ID selalu numerik setelah firstOrCreate
                    Log::warning("[MUTATE_FILL] AssessmentScore memiliki ID non-numerik atau tidak valid: " . $score->id . ". Ini seharusnya tidak terjadi.");
                }
                // Kunci array di sini adalah ID dari AssessmentScore, yang akan digunakan Filament
                // untuk mencocokkan item repeater dengan data yang ada jika repeater menggunakan relationship.
                // Namun, karena kita akan mengisi repeater secara manual, kita buat array data.
                $indicatorScoresData[] = [ // Repeater yang tidak pakai ->relationship() butuh array numerik
                    'indicator_id' => $score->indicator_id,
                    'assessment_score_id' => $score->id, 
                    'score' => $score->score,
                    'assessor_notes' => $score->assessor_notes,
                    'evidences_upload' => $score->evidences->pluck('file_path')->all(),
                ];
                $data['indicator_scores_repeater'] = $indicatorScoresData;

            }
        }
        
        $data['indicator_scores_repeater'] = $indicatorScoresData; // Data untuk repeater
        Log::debug("[MUTATE_FILL] Data yang disiapkan untuk form repeater: ", $data['indicator_scores_repeater']);

        if ($assignment->status === 'assigned') {
            // Anda bisa uncomment ini jika ingin status otomatis berubah saat form dibuka pertama kali
            $assignment->status = 'in_progress';
            $assignment->saveQuietly();
            $this->refreshFormData(['status']); 
            $data['status'] = 'in_progress'; 
        }
        $data['status'] = $assignment->status; // Untuk field status utama di form (jika ada)

        return $data;
    }
    
    // Mengganti handleRecordUpdate untuk kontrol penuh atas penyimpanan Repeater
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Assignment $assignment */
        $assignment = $record;

        DB::beginTransaction();
        try {
            // 1. Update Assignment utama (misalnya status, jika diubah dari form)
            if (isset($data['status']) && $data['status'] !== $assignment->status && in_array($assignment->status, MyAssignmentResource::ACTIVE_STATUSES) ) {
                 Log::info("[HANDLE_UPDATE] Mengubah status Assignment ID {$assignment->id} dari {$assignment->status} ke {$data['status']}");
                 $assignment->status = $data['status'];
                 // Catatan lain untuk assignment bisa diupdate di sini jika ada fieldnya
                 $assignment->save();
            }

            $repeaterData = $data['indicator_scores_repeater'] ?? [];
            Log::info("[HANDLE_UPDATE] Data repeater diterima untuk diproses: " . count($repeaterData) . " item.");
            // Log::debug("[HANDLE_UPDATE] Isi data repeater:", $repeaterData);


            $processedAssessmentScoreIds = [];

            foreach ($repeaterData as $itemData) {
                if (!isset($itemData['indicator_id'])) {
                    Log::warning("[HANDLE_UPDATE] Item repeater tidak memiliki indicator_id.", $itemData);
                    continue;
                }

                // Cari atau buat AssessmentScore berdasarkan assignment_id dan indicator_id
                // Jika ada assessment_score_id di itemData, gunakan itu untuk update.
                $assessmentScore = null;
                if (!empty($itemData['assessment_score_id'])) {
                    $assessmentScore = AssessmentScore::find($itemData['assessment_score_id']);
                }
                
                // Jika tidak ditemukan dengan ID atau ID tidak ada, coba firstOrCreate dengan indicator_id
                if (!$assessmentScore) {
                    $assessmentScore = $assignment->assessmentScores()->firstOrCreate(
                        ['indicator_id' => $itemData['indicator_id']],
                        [
                            'score' => $itemData['score'] ?? null,
                            'assessor_notes' => $itemData['assessor_notes'] ?? null,
                        ]
                    );
                     Log::info("[HANDLE_UPDATE] AssessmentScore DIBUAT untuk Indicator ID {$itemData['indicator_id']} menjadi ID: {$assessmentScore->id}");
                } else {
                    $assessmentScore->update([
                        'score' => $itemData['score'] ?? null,
                        'assessor_notes' => $itemData['assessor_notes'] ?? null,
                    ]);
                    Log::info("[HANDLE_UPDATE] AssessmentScore ID {$assessmentScore->id} DIUPDATE. Skor: {$itemData['score']}");
                }
                $processedAssessmentScoreIds[] = $assessmentScore->id;


                // PENANGANAN BUKTI (EVIDENCES)
                $submittedFilePaths = $itemData['evidences_upload'] ?? [];
                if (!is_array($submittedFilePaths)) $submittedFilePaths = [];
                Log::info("[HANDLE_UPDATE] AssessmentScore ID {$assessmentScore->id}: Submitted file paths untuk 'evidences_upload': ", $submittedFilePaths);

                $existingEvidenceRecords = $assessmentScore->evidences()->get();
                $currentDbFilePaths = $existingEvidenceRecords->pluck('file_path')->toArray();

                // Hapus bukti yang tidak ada lagi di $submittedFilePaths (karena FileUpload + appendFiles mengirim state akhir)
                foreach ($existingEvidenceRecords as $existingEvidence) {
                    if (!in_array($existingEvidence->file_path, $submittedFilePaths)) {
                        Log::info("[HANDLE_UPDATE] Menghapus bukti: {$existingEvidence->file_path} dari AssessmentScore ID: {$assessmentScore->id}");
                        if (Storage::disk('public')->exists($existingEvidence->file_path)) {
                            Storage::disk('public')->delete($existingEvidence->file_path);
                        }
                        $existingEvidence->delete();
                    }
                }

                // Tambah bukti baru
                foreach ($submittedFilePaths as $submittedPath) {
                    if (is_string($submittedPath) && !empty($submittedPath)) {
                        // Cek apakah path ini sudah ada untuk assessment_score_id ini di DB
                        $isAlreadyInDb = $assessmentScore->evidences()->where('file_path', $submittedPath)->exists();
                        if (!$isAlreadyInDb) {
                            Log::info("[HANDLE_UPDATE] Membuat AssessmentEvidence baru: {$submittedPath} untuk AssessmentScore ID: {$assessmentScore->id}");
                            AssessmentEvidence::create([
                                'assessment_score_id' => $assessmentScore->id,
                                'file_path' => $submittedPath,
                                // 'original_file_name' => ..., // Anda perlu mekanisme sendiri untuk ini
                            ]);
                        }
                    }
                }

                // Tangani penghapusan bukti lama yang dicentang dari request (jika ada)
                // 'delete_evidences' adalah array asosiatif [assessment_score_id => [evidence_id1, evidence_id2]]
                $evidencesMarkedForDeletion = request()->input("delete_evidences.{$assessmentScore->id}", []);
                if (!empty($evidencesMarkedForDeletion) && is_array($evidencesMarkedForDeletion)) {
                    foreach ($evidencesMarkedForDeletion as $evidenceIdToDelete) {
                         $evidence = AssessmentEvidence::where('id', $evidenceIdToDelete)
                                                     ->where('assessment_score_id', $assessmentScore->id)
                                                     ->first();
                        if ($evidence) {
                            Log::info("[HANDLE_UPDATE] Menghapus bukti (via checkbox) ID: {$evidence->id} dengan path: {$evidence->file_path}");
                            if (Storage::disk('public')->exists($evidence->file_path)) {
                                Storage::disk('public')->delete($evidence->file_path);
                            }
                            $evidence->delete();
                        }
                    }
                }

            } // End foreach $repeaterData

            // Opsional: Hapus AssessmentScore yang tidak lagi relevan (jika daftar indikator bisa berubah)
            // $assignment->assessmentScores()->whereNotIn('id', $processedAssessmentScoreIds)->delete();

            DB::commit();
            Log::info("[HANDLE_UPDATE] Transaksi BERHASIL untuk Assignment ID: {$assignment->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[HANDLE_UPDATE] EXCEPTION: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            Notification::make()->danger()->title('Gagal Menyimpan Data Penilaian')->body('Terjadi kesalahan: '.$e->getMessage())->send();
        }
        return $assignment;
    }

    // afterSave tidak kita gunakan lagi untuk logika utama penyimpanan bukti, karena sudah di handleRecordUpdate
    // protected function afterSave(): void {}


    protected function getHeaderActions(): array
    {
        /** @var Assignment $record */
        $record = $this->getRecord();
        return [
            Actions\Action::make('saveProgress')
                ->label('Simpan Progres')
                ->action('save') 
                ->color('gray')
                ->visible(fn (): bool => in_array($record->status, MyAssignmentResource::ACTIVE_STATUSES)),

            Actions\Action::make('submitAssessment')
                ->label('Selesaikan & Kirim Penilaian')
                ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                ->modalHeading('Kirim Hasil Penilaian?')->modalDescription('Pastikan semua indikator sudah terisi dengan benar...')
                ->action(function (Assignment $record) { // $record di sini adalah instance yang benar
                    $this->save(); // Panggil save untuk memproses handleRecordUpdate
                    $record->refresh()->load('assessmentScores'); 
                    
                    $location = $record->location;
                    if (!$location) { /* ... error ... */ return; }
                    $relevantIndicatorIds = Indicator::where(function ($q) use ($location) {
                        $q->where('target_location_type', $location->location_type)->orWhere('target_location_type', 'all');
                    })->where('is_active', true)->pluck('id');

                    $scoredCount = $record->assessmentScores()
                        ->whereIn('indicator_id', $relevantIndicatorIds)
                        ->where(function($q){ 
                            $q->whereNotNull('score')->where('score', '!=', '');
                        })->count();
                    
                    if ($scoredCount < $relevantIndicatorIds->count()) {
                        Notification::make()->danger()->title('Penilaian Belum Lengkap')
                            ->body("Harap isi skor untuk semua ({$relevantIndicatorIds->count()}) indikator relevan. Baru terisi {$scoredCount}.")
                            ->send();
                        return;
                    }

                    $record->status = 'completed';
                    $record->save(); // Simpan perubahan status
                    Notification::make()->success()->title('Penilaian Terkirim')->body('Hasil penilaian berhasil dikirim.')->send();
                    $this->redirect(MyAssignmentResource::getUrl('index'));
                })
                ->visible(fn (): bool => in_array($record->status, MyAssignmentResource::ACTIVE_STATUSES)),
        ];
    }

    protected function getFormActions(): array
    {
        /** @var \App\Models\Assignment $record */
        $record = $this->getRecord();
        if (!in_array($record->status, MyAssignmentResource::ACTIVE_STATUSES)) {
            return [];
        }
        // Jika ingin menyembunyikan tombol save & cancel standar di footer jika sudah ada 'Simpan Progres' di header
        // return []; 
        return parent::getFormActions();
    }

     protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Progres Disimpan')
            ->body('Perubahan pada penilaian telah berhasil disimpan.');
    }
}