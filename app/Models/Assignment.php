<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


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
}
