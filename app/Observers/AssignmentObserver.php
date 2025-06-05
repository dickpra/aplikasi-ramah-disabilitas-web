<?php

namespace App\Observers;

use App\Models\Assignment;
use Illuminate\Support\Facades\Log; // Untuk debugging jika perlu

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
    }

    // Metode lain (created, deleted, etc.) bisa Anda biarkan kosong jika tidak digunakan
    public function created(Assignment $assignment): void {}
    public function deleted(Assignment $assignment): void {}
    public function restored(Assignment $assignment): void {}
    public function forceDeleted(Assignment $assignment): void {}
}