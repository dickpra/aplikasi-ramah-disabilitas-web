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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
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
                    ->label('Export ke CSV (Backup)')
                    ->icon('heroicon-o-table-cells')
                    ->action('exportToCsv'),
                
                Actions\Action::make('importCsv')
                    ->label('Import dari CSV (Backup)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('File CSV')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ])
                    ->action(fn(array $data) => $this->importFromCsv($data['attachment'])),

                // **TOMBOL BARU DITAMBAHKAN DI SINI**
                Actions\Action::make('exportLangToZip')
                    ->label('Export Folder Lang (.zip)')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->action('exportLangToZip'),
                
                Actions\Action::make('importLangFilesFromZip')
                    ->label('Import Folder Lang (.zip)')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->modalDescription('Upload satu file .zip yang berisi semua file bahasa Anda (e.g., id.json, en.json) untuk memperbarui server.')
                    ->form([
                        Forms\Components\FileUpload::make('zip_file')
                            ->label('File .zip Bahasa')
                            ->required()
                            ->acceptedFileTypes(['application/zip']),
                    ])
                    ->action(fn(array $data) => $this->importLangFilesFromZip($data['zip_file'])),
            ])->label('Import/Export File')->button()->icon('heroicon-o-document-arrow-down')->color('info'),

                        
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
        Notification::make()->title('Scanning started...')->body('The system is now scanning project files.')->info()->send();
        
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
                Notification::make()->title('Scan Failed')->body('No languages found. Please add a language first.')->danger()->send();
                return;
            }

            $defaultLangCode = Language::where('is_default', true)->first()?->code;

            // **== PERUBAHAN UTAMA DI SINI ==**
            // 1. Ambil semua key yang ada dari database.
            $existingKeys = Translation::pluck('key')->all();
            // 2. Buat versi huruf kecil dari semua key tersebut untuk perbandingan yang case-insensitive.
            $existingKeysLower = array_map('strtolower', $existingKeys);

            foreach ($uniqueKeys as $key) {
                // 3. Bandingkan versi huruf kecil dari key baru dengan daftar huruf kecil yang sudah ada.
                if (!in_array(strtolower($key), $existingKeysLower)) {
                    $newlyAdded++;
                    $textValues = [];
                    foreach ($languages as $language) {
                        if ($defaultLangCode && $language->code === $defaultLangCode) {
                            $textValues[$language->code] = $key; // Tetap simpan dengan case asli
                        } else {
                            $textValues[$language->code] = '';
                        }
                    }
                    // 4. Buat record baru. Ini dijamin tidak akan duplikat lagi.
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
     * Import CSV ke tabel `translations`.
     *
     * @param  UploadedFile|TemporaryUploadedFile|string  $file
     */
    protected function importFromCsv($file): void
{
    try {
        // 1) Tentukan path fisik
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $cleanup = fn() => $file->delete();
        } elseif (is_string($file)) {
            // coba di disk lokal
            if (Storage::disk('local')->exists($file)) {
                $path = Storage::disk('local')->path($file);
                $cleanup = fn() => Storage::disk('local')->delete($file);
            }
            // jika tidak, coba di disk public
            elseif (Storage::disk('public')->exists($file)) {
                $path = Storage::disk('public')->path($file);
                $cleanup = fn() => Storage::disk('public')->delete($file);
            } else {
                throw new \Exception("File CSV tidak ditemukan: {$file}");
            }
        } else {
            throw new \Exception('Tipe file tidak didukung.');
        }

        if (! file_exists($path)) {
            throw new \Exception("Path file tidak ada: {$path}");
        }

        // 2) Buka CSV
        if (! $handle = fopen($path, 'r')) {
            throw new \Exception("Gagal membuka file CSV: {$path}");
        }

        // 3) Baca header
        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw new \Exception('Header CSV kosong atau tidak valid.');
        }

        // 4) Proses baris demi baris
        $importedCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row) ?: [];
            if (empty($data['key'])) {
                continue;
            }
            $key = $data['key'];
            unset($data['key']);

            Translation::updateOrCreate(
                ['key'  => $key],
                ['text' => $data]
            );

            $importedCount++;
        }
        fclose($handle);

        // 5) Hapus file
        $cleanup();

        // 6) Notifikasi sukses
        Notification::make()
            ->title('Import Berhasil')
            ->body("{$importedCount} baris berhasil diimpor.")
            ->success()
            ->send();
    } catch (\Exception $e) {
        Notification::make()
            ->title('Import Gagal')
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}

public function exportLangToZip()
    {
        try {
            $langPath = lang_path();
            $files = File::files($langPath);

            if (empty($files)) {
                Notification::make()->title('Aksi Dibatalkan')->body('Folder lang tidak berisi file .json untuk diekspor.')->warning()->send();
                return;
            }

            $zip = new ZipArchive();
            $zipFileName = 'lang_files_' . now()->format('Y-m-d') . '.zip';
            // Buat file zip di lokasi temporary
            $tempZipPath = tempnam(sys_get_temp_dir(), 'zip');

            if ($zip->open($tempZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Tidak dapat membuat file .zip.');
            }

            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $zip->addFile($file->getRealPath(), $file->getFilename());
                }
            }
            $zip->close();
            
            // Kirim file zip ke browser dan hapus setelah terkirim
            return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()->title('Export Gagal')->body($e->getMessage())->danger()->send();
            return null;
        }
    }

protected function importLangFilesFromZip($file): void
    {
        try {
            // 1) Tentukan path fisik menggunakan logika yang terbukti andal
            $path = null;
            $cleanup = function() {}; // Default cleanup function
            if ($file instanceof UploadedFile) {
                $path = $file->getRealPath();
            } elseif (is_string($file)) {
                if (Storage::disk('local')->exists($file)) {
                    $path = Storage::disk('local')->path($file);
                    $cleanup = fn() => Storage::disk('local')->delete($file);
                } elseif (Storage::disk('public')->exists($file)) {
                    $path = Storage::disk('public')->path($file);
                    $cleanup = fn() => Storage::disk('public')->delete($file);
                } else {
                    throw new \Exception("File .zip tidak ditemukan: {$file}");
                }
            } else {
                throw new \Exception('Tipe file tidak didukung.');
            }

            if (! file_exists($path)) {
                throw new \Exception("Path file tidak ada: {$path}");
            }

            // 2) Buka dan proses file .zip
            $zip = new ZipArchive;
            $res = $zip->open($path);
            $filesUpdated = 0;

            if ($res === TRUE) {
                $langDestinationPath = lang_path();
                if (!File::isDirectory($langDestinationPath)) {
                    File::makeDirectory($langDestinationPath, 0755, true, true);
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileName = $zip->getNameIndex($i);
                    if (pathinfo($fileName, PATHINFO_EXTENSION) === 'json' && !str_contains($fileName, '/')) {
                        $fileContent = $zip->getFromIndex($i);
                        File::put($langDestinationPath . '/' . $fileName, $fileContent);
                        $filesUpdated++;
                    }
                }
                $zip->close();
            } else {
                throw new \Exception('Gagal membuka file .zip.');
            }

            // 3) Hapus file sementara jika perlu
            $cleanup();

            // 4) Notifikasi sukses
            Notification::make()->title('Update Berhasil')->body("{$filesUpdated} file bahasa berhasil diupdate.")->success()->send();

        } catch (\Exception $e) {
            Notification::make()->title('Update Gagal')->body($e->getMessage())->danger()->send();
        }
    }
    
}