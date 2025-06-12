<?php

// app/Models/Translation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Cast kolom 'text' ke array agar mudah dimanipulasi
    protected $casts = [
        'text' => 'array',
    ];
}