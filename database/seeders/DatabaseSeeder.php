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

        // Admin::create([
        //     'name' => 'Admin Master',
        //     'email' => 'admin@admin.com',
        //     'password' => bcrypt('admin123'),
        // ]);
        
        // Assessor::create([
        //     'name' => 'Asesor',
        //     'email' => 'asesor@asesor.com',
        //     'password' => bcrypt('asesor123'),
        // ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $indicators =  [
    // === Kategori: Sosial & Budaya ===
    [
        'name' => 'Kegiatan seni dan budaya kota mengakomodasi partisipasi penyandang disabilitas',
        'category' => 'Sosial & Budaya',
        'keywords' => 'Event budaya inklusi disabilitas kota',
        'measurement_method' => 'Review jadwal dan dokumentasi event budaya',
        'scoring_criteria_text' => "1. Tidak ada kegiatan inklusi\n2. Ada 1–2 kegiatan inklusi\n3. Ada lebih dari 2 kegiatan inklusi",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Tersedia komunitas atau organisasi penyandang disabilitas di kota',
        'category' => 'Sosial & Budaya',
        'keywords' => 'Komunitas disabilitas kota daftar organisasi',
        'measurement_method' => 'Pencarian direktori organisasi/jaringan sosial',
        'scoring_criteria_text' => "1. Tidak ada komunitas terdaftar\n2. Ada 1–3 komunitas\n3. Lebih dari 3 komunitas",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // === Kategori: Ekonomi & Kewirausahaan ===
    [
        'name' => 'Program modal usaha mikro untuk pelaku disabilitas',
        'category' => 'Ekonomi & Kewirausahaan',
        'keywords' => 'Program UMKM disabilitas kota',
        'measurement_method' => 'Cek kebijakan Dinas Koperasi/Dinas UMKM',
        'scoring_criteria_text' => "1. Tidak ada program\n2. Ada program satu jenis\n3. Ada beberapa program diversifikasi",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Fasilitas coworking space atau inkubator bisnis ramah disabilitas',
        'category' => 'Ekonomi & Kewirausahaan',
        'keywords' => 'Inkubator bisnis inklusi disabilitas kota',
        'measurement_method' => 'Survey fasilitas publik/personal interview',
        'scoring_criteria_text' => "1. Tidak ada fasilitas\n2. Ada satu lokasi\n3. Ada lebih dari satu lokasi",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // === Kategori: Komunikasi & Informasi ===
    [
        'name' => 'Tersedianya layanan penerjemah bahasa isyarat di instansi publik',
        'category' => 'Komunikasi & Informasi',
        'keywords' => 'Penerjemah isyarat instansi publik kota',
        'measurement_method' => 'Konfirmasi ke instansi terkait melalui telepon/email',
        'scoring_criteria_text' => "1. Tidak tersedia\n2. Ada layanan terbatas\n3. Ada layanan penuh dan rutin",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Website dan aplikasi layanan publik menyediakan teks alternatif (alt text) untuk media',
        'category' => 'Komunikasi & Informasi',
        'keywords' => 'Alt text website pemerintah kota',
        'measurement_method' => 'Audit manual pada 5–10 halaman web',
        'scoring_criteria_text' => "1. < 30% halaman punya alt text\n2. 30–70%\n3. > 70%",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // === Kategori: Kedaruratan & Kesehatan ===
    [
        'name' => 'Rencana tanggap darurat kota mencakup penyandang disabilitas',
        'category' => 'Kedaruratan & Kesehatan',
        'keywords' => 'Rencana evakuasi disabilitas kota',
        'measurement_method' => 'Review dokumen BPBD kota',
        'scoring_criteria_text' => "1. Tidak ada rencana khusus\n2. Ada rencana namun tidak teruji\n3. Ada dan sudah diuji coba",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Puskesmas/klinik menyediakan jalur prioritas antrean untuk penyandang disabilitas',
        'category' => 'Kedaruratan & Kesehatan',
        'keywords' => 'Antrean prioritas disabilitas puskesmas kota',
        'measurement_method' => 'Survei lapangan di 5 fasilitas kesehatan',
        'scoring_criteria_text' => "1. Tidak ada prioritas\n2. Ada di sebagian fasilitas\n3. Ada di semua fasilitas",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],

    // === Kategori: Teknologi & Inovasi ===
    [
        'name' => 'Adanya aplikasi mobile kota yang menyediakan fitur aksesibilitas (teks besar, high contrast)',
        'category' => 'Teknologi & Inovasi',
        'keywords' => 'Aplikasi kota aksesibilitas fitur',
        'measurement_method' => 'Uji coba aplikasi mobile dan dokumentasi fitur',
        'scoring_criteria_text' => "1. Tidak ada fitur\n2. Ada satu fitur dasar\n3. Ada beberapa fitur lengkap",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Implementasi IoT atau smart city untuk membantu mobilitas disabilitas (misal: beacon audio)',
        'category' => 'Teknologi & Inovasi',
        'keywords' => 'Smart city disabilitas beacon audio kota',
        'measurement_method' => 'Wawancara dengan Dinas Kominfo dan uji lapangan',
        'scoring_criteria_text' => "1. Tidak ada implementasi\n2. Ada pilot project\n3. Sudah terintegrasi kota",
        'weight' => 1,
        'scale_type' => 'Skala 1-3',
        'target_location_type' => 'Kota',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]
    ];


        foreach ($indicators as $indicator) {
            Indicator::create($indicator);
        }
    }
}
