<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentEvidence extends Model
{
    use HasFactory;
    protected $table = 'assessment_evidences'; // <--- TAMBAHKAN BARIS INI SECARA EKSPLISIT

    protected $fillable = [
        'assessment_score_id',
        'file_path',
        'original_file_name', // Jika Anda akan menyimpannya
        'file_mime_type',     // Jika Anda akan menyimpannya
        'file_size',          // Jika Anda akan menyimpannya
        'description',
    ];
    public function assessmentScore(): BelongsTo {
        return $this->belongsTo(AssessmentScore::class);
    }
}
