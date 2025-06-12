<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationsSyncFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:sync-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ambil semua terjemahan dari database dan tulis ke file lang/*.json';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Memulai sinkronisasi terjemahan dari database ke file...');

        // Ambil semua bahasa yang aktif
        $languages = Language::all();
        if ($languages->isEmpty()) {
            $this->warn('Tidak ada bahasa ditemukan di database. Proses dibatalkan.');
            return;
        }

        // Ambil semua data terjemahan sekali saja untuk efisiensi
        $allTranslations = Translation::all();

        foreach ($languages as $language) {
            $langCode = $language->code;
            $filePath = lang_path("{$langCode}.json");
            $translationsForFile = [];

            // Proses setiap key dari database
            foreach ($allTranslations as $translation) {
                // Ambil teks untuk bahasa saat ini
                $text = $translation->text[$langCode] ?? null;

                // Hanya tambahkan jika teksnya ada (tidak null atau kosong)
                if (!empty($text)) {
                    $translationsForFile[$translation->key] = $text;
                }
            }

            // Jika ada data terjemahan untuk bahasa ini, tulis ke file
            if (!empty($translationsForFile)) {
                // Encode ke JSON dengan format yang rapi
                $jsonContent = json_encode(
                    $translationsForFile,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                File::put($filePath, $jsonContent);
                $this->line("-> File '{$langCode}.json' berhasil disinkronkan dengan " . count($translationsForFile) . " key.");
            } else {
                $this->line("-> Tidak ada terjemahan untuk '{$langCode}'. File tidak diubah.");
            }
        }

        $this->info('Sinkronisasi selesai!');
    }
}
