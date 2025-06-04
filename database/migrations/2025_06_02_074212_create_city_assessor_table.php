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
        Schema::create('city_assessor', function (Blueprint $table) {
            $table->id(); // Primary key untuk tabel pivot (opsional, tapi sering berguna)
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained()->cascadeOnDelete();
            $table->date('assignment_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps(); // Opsional, tapi bisa berguna untuk melacak kapan penugasan dibuat/diubah
            // Mencegah duplikasi penugasan asesor yang sama ke kota yang sama
            $table->unique(['city_id', 'assessor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_assessor');
    }
};
