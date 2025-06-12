<?php

namespace App\Filament\Resources\TranslationResource\Pages;

use App\Filament\Resources\TranslationResource;
use App\Models\Language;
use App\Models\Translation;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Filament\Forms;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Ganti ini dengan library client API terjemahan yang Anda gunakan.
// Contoh ini menggunakan library konseptual.
// Jika menggunakan Google, bisa jadi: use Google\Cloud\Translate\V2\TranslateClient;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    /**
     * Mendefinisikan actions yang akan muncul di header halaman.
     *
     * @return array
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // **GRUP AKSI EXPORT/IMPORT YANG BARU**
            Actions\ActionGroup::make([
                Actions\Action::make('exportCsv')
                    ->label('Export ke CSV')
                    ->icon('heroicon-o-table-cells')
                    ->action('exportToCsv'),
                
                Actions\Action::make('importCsv')
                    ->label('Import dari CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('File CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'text/plain']),
                    ])
                    ->action(function (array $data) {
                        $this->importFromCsv($data['attachment']);
                    }),
            ])->label('Import/Export')->button()->icon('heroicon-o-arrows-up-down')->color('info'),

            Actions\Action::make('scan')
                ->label('Scan Translations')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->action('scanAndSaveTranslations')
                ->requiresConfirmation()
                ->modalHeading('Scan for New Translations')
                ->modalDescription('This will scan your app and resource folders for new translation keys. Are you sure?'),

            Actions\Action::make('autoTranslate')
                ->label('Auto Translate...')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                // Menambahkan form di dalam modal/dialog
                ->form([
                    Forms\Components\Select::make('target_language')
                        ->label(__('Terjemahkan ke bahasa'))
                        ->options(function () {
                            // Ambil semua bahasa KECUALI yang default
                            $defaultLangCode = Language::where('is_default', true)->first()?->code;
                            if (!$defaultLangCode) {
                                return [];
                            }
                            return Language::where('code', '!=', $defaultLangCode)
                                            ->pluck('name', 'code');
                        })
                        ->required()
                        ->helperText(__('Pilih bahasa tujuan untuk menerjemahkan teks dari bahasa default.')),
                ])
                ->action(function (array $data) {
                    $this->runAutoTranslation($data['target_language']);
                }),


                Actions\Action::make('syncFiles')
                ->label(__('Sinkronkan File JSON'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('Sinkronkan File Terjemahan'))
                ->modalDescription(__('Aksi ini akan menimpa semua file di resources/lang/*.json dengan data terbaru dari database. Lanjutkan?'))
                ->action(function () {
                    try {
                        // Jalankan Artisan command yang telah kita buat
                        Artisan::call('translations:sync-files');
                        
                        Notification::make()
                            ->title(__('Sinkronisasi Berhasil'))
                            ->body(__('Semua file terjemahan .json telah diperbarui sesuai data di database.'))
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Sinkronisasi Gagal'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Fungsi untuk menjalankan proses scan dan menyimpan ke database.
     */
    public function scanAndSaveTranslations(): void
    {
        Notification::make()
        ->title('Scanning started...')
        ->body('The system is now scanning project files.')->info()->send();
        $path = base_path();
        $excludeDirs = ['vendor', 'storage'];
        $pattern = "/(?:__|trans)\(\s*['\"]([^'\"]+)['\"]\s*[\),]/siU";
        $finder = new Finder();
        $finder->in($path)->exclude($excludeDirs)->name(['*.php', '*.vue', '*.js'])->files();
        $allKeys = [];
        foreach ($finder as $file) {
            if (preg_match_all($pattern, $file->getContents(), $matches)) {
                foreach ($matches[1] as $key) {
                    if (str_contains($key, '$') || strlen($key) < 2) continue;
                    $allKeys[] = $key;
                }
            }
        }
        $uniqueKeys = array_unique($allKeys);
        $foundCount = count($uniqueKeys);
        $newlyAdded = 0;
        if ($foundCount > 0) {
            $languages = Language::all();
            if ($languages->isEmpty()) {
                Notification::make()
                ->title(__('Scan Failed'))
                ->body(__('No languages found. Please add a language first.'))->danger()->send();
                return;
            }

            // **== PERUBAHAN LOGIKA ADA DI SINI ==**
            // Dapatkan kode bahasa default sebelum loop
            $defaultLangCode = Language::where('is_default', true)->first()?->code;

            $existingKeys = Translation::pluck('key')->all();
            foreach ($uniqueKeys as $key) {
                if (!in_array($key, $existingKeys)) {
                    $newlyAdded++;
                    $textValues = [];
                    foreach ($languages as $language) {
                        // Jika bahasa saat ini adalah bahasa default, isi teksnya dengan key itu sendiri.
                        // Jika tidak, biarkan kosong.
                        if ($defaultLangCode && $language->code === $defaultLangCode) {
                            $textValues[$language->code] = $key;
                        } else {
                            $textValues[$language->code] = '';
                        }
                    }
                    Translation::create(['key' => $key, 'text' => $textValues]);
                }
            }
        }
        if ($newlyAdded > 0) {
            Artisan::call('cache:clear');
        }
        Notification::make()->title('Scan Complete')->body("Found {$foundCount} unique keys. {$newlyAdded} new keys were added to the database.")->success()->send();
    }


    /**
     * Fungsi untuk menerjemahkan key yang kosong secara otomatis.
     */
    public function runAutoTranslation(string $targetLangCode): void
    {
        $defaultLanguage = Language::where('is_default', true)->first();

        if (!$defaultLanguage) {
            Notification::make()
            ->title(__('Aksi Dibatalkan'))
            ->body(__('Anda harus menetapkan satu bahasa sebagai default terlebih dahulu.'))
            ->danger()->send();
            return;
        }

        $sourceLangCode = $defaultLanguage->code;
        if ($sourceLangCode === $targetLangCode) {
            Notification::make()
            ->title(__('Aksi Dibatalkan'))
            ->body(__('Bahasa sumber dan tujuan tidak boleh sama.'))
            ->warning()->send();
            return;
        }

        // Kueri untuk menemukan key yang bisa diproses
        $translationsToProcessQuery = Translation::query()
            ->whereNotNull("text->{$sourceLangCode}")
            ->where("text->{$sourceLangCode}", '!=', '')
            ->where(function ($query) use ($targetLangCode) {
                $query->whereNull("text->{$targetLangCode}")
                      ->orWhere("text->{$targetLangCode}", '');
            });

        // **== KODE BARU: LAPORAN DETAIL ==**
        // Jika kueri tidak menemukan hasil, berikan laporan mendetail.
        if ($translationsToProcessQuery->count() === 0) {
            $totalKeys = Translation::count();
            $keysWithoutSourceText = Translation::query()
                ->where(function($q) use ($sourceLangCode) {
                    $q->whereNull("text->{$sourceLangCode}")
                      ->orWhere("text->{$sourceLangCode}", '');
                })
                ->count();
            
            $alreadyTranslatedCount = Translation::query()
                ->whereNotNull("text->{$targetLangCode}")
                ->where("text->{$targetLangCode}", '!=', '')
                ->count();

            $report = "Tidak ada key yang perlu diproses untuk bahasa '{$targetLangCode}'.\n\n" .
                      "• Total Key di Database: **{$totalKeys}**\n" .
                      "• Key dengan Teks Sumber Kosong: **{$keysWithoutSourceText}**\n" .
                      "• Key yang Sudah Diterjemahkan: **{$alreadyTranslatedCount}**\n\n" .
                      "**Solusi**: Pastikan teks untuk bahasa default (**{$sourceLangCode}**) sudah diisi sebelum menerjemahkan.";

            Notification::make()
                ->title(__('Tidak Ada Data Untuk Diproses'))
                ->body($report)
                ->warning()
                ->persistent() // Notifikasi tidak akan hilang sampai di-klik
                ->send();
            return;
        }
        
        // Lanjutkan proses jika ada data
        Notification::make()
        ->title(__('Proses Dimulai'))
        ->body(__("Menerjemahkan dari {$sourceLangCode} ke {$targetLangCode}..."))->info()->send();

        try {
            $tr = new GoogleTranslate();
            $tr->setSource($sourceLangCode);
            $tr->setTarget($targetLangCode);

            $translatedCount = 0;
            // Gunakan cursor pada kueri yang sudah kita buat
            foreach ($translationsToProcessQuery->cursor() as $translation) {
                $sourceText = $translation->text[$sourceLangCode];
                $translatedText = $tr->translate($sourceText);
                $currentText = $translation->text;
                $currentText[$targetLangCode] = $translatedText;
                $translation->text = $currentText;
                $translation->save();
                $translatedCount++;
            }

            if ($translatedCount > 0) {
                Artisan::call('cache:clear');
                Notification::make()
                ->title(__('Sukses'))
                ->body(__("{$translatedCount} key berhasil diterjemahkan ke bahasa '{$targetLangCode}'."))->success()->send();
            }

        } catch (\Exception $e) {
            Notification::make()
            ->title('Terjadi Error')
            ->body(__("Gagal menerjemahkan: ") . $e->getMessage())->danger()->send();
        }
    }

    public function exportToCsv(): StreamedResponse
    {
        $fileName = 'translations-' . now()->format('Y-m-d') . '.csv';
        $languages = Language::all();
        $translations = Translation::all();

        // Buat header dinamis berdasarkan bahasa yang ada
        $headers = ['key'];
        foreach ($languages as $language) {
            $headers[] = $language->code;
        }

        return new StreamedResponse(function () use ($translations, $headers, $languages) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers); // Tulis header

            // Tulis setiap baris data
            foreach ($translations as $translation) {
                $row = [$translation->key];
                foreach ($languages as $language) {
                    $row[] = $translation->text[$language->code] ?? '';
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Aksi untuk mengimpor data dari file CSV.
     * @param string $filePath Path sementara dari file yang di-upload.
     */
    public function importFromCsv(string $filePath): void
    {
        try {
            $path = storage_path('app/' . $filePath);
            $file = fopen($path, 'r');

            // Baca header untuk memetakan kolom
            $header = fgetcsv($file);
            if ($header === false) {
                throw new \Exception('File CSV kosong atau tidak valid.');
            }
            
            $importedCount = 0;
            while (($row = fgetcsv($file)) !== false) {
                // Gabungkan header dengan baris data menjadi array asosiatif
                $data = array_combine($header, $row);

                $key = $data['key'] ?? null;
                if (!$key) continue;

                unset($data['key']); // Hapus key dari data agar sisanya adalah bahasa

                // Gunakan updateOrCreate untuk efisiensi
                Translation::updateOrCreate(
                    ['key' => $key],
                    ['text' => $data]
                );
                $importedCount++;
            }
            fclose($file);
            File::delete($path); // Hapus file sementara

            Notification::make()->title('Import Berhasil')->body("{$importedCount} baris berhasil diimpor dan diperbarui.")->success()->send();
        
        } catch (\Exception $e) {
            Notification::make()->title('Import Gagal')->body($e->getMessage())->danger()->send();
        }
    }
    
}
