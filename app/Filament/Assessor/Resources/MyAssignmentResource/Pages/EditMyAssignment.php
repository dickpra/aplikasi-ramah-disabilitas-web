<?php

namespace App\Filament\Assessor\Resources\MyAssignmentResource\Pages;

use App\Filament\Assessor\Resources\MyAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Assignment;
use App\Models\Indicator;
use App\Models\AssessmentScore;
use App\Models\AssessmentEvidence; // Pastikan ini di-import
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;      // Untuk transaksi database
use Illuminate\Support\Facades\Storage; // Untuk manajemen file jika diperlukan
use Illuminate\Http\UploadedFile;     // Untuk type hint FileUpload
use Illuminate\Support\Facades\Log; // Penting untuk debugging



class EditMyAssignment extends EditRecord
{
    protected static string $resource = MyAssignmentResource::class;

    // Mempersiapkan data sebelum form diisi
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Assignment $assignment */
        $assignment = $this->getRecord(); // Mengambil record Assignment yang sedang diedit

        Log::info("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Memulai untuk Assignment ID: " . ($assignment?->id ?? 'NULL'));

        if (!$assignment) {
            Log::error("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Gagal mendapatkan record Assignment.");
            return $data;
        }

        if (!$assignment->location) {
            Log::error("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Record Assignment ID {$assignment->id} tidak memiliki relasi Lokasi yang valid.");
            return $data;
        }

        $location = $assignment->location;
        Log::info("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Lokasi terkait: ID {$location->id}, Tipe: '{$location->location_type}'");

        // Ambil indikator yang relevan untuk tipe lokasi ini dan aktif
        $relevantIndicators = Indicator::where(function ($query) use ($location) {
            $query->where('target_location_type', $location->location_type)
                  ->orWhere('target_location_type', 'all'); // Indikator yang berlaku untuk semua tipe lokasi
        })->where('is_active', true)->orderBy('category')->orderBy('id')->get();

        Log::info("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Ditemukan {$relevantIndicators->count()} indikator relevan.");

        if ($relevantIndicators->isNotEmpty()) {
            foreach ($relevantIndicators as $indicator) {
                // Pastikan ada record AssessmentScore untuk setiap indikator yang relevan.
                // Ini akan membuat item di repeater untuk setiap indikator.
                // 'assignment_id' akan otomatis terisi karena kita memanggilnya melalui relasi '$assignment->assessmentScores()'
                $scoreRecord = $assignment->assessmentScores()->firstOrCreate(
                    ['indicator_id' => $indicator->id], // Kunci untuk mencari atau membuat
                    ['score' => null, 'assessor_notes' => null] // Nilai default jika baru dibuat
                );
                Log::info("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Memastikan AssessmentScore untuk Indicator ID: {$indicator->id}. Hasil AssessmentScore ID: {$scoreRecord->id}, Assignment ID: {$scoreRecord->assignment_id}");
            }
        } else {
            Log::warning("[AssessorPanel] EditMyAssignment - mutateFormDataBeforeFill: Tidak ada indikator relevan yang ditemukan untuk lokasi tipe '{$location->location_type}'. Repeater mungkin kosong.");
        }

        // Setelah memastikan AssessmentScore records ada, kita tidak perlu secara manual mengisi $data['indicator_scores']
        // karena Repeater dikonfigurasi dengan ->relationship('assessmentScores').
        // Filament akan mengambilnya dari relasi $this->record->assessmentScores yang sudah di-refresh.
        // Namun, untuk memastikan data terbaru ter-load di record sebelum form render:
        $this->record->load('assessmentScores.indicator'); // Eager load indicator untuk setiap score

        // Update status tugas jika baru dibuka oleh asesor
        if ($assignment->status === 'assigned') {
            // Anda bisa memilih untuk otomatis mengubah status di sini, atau biarkan asesor melakukannya via field di form
            // $assignment->status = 'in_progress';
            // $assignment->saveQuietly(); // Simpan tanpa memicu event jika tidak perlu
            // $this->refreshFormData(['status']); // Jika ada field 'status' di form utama yang perlu diupdate
            // $data['status'] = 'in_progress';
        }
        // $data['status'] = $assignment->status; // Jika ada field status di form utama

        return $data; // Kembalikan array $data (bisa jadi tidak dimodifikasi signifikan di sini jika repeater pakai relationship)
    }

    // Menangani penyimpanan data setelah form utama (Assignment & AssessmentScores) disimpan oleh Filament
    protected function afterSave(): void
    {
        /** @var Assignment $assignment */
        $assignment = $this->getRecord();
        $formData = $this->form->getState(); // Mengambil semua data dari form yang sudah divalidasi

        if (isset($formData['indicator_scores']) && is_array($formData['indicator_scores'])) {
            DB::beginTransaction();
            try {
                foreach ($formData['indicator_scores'] as $assessmentScoreId => $scoreData) {
                    $assessmentScore = AssessmentScore::find($assessmentScoreId);
                    if ($assessmentScore) {
                        // Tangani upload file bukti (evidences)
                        if (isset($scoreData['evidences']) && is_array($scoreData['evidences'])) {
                            // Strategi: Hapus bukti lama, lalu tambahkan yang baru
                            // Atau Anda bisa melakukan diff jika ingin mempertahankan beberapa bukti lama.
                            // Untuk kesederhanaan, kita hapus semua dan buat baru dari yang diupload.
                            $assessmentScore->evidences()->each(function ($evidence) {
                                // Storage::disk('public')->delete($evidence->file_path); // Hapus file fisik
                                $evidence->delete(); // Hapus record dari DB
                            });

                            foreach ($scoreData['evidences'] as $filePath) {
                                // $filePath di sini adalah path yang dikembalikan oleh FileUpload setelah file disimpan.
                                // FileUpload biasanya menyimpan ke disk dan mengembalikan path relatif.
                                if (is_string($filePath)) { // Pastikan itu string path
                                    AssessmentEvidence::create([
                                        'assessment_score_id' => $assessmentScore->id,
                                        'file_path' => $filePath,
                                        // Anda mungkin perlu mengambil original_file_name, mime_type, size
                                        // dari proses upload FileUpload jika ingin menyimpannya.
                                        // Ini mungkin memerlukan kustomisasi lebih lanjut pada bagaimana FileUpload
                                        // menyimpan atau mengembalikan data, atau mengambilnya dari request.
                                        // 'original_file_name' => 'nama_file_asli_dari_upload.jpg',
                                        // 'file_mime_type' => 'image/jpeg',
                                        // 'file_size' => 12345,
                                    ]);
                                }
                            }
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Notification::make()
                    ->danger()
                    ->title('Gagal Menyimpan Bukti')
                    ->body('Terjadi kesalahan saat menyimpan file bukti: ' . $e->getMessage())
                    ->send();
            }
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            // Tombol "Simpan Progres" akan memanggil metode 'save' standar dari EditRecord
            // yang akan memicu handleRecordUpdate dan afterSave.
            Actions\Action::make('saveProgress')
                ->label('Simpan Progres')
                ->action('save') // Ini adalah action bawaan untuk menyimpan
                ->color('gray')
                ->visible(fn (Assignment $record): bool => in_array($record->status, ['assigned', 'in_progress'])),

            Actions\Action::make('submitAssessment')
                ->label('Selesaikan & Kirim Penilaian')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Kirim Hasil Penilaian?')
                ->modalDescription('Pastikan semua indikator sudah terisi dengan benar. Setelah dikirim, Anda mungkin tidak dapat mengubahnya lagi.')
                ->action(function (Assignment $record) {
                    // 1. Simpan dulu semua perubahan terakhir dari form
                    // Panggilan $this->save() akan menjalankan handleRecordUpdate() dan afterSave()
                    $this->save();

                    // 2. Refresh record untuk mendapatkan data terbaru setelah save
                    $record->refresh();
                    
                    // 3. Validasi Akhir (Contoh: semua indikator relevan harus punya skor)
                    $location = $record->location;
                    if (!$location) { // Guard clause jika lokasi tidak ada
                        Notification::make()->danger()->title('Error')->body('Lokasi tidak ditemukan untuk penugasan ini.')->send();
                        return;
                    }

                    $relevantIndicatorIds = Indicator::where(function ($query) use ($location) {
                        $query->where('target_location_type', $location->location_type)
                              ->orWhere('target_location_type', 'all');
                    })->where('is_active', true)->pluck('id');

                    $scoredIndicatorIdsCount = $record->assessmentScores()
                                                ->whereIn('indicator_id', $relevantIndicatorIds)
                                                ->whereNotNull('score') // Pastikan ada skornya (bukan null)
                                                ->count();
                    
                    if ($scoredIndicatorIdsCount < $relevantIndicatorIds->count()) {
                        Notification::make()
                            ->danger()
                            ->title('Penilaian Belum Lengkap')
                            ->body("Harap isi skor untuk semua ({$relevantIndicatorIds->count()}) indikator yang relevan sebelum mengirim. Baru terisi {$scoredIndicatorIdsCount}.")
                            ->send();
                        return;
                    }

                    // 4. Ubah status assignment
                    $record->status = 'completed'; // Atau 'pending_review_admin' sesuai alur Anda
                    $record->save();

                    Notification::make()
                        ->success()
                        ->title('Penilaian Terkirim')
                        ->body('Hasil penilaian untuk lokasi ' . $record->location->name . ' telah berhasil dikirim.')
                        ->send();
                    
                    // 5. Redirect ke halaman index
                    $this->redirect(MyAssignmentResource::getUrl('index'));
                })
                ->visible(fn (Assignment $record): bool => in_array($record->status, ['assigned', 'in_progress'])),
        ];
    }

    // Opsional: Ubah judul notifikasi default saat menyimpan progres
    protected function getSavedNotification(): ?Notification
    {
        // Jika notifikasi sudah dikirim dari afterSave atau action, ini mungkin tidak perlu
        // atau bisa di-return null agar tidak ada notifikasi ganda.
        // Untuk 'Simpan Progres', kita bisa buat notifikasi sendiri di afterSave.
        // return null; // Atau custom notifikasi
        return Notification::make()
            ->success()
            ->title('Progres Disimpan')
            ->body('Perubahan pada penilaian telah berhasil disimpan.');
    }

    // Opsional: Jika Anda ingin redirect ke halaman index setelah save progres biasa (bukan hanya submit)
    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }
}