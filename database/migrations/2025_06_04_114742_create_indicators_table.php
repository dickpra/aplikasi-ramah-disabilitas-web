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
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->text('name'); // Deskripsi/pertanyaan indikator
            $table->string('category')->nullable()->comment('Kategori indikator');
            $table->unsignedInteger('weight')->default(1)->comment('Bobot kepentingan');
            $table->string('scale_type')->nullable()->comment('Jenis skala, cth: 1-5, Ya/Tidak');
            $table->string('target_location_type')->nullable()->comment('Jenis lokasi target, cth: city, university, all');
            $table->text('keywords')->nullable()->comment('Kata kunci terkait indikator'); // Dari PDF Anda
            $table->text('measurement_method')->nullable()->comment('Cara pengukuran/penilaian'); // Dari PDF Anda
            $table->text('scoring_criteria_text')->nullable()->comment('Deskripsi teks kriteria penilaian'); // Dari PDF Anda
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
