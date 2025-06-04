<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;
    protected $fillable = [
        'province_id',
        'name',          // Nama lokasi
        'location_type', // Jenis lokasi
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
}
