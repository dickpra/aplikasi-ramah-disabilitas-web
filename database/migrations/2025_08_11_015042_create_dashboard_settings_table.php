<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_settings', function (Blueprint $table) {
            $table->id();

            // Bagian Hero/Dashboard
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();

            // Konten Halaman
            $table->text('about_me')->nullable();
            $table->text('credit')->nullable();
            $table->text('guidebook')->nullable();
            $table->text('metodologi')->nullable();

            // Info Kontak di Footer
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_settings');
    }
};