<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentScore extends Model
{
    use HasFactory;
    protected $fillable = ['assignment_id', 'indicator_id', 'score', 'assessor_notes'];
    public function assignment(): BelongsTo {
        return $this->belongsTo(Assignment::class);
    }
    public function indicator(): BelongsTo {
        return $this->belongsTo(Indicator::class);
    }
    public function evidences(): HasMany { // Jika ada tabel bukti
        return $this->hasMany(AssessmentEvidence::class);
    }
}
