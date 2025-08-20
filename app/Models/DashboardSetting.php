<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'about_me',
        'credit',
        'guidebook',
        'metodologi',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'about_me' => 'array',
        'credit' => 'array',
        'guidebook' => 'array',
        'metodologi' => 'array',
    ];
}