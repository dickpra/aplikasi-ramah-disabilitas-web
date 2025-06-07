<?php

namespace App\Observers;

use App\Models\Assignment;
use Illuminate\Support\Facades\Log; // Untuk debugging jika perlu
use App\Notifications\NewAssignmentNotification; // <-- Import notifikasi
use App\Notifications\RevisionRequestNotification; // <-- Import notifikasi revisi
use App\Models\Admin; // <-- 1. IMPORT model Admin
use App\Notifications\AssessorSubmissionNotification; // <-- 2. IMPORT notifikasi baru
use Illuminate\Support\Facades\Notification; // <-- 3. IMPORT Fassade Notifikasi


class AssignmentObserver
{
    /**
     * Handle the Assignment "updated" event.
     */
    public function updated(Assignment $assignment): void
    {

        
        
        // Cek apakah kolom 'status' baru saja diubah menjadi 'approved'
        if ($assignment->wasChanged('status') && $assignment->status === 'approved') {
            
            Log::info("AssignmentObserver: Status Assignment #{$assignment->id} diubah menjadi 'approved'. Memicu kalkulasi ulang untuk Location #{$assignment->location_id}.");

            // Ambil lokasi yang terkait dengan penugasan ini
            $location = $assignment->location;

            if ($location) {
                // Panggil metode kalkulasi skor dari model Location
                $result = $location->calculateFinalScore();

                if ($result) {
                    // Simpan skor dan peringkat baru ke tabel locations
                    $location->final_score = $result['final_score'];
                    $location->rank = $result['rank'];
                    $location->save();
                    
                    Log::info("AssignmentObserver: Lokasi #{$location->id} diupdate. Skor: {$location->final_score}, Peringkat: {$location->rank}");
                }
            }
        }
        

        if ($assignment->wasChanged('status')) {
            $newStatus = $assignment->status;
            $assessor = $assignment->assessor;

            if ($assessor) {
                Log::info("AssignmentObserver: Status Assignment #{$assignment->id} diubah menjadi '{$newStatus}'.");

                // Kirim notifikasi berdasarkan status baru
                match ($newStatus) {
                    'revision_needed' => $assessor->notify(new RevisionRequestNotification($assignment)),
                    'approved' => null, // Anda bisa buat notifikasi 'Approved' di sini jika mau, contoh: $assessor->notify(new AssessmentApprovedNotification($assignment)),
                    'pending_review_admin' => Notification::send(Admin::all(), new AssessorSubmissionNotification($assignment)),

                    // Status lain bisa ditambahkan di sini
                    default => Log::info("AssignmentObserver: Tidak ada notifikasi yang dikonfigurasi untuk status '{$newStatus}'.")
                };
            }
            
            // Logika kalkulasi skor akhir untuk status 'approved' tetap di sini
            if ($newStatus === 'approved' && $assignment->location) {
                Log::info("Memicu kalkulasi ulang untuk Lokasi #{$assignment->location_id}.");
                $result = $assignment->location->calculateFinalScore();
                if ($result) {
                    $assignment->location->update([
                        'final_score' => $result['final_score'],
                        'rank' => $result['rank'],
                    ]);
                }
            }
            
        }
        
    }

    // Metode lain (created, deleted, etc.) bisa Anda biarkan kosong jika tidak digunakan
    public function created(Assignment $assignment): void
    {
        Log::info("AssignmentObserver: Metode 'created' terpicu untuk Assignment ID: " . $assignment->id);

        if ($assignment->assessor) {
            Log::info("AssignmentObserver: Mengirim notifikasi ke Assessor ID: " . $assignment->assessor->id);
            $assignment->assessor->notify(new NewAssignmentNotification($assignment));
        } else {
            Log::warning("AssignmentObserver: Tidak bisa mengirim notifikasi karena tidak ada asesor terkait pada Assignment ID: " . $assignment->id);
        }
    }
    public function deleted(Assignment $assignment): void {}
    public function restored(Assignment $assignment): void {}
    public function forceDeleted(Assignment $assignment): void {}
}