<?php

namespace App\Filament\Resources\LanguageResource\Pages;

use App\Filament\Resources\LanguageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Hook yang berjalan setelah record berhasil dibuat.
     */
    protected function afterCreate(): void
    {
        $language = $this->getRecord();
        $this->createLangFile($language->code);
    }

    /**
     * Fungsi untuk membuat file bahasa .json yang kosong.
     * Ini diperlukan agar Laravel mengenali locale-nya.
     */
    protected function createLangFile(string $code): void
    {
        $path = lang_path($code . '.json');

        if (!File::exists($path)) {
            // Cukup buat file JSON kosong
            File::put($path, "{\n\n}");
        }
    }
}
