<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Indicator;
use App\Models\Admin;
use App\Models\Language;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AutoTranslateIndicatorFields implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Indicator $indicator;
    protected $admin;

    public function __construct(Indicator $indicator, $admin)
    {
        $this->indicator = $indicator;
        $this->admin = $admin;
    }

    public function handle(): void
{
    Log::info("Memulai Job Terjemahan (Logika Deteksi Bahasa) untuk Indicator ID: {$this->indicator->id}");

    $translatableFields = $this->indicator->getTranslatableAttributes();
    $allLocales = Language::pluck('code')->toArray();
    $sourceTextForNotification = 'N/A';

    try {
        $tr = new GoogleTranslate();

        foreach ($translatableFields as $field) {
            $translations = $this->indicator->getTranslations($field);

            $sourceLocale = null;
            $sourceText = null;

            foreach ($translations as $locale => $text) {
                if (!empty(trim($text))) {
                    $sourceLocale = $locale;
                    $sourceText = trim($text);
                    Log::info("   - Field '{$field}': Menemukan teks sumber awal '{$sourceText}' di kolom [{$sourceLocale}]");
                    break;
                }
            }

            if (!$sourceText) {
                Log::info("   - Field '{$field}': Tidak ada teks sama sekali. Dilewati.");
                continue;
            }

            // 🔍 Deteksi bahasa asli dari isi teks
            $detectedText = $tr->setSource()->setTarget('en')->translate($sourceText);
            $detectedLocale = $tr->getLastDetectedSource();

            if (!$detectedLocale) {
                Log::warning("   - Tidak bisa mendeteksi bahasa dari '{$sourceText}', lanjut tanpa koreksi.");
                $detectedLocale = $sourceLocale;
            }

            Log::info("   - Bahasa terdeteksi: [{$detectedLocale}]");

            // 🔄 Jika lokasi asal salah, pindahkan isi
            if ($detectedLocale !== $sourceLocale && in_array($detectedLocale, $allLocales)) {
                Log::warning("     ⚠️ Bahasa salah tempat! Memindahkan teks dari [{$sourceLocale}] ke [{$detectedLocale}]");
                $this->indicator->setTranslation($field, $detectedLocale, $sourceText);
                $this->indicator->setTranslation($field, $sourceLocale, null);
                $sourceLocale = $detectedLocale;
            }

            if ($field === 'name') {
                $sourceTextForNotification = $sourceText;
            }

            // 🌐 Translate ke bahasa lain
            foreach ($allLocales as $targetLocale) {
                if ($targetLocale === $sourceLocale) continue;

                $existing = $this->indicator->getTranslation($field, $targetLocale, false);
                if (empty(trim($existing))) {
                    Log::info("     -> Menerjemahkan dari [{$sourceLocale}] ke [{$targetLocale}]...");
                    $tr->setSource($sourceLocale)->setTarget($targetLocale);
                    $translatedText = $tr->translate($sourceText);
                    $this->indicator->setTranslation($field, $targetLocale, $translatedText);
                    Log::info("     -> Hasil: {$translatedText}");
                    sleep(1);
                }
            }
        }

        $this->indicator->save();

        Notification::make()
            ->title('✅ Terjemahan Otomatis Selesai')
            ->body("Penerjemahan untuk indikator '{$sourceTextForNotification}' berhasil.")
            ->success()
            ->sendToDatabase($this->admin);

    } catch (\Exception $e) {
        Log::error("❌ Error di AutoTranslate Job: " . $e->getMessage());
        Notification::make()
            ->title('❌ Terjemahan Otomatis Gagal')
            ->body("Terjadi kesalahan saat auto-translate. Silakan cek log.")
            ->danger()
            ->sendToDatabase($this->admin);
     }
    }
}