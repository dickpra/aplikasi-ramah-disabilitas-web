<?php

// app/Models/Assessor.php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Assignment; // <-- TAMBAHKAN ATAU PASTIKAN INI ADA


class Assessor extends Authenticatable implements FilamentUser // Implementasi FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        // 'specialization', // Jika Anda menambahkan kolom lain
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_assessor')
                    ->withPivot('assignment_date', 'description', 'id')
                    ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assessor_id');
    }
    
    // public function assignments(): HasMany // Relasi ke record penugasan
    // {
    //     return $this->hasMany(\App\Models\Assignment::class);
    // }

    public function canAccessPanel(Panel $panel): bool
    {
        // Izinkan asesor mengakses panel yang ID-nya 'assessor'
        return $panel->getId() === 'assessor';
    }
}
