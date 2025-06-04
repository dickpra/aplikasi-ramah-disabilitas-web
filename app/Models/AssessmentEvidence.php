<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentEvidence extends Model
{
    use HasFactory;
    protected $fillable = [
        'assessment_score_id', 'file_path', 'original_file_name',
        'file_mime_type', 'file_size', 'description',
    ];
    public function assessmentScore(): BelongsTo {
        return $this->belongsTo(AssessmentScore::class);
    }
}
