<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class City extends Model
{
    use HasFactory;
    protected $fillable = [
        'country_id',
        'name',
    ];

    // Definisikan relasi 'belongsTo' ke Country
    public function country(): BelongsTo
    {
        // Pastikan App\Models\Country adalah path yang benar ke model Country Anda
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
        // 'country_id' adalah foreign key default, jika nama kolom Anda berbeda, sesuaikan.
    }

    public function assessors(): BelongsToMany
    {
        return $this->belongsToMany(Assessor::class, 'city_assessor')
                    ->withPivot('assignment_date', 'description', 'id') // Sertakan pivot fields
                    ->withTimestamps(); // Jika tabel pivot punya timestamps
    }

    public function assignments(): HasMany // Relasi ke record penugasan
    {
        return $this->hasMany(Assignment::class);
    }

    // app/Models/City.php
    // ...
    // public function getLatestAssignmentDateAttribute()
    // {
    //     // Mengambil data pivot 'created_at' dari relasi 'assessors'
    //     // dan mengurutkannya dari yang terbaru, lalu ambil yang pertama.
    //     return $this->assessors()
    //                 ->orderByPivot('created_at', 'desc')
    //                 ->first()?->pivot?->created_at;
    // }
    public function getLatestAssignmentDateAttribute()
    {
        return $this->assignments()->latest('assignment_date')->first()?->assignment_date;
    }   
}
