<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Assessor;
use App\Models\Indicator; // <--- TAMBAHKAN IMPORT MODEL INDICATOR


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Admin::create([
            'name' => 'Admin Master',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin123'),
        ]);
        
        Assessor::create([
            'name' => 'Asesor',
            'email' => 'asesor@asesor.com',
            'password' => bcrypt('asesor123'),
        ]);
        \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $indicators = [
            // === Kategori: Dukungan Pemerintah ===
            [
                'name' => 'Pemerintah telah mendukung dan memayungi keadilan, perlindungan hukum, pendidikan dan layanan kebutuhan khusus bagi penyandang disabilitas',
                'category' => 'Dukungan Pemerintah',
                'keywords' => 'Peraturan Perlindungan hukum disabilitas di kota',
                'measurement_method' => 'Melakukan pencarian melalui google berdasarkan key word',
                'scoring_criteria_text' => "1. Tidak ada perlindungan hukum\n2. Ada perlindungan hukum",
                'weight' => 1,
                'scale_type' => 'Skala 1-2',
                'target_location_type' => 'Kota',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pemerintah telah mendukung dan memayungi hak kemudahan/aksesibilitas dalam layanan atau fasilitas fisik bagi penyandang disabilitas (dilingkungan sekolah, media, umum)',
                'category' => 'Dukungan Pemerintah',
                'keywords' => 'Dukungan "pemerintah" terhadap aksesibilitas bagi penyandang disabilitas di kota',
                'measurement_method' => 'Melihat jumlah berita yang sesuai melalui pencarian di google',
                'scoring_criteria_text' => "1. Tidak ada berita yang memberikan informasi...\n2. Ada 1-3 berita saja...\n3. Lebih dari 3 berita...",
                'weight' => 1,
                'scale_type' => 'Skala 1-3',
                'target_location_type' => 'Kota',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Tambahkan contoh indikator lain dari PDF Anda di sini seperti yang saya berikan sebelumnya...
            // Contoh: Pendidikan, Penyerapan Tenaga Kerja, Infrastruktur
            [
                'name' => 'Rasio daya tampung ABK di kelas inklusi di SD/SMP/SMA/PT (jumlah penerimaan ABK sesuai UU)',
                'category' => 'Pendidikan',
                'keywords' => 'PPDB kota daya tampung inklusi',
                'measurement_method' => 'Penelusuran Website PPDB kota dengan melihat daya tamping inklusi',
                'scoring_criteria_text' => "1. Tidak ada informasi daya tampung siswa inklusi\n2. Ada informasi daya tampung siswa inklusi",
                'weight' => 1,
                'scale_type' => 'Skala 1-2',
                'target_location_type' => 'Kota',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Apakah perusahaan melakukan ujian minat, bakat dan kemampuan menggunakan wawancara?',
                'category' => 'Penyerapan Tenaga Kerja',
                'keywords' => 'Proses rekruitmen karyawan penyandang disabillitas di perusahaan...',
                'measurement_method' => 'Melakukan pencarian melalui google berdasarkan key word',
                'scoring_criteria_text' => "1. Tidak ada proses rekrutmen karyawan disabilitas\n2. Ada proses rekrutmen karyawan disabilitas",
                'weight' => 1,
                'scale_type' => 'Skala 1-2',
                'target_location_type' => 'Kota',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($indicators as $indicator) {
            Indicator::create($indicator);
        }
    }
}
