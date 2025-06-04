<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import HasMany

class Country extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'code',
    ];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
