<?php

namespace App\Filament\Resources\LanguageResource\Pages;

use App\Filament\Resources\LanguageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\File;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Hook yang berjalan TEPAT SEBELUM data baru disimpan ke database.
     */
    protected function beforeSave(): void
    {
        // Ambil data asli dari record sebelum ada perubahan
        $originalRecord = $this->getRecord();
        $originalCode = $originalRecord->code;

        // Ambil data baru dari form yang telah diisi
        $newData = $this->data;
        $newCode = $newData['code'];

        // Lakukan aksi hanya jika kode bahasa benar-benar berubah
        if ($originalCode !== $newCode) {
            $this->renameLangFile($originalCode, $newCode);
        }
    }

    /**
     * Fungsi untuk mengganti nama file .json di folder lang.
     */
    protected function renameLangFile(string $oldCode, string $newCode): void
    {
        $oldPath = lang_path($oldCode . '.json');
        $newPath = lang_path($newCode . '.json');

        // Pastikan file lama ada sebelum mencoba memindahkannya/mengganti namanya
        if (File::exists($oldPath)) {
            File::move($oldPath, $newPath);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
