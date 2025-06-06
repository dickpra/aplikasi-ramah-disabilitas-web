<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Penting untuk accessor



class Location extends Model
{
    use HasFactory;
    // app/Models/Location.php
    protected $fillable = [
        'province_id', 
        'name', 
        'location_type',
        'final_score', // <-- Tambahkan
        'rank',        // <-- Tambahkan
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function assignments(): HasMany // Sebuah lokasi bisa memiliki banyak penugasan (hingga 3 asesor)
    {
        return $this->hasMany(Assignment::class);
    }

    // Helper untuk mendapatkan jumlah asesor yang sudah ditugaskan
    public function getAssignedAssessorsCountAttribute(): int
    {
        return $this->assignments()->count(); // Atau filter berdasarkan status 'assigned' jika perlu
    }

     private function getMaxScoreFromScaleType(?string $scaleType): int
    {
        if (!$scaleType) return 1; // Default jika tidak ada tipe skala

        $scaleType = strtolower($scaleType);
        if (str_starts_with($scaleType, 'skala ')) {
            $parts = explode('-', str_replace('skala ', '', $scaleType));
            if (count($parts) === 2 && is_numeric($parts[1])) {
                return (int)$parts[1]; // Mengambil angka kedua sebagai skor maksimal
            }
        } elseif (in_array($scaleType, ['ya/tidak', 'ada/tidak ada'])) {
            return 1; // Skor maksimal adalah 1 (untuk "Ya" atau "Ada")
        }
        
        // Fallback jika format tidak dikenali, bisa disesuaikan
        return 5; 
    }

    public function calculateFinalScore(): ?array
    {
        $approvedAssignments = $this->assignments()
                                 ->where('status', 'approved')
                                 ->with('assessmentScores.indicator')
                                 ->get();

        if ($approvedAssignments->isEmpty()) return null;

        $scoresByIndicator = [];
        foreach ($approvedAssignments as $assignment) {
            foreach ($assignment->assessmentScores as $scoreEntry) {
                if ($scoreEntry->indicator && is_numeric($scoreEntry->score)) {
                    $scoresByIndicator[$scoreEntry->indicator_id]['scores'][] = (float)$scoreEntry->score;
                    if (!isset($scoresByIndicator[$scoreEntry->indicator_id]['indicator'])) {
                        $scoresByIndicator[$scoreEntry->indicator_id]['indicator'] = $scoreEntry->indicator;
                    }
                }
            }
        }

        if (empty($scoresByIndicator)) return null;

        $totalWeightedPercentageSum = 0;
        $totalWeightSum = 0;

        foreach ($scoresByIndicator as $indicatorId => $data) {
            if (empty($data['scores']) || !$data['indicator']) continue;
            
            $averageScoreForIndicator = array_sum($data['scores']) / count($data['scores']);
            $indicator = $data['indicator'];
            $indicatorWeight = $indicator->weight ?? 1;

            // --- PERUBAHAN UTAMA DI SINI ---
            $maxScore = $this->getMaxScoreFromScaleType($indicator->scale_type);
            // Hitung skor persentase (dalam desimal 0-1 agar mudah dihitung)
            $percentageScore = ($maxScore > 0) ? ($averageScoreForIndicator / $maxScore) : 0;
            // -----------------------------

            $totalWeightedPercentageSum += $percentageScore * $indicatorWeight;
            $totalWeightSum += $indicatorWeight;
        }

        if ($totalWeightSum == 0) return ['final_score' => 0, 'rank' => 'N/A'];

        // Skor akhir sekarang dalam bentuk persentase
        $finalLocationPercentage = round(($totalWeightedPercentageSum / $totalWeightSum) * 100, 2); 
        
        // Anda perlu menyesuaikan ambang batas peringkat Anda untuk bekerja dengan persentase
        // Contoh: DIAMOND >= 90%, GOLD >= 75%, dst.
        $rank = $this->determineRankByPercentage($finalLocationPercentage);

        return [
            'final_score' => $finalLocationPercentage, // Sekarang ini adalah persentase
            'rank' => $rank,
        ];
    }
    
    // Metode rank baru berdasarkan persentase
    protected function determineRankByPercentage(float $percentage): string
    {
        // CONTOH AMBANG BATAS BARU, SILAKAN DISESUAIKAN
        if ($percentage >= 90) return 'DIAMOND';
        if ($percentage >= 75) return 'GOLD';
        if ($percentage >= 50) return 'SILVER';
        if ($percentage >= 25) return 'BRONZE';
        return 'PARTICIPANT';
    }

    // Accessor bisa disesuaikan untuk memanggil metode baru ini
    protected function finalAssessment(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateFinalScore(),
        );
    }

//     /**
//      * Metode utama untuk menghitung skor akhir dan peringkat.
//      * Hanya akan menghitung dari assignment yang statusnya 'approved'.
//      *
//      * @return array|null
//      */
//     public function calculateFinalScore(): ?array
//     {
//         // 1. Dapatkan semua penugasan yang sudah disetujui (approved) untuk lokasi ini
//         $approvedAssignments = $this->assignments()
//                                  ->where('status', 'approved')
//                                  ->with('assessmentScores.indicator') // Eager load untuk performa
//                                  ->get();

//         if ($approvedAssignments->isEmpty()) {
//             return null; // Kembalikan null jika tidak ada penilaian yang disetujui
//         }

//         // 2. Kumpulkan semua skor dari semua asesor untuk setiap indikator
//         $scoresByIndicator = [];
//         foreach ($approvedAssignments as $assignment) {
//             foreach ($assignment->assessmentScores as $scoreEntry) {
//                 // Pastikan indikator dan skor ada dan numerik
//                 if ($scoreEntry->indicator && is_numeric($scoreEntry->score)) {
//                     // Kumpulkan semua skor untuk indicator_id yang sama
//                     $scoresByIndicator[$scoreEntry->indicator_id]['scores'][] = (float)$scoreEntry->score;
//                     // Simpan detail indikator sekali saja untuk efisiensi
//                     if (!isset($scoresByIndicator[$scoreEntry->indicator_id]['indicator'])) {
//                         $scoresByIndicator[$scoreEntry->indicator_id]['indicator'] = $scoreEntry->indicator;
//                     }
//                 }
//             }
//         }

//         if (empty($scoresByIndicator)) {
//             return null; // Tidak ada skor valid yang bisa dihitung
//         }

//         $totalWeightedScoreSum = 0;
//         $totalWeightSum = 0;

//         // 3. Hitung skor konsolidasi (rata-rata) untuk setiap indikator dan terapkan bobot
//         foreach ($scoresByIndicator as $indicatorId => $data) {
//             if (empty($data['scores']) || !$data['indicator']) {
//                 continue;
//             }
//             // a. Hitung rata-rata skor untuk indikator ini dari semua asesor
//             $averageScoreForIndicator = array_sum($data['scores']) / count($data['scores']);
            
//             // b. Dapatkan bobot indikator
//             $indicatorWeight = $data['indicator']->weight ?? 1;

//             // c. Akumulasi skor tertimbang dan total bobot
//             $totalWeightedScoreSum += $averageScoreForIndicator * $indicatorWeight;
//             $totalWeightSum += $indicatorWeight;
//         }

//         if ($totalWeightSum == 0) {
//             return ['final_score' => 0, 'rank' => 'N/A'];
//         }

//         // 4. Hitung skor akhir lokasi (rata-rata tertimbang)
//         $finalLocationScore = round($totalWeightedScoreSum / $totalWeightSum, 3); // Ambil 3 angka di belakang koma

//         // 5. Tentukan Peringkat (DIAMOND, GOLD, dll.)
//         $rank = $this->determineRank($finalLocationScore);

//         return [
//             'final_score' => $finalLocationScore,
//             'rank' => $rank,
//             'total_assignments_calculated' => $approvedAssignments->count(),
//             'total_indicators_scored' => count($scoresByIndicator),
//         ];
//     }

//     /**
//      * Metode helper untuk menentukan peringkat berdasarkan skor.
//      * Anda BISA dan SEBAIKNYA mengubah ambang batas ini sesuai kebutuhan.
//      *
//      * @param float $score
//      * @return string
//      */
//     protected function determineRank(float $score): string
//     {
//         // CONTOH AMBANG BATAS, SILAKAN DISESUAIKAN
//         if ($score >= 4.5) return 'DIAMOND';
//         if ($score >= 3.5) return 'GOLD';
//         if ($score >= 2.5) return 'SILVER';
//         if ($score >= 1.5) return 'BRONZE';
//         return 'PARTICIPANT';
//     }

//     /**
//      * Membuat 'virtual attribute' agar kita bisa memanggil $location->final_assessment
//      * dengan mudah di Filament atau di tempat lain.
//      * Ini akan menjalankan kalkulasi secara otomatis.
//      *
//      * @return \Illuminate\Database\Eloquent\Casts\Attribute
//      */
//     protected function finalAssessment(): Attribute
//     {
//         return Attribute::make(
//             get: fn () => $this->calculateFinalScore(),
//         );
//     }
}
