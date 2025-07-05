<?php

namespace App\Services;

use App\Models\Language; // Pastikan model Language Anda sudah ada
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class LanguageService
{
    /**
     * Mengambil daftar kode bahasa (locales) yang aktif dari database.
     *
     * @return array
     */
    public function getActiveLocales(): array
    {
        // Gunakan cache untuk menyimpan daftar bahasa selama 1 hari (atau selamanya)
        // Ini akan sangat mempercepat proses dan menghindari error saat migrasi.
        return Cache::rememberForever('active_locales', function () {
            try {
                // Cek apakah tabel 'languages' sudah ada sebelum melakukan query
                if (Schema::hasTable('languages')) {
                    // Ambil semua bahasa, lalu ambil hanya kolom 'code'
                    return Language::query()->pluck('code')->toArray();
                }
            } catch (\Exception $e) {
                // Jika terjadi error (misal saat DB belum siap), kembalikan array default
                return ['id', 'en']; // Fallback default
            }

            // Fallback jika tabel belum ada
            return ['id', 'en'];
        });
    }
}