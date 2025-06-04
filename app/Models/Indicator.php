<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Indicator extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'category', 'weight', 'scale_type', 'target_location_type',
        'keywords', 'measurement_method', 'scoring_criteria_text', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean', 'weight' => 'integer'];
    public function assessmentScores(): HasMany {
        return $this->hasMany(AssessmentScore::class);
    }
}
