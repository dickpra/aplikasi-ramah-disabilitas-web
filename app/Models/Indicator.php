<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations; // <-- 1. Gunakan import dari Spatie

class Indicator extends Model
{
    use HasFactory;
    use HasTranslations; // <-- 2. Gunakan trait yang benar

    // --- 3. TAMBAHKAN PROPERTI PENTING INI ---
    // Definisikan semua kolom yang ingin Anda buat multibahasa.
    public array $translatable = [
        'name',
        'category',
        'keywords',
        'measurement_method',
        'scoring_criteria_text'
    ];
    // ------------------------------------

    protected $fillable = [
        'name', 'category', 'weight', 'scale_type', 'target_location_type',
        'keywords', 'measurement_method', 'scoring_criteria_text', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}