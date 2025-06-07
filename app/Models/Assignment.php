<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Penting untuk accessor

class Assignment extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'location_id',
        'assessor_id', // Diubah dari user_id
        'assignment_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'due_date' => 'date',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assessor(): BelongsTo // Diubah dari user()
    {
        return $this->belongsTo(Assessor::class, 'assessor_id');
    }
    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    // --- TAMBAHKAN METODE BARU DI SINI ---

    /**
     * Menghitung skor akhir untuk penugasan ini.
     *
     * @return float|null
     */
    public function calculateScore(): ?float
    {
        // Eager load relasi untuk efisiensi
        $scores = $this->assessmentScores()->with('indicator')->get();

        if ($scores->isEmpty()) {
            return null;
        }

        $totalWeightedScoreSum = 0;
        $totalWeightSum = 0;

        foreach ($scores as $scoreEntry) {
            if ($scoreEntry->indicator && is_numeric($scoreEntry->score)) {
                $indicatorWeight = $scoreEntry->indicator->weight ?? 1;
                
                $totalWeightedScoreSum += (float)$scoreEntry->score * $indicatorWeight;
                $totalWeightSum += $indicatorWeight;
            }
        }

        if ($totalWeightSum == 0) {
            return 0;
        }

        // Kembalikan skor akhir (rata-rata tertimbang)
        return round($totalWeightedScoreSum / $totalWeightSum, 3);
    }

    /**
     * Membuat accessor 'final_score' agar bisa dipanggil dengan mudah.
     * Contoh: $assignment->final_score
     */
    protected function finalScore(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculateScore(),
        );
    }
}
