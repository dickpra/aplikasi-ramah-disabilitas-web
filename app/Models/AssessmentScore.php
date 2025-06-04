<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Pastikan ini ada

class AssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = ['assignment_id', 'indicator_id', 'score', 'assessor_notes'];

    // ... relasi lain ...

    public function evidences(): HasMany // Pastikan nama methodnya benar
    {
        // Parameter kedua dan ketiga biasanya tidak perlu jika mengikuti konvensi Laravel
        // yaitu foreign key 'assessment_score_id' dan local key 'id' di assessment_scores
        return $this->hasMany(AssessmentEvidence::class, 'assessment_score_id', 'id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
    // ...
}