<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'city_assessor'; // Secara eksplisit menunjuk ke tabel pivot

    protected $fillable = [
        'city_id',
        'assessor_id',
        'assignment_date', // Ditambahkan
        'description',
    ];

    // Casts untuk memastikan 'assignment_date' diperlakukan sebagai objek Carbon/Date
    protected $casts = [
        'assignment_date' => 'date',
    ];

    // Setiap record Assignment dimiliki oleh SATU City
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Setiap record Assignment dimiliki oleh SATU Assessor
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(Assessor::class);
    }

    // Accessor getLatestAssignmentDateAttribute TIDAK cocok di sini
    // karena model ini merepresentasikan SATU assignment.
    // Tanggal assignment ini sendiri adalah $this->created_at.
}