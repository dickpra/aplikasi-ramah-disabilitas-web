<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->string('name'); // Ini adalah nama lokasi
            $table->string('location_type')->nullable()->comment('Tipe lokasi, cth: city, university, regency'); // Kolom baru untuk jenis lokasi
            $table->decimal('final_score', 8, 3)->nullable()->comment('Skor akhir hasil kalkulasi');
            $table->string('rank')->nullable()->comment('Peringkat hasil kalkulasi (DIAMOND, GOLD, dll.)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
