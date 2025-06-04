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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            // Mengubah user_id menjadi assessor_id dan merujuk ke tabel 'assessors'
            $table->foreignId('assessor_id')->constrained('assessors')->onDelete('cascade');
            $table->date('assignment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint untuk kombinasi location_id dan assessor_id
            $table->unique(['location_id', 'assessor_id'], 'location_assessor_unique_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
