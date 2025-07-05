<?php
namespace App\Filament\Resources\IndicatorResource\Pages;

use App\Filament\Resources\IndicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\Translatable; // <-- Import Trait
use Stichoza\GoogleTranslate\GoogleTranslate;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Livewire\Attributes\On; // <-- 1. Import Atribut On


class EditIndicator extends EditRecord
{
    protected static string $resource = IndicatorResource::class;

    use EditRecord\Concerns\Translatable;

    #[On('localeSwitched')]
    public function onLocaleSwitched(string $locale): void
    {
        // Panggil metode resmi dari trait Translatable untuk mengganti tab
        $this->setActiveLocale($locale);
    }
    // ---------------------------------
    public function mountTranslatable(): void
    {
        // Secara eksplisit mengatur locale aktif di halaman ini
        // agar cocok dengan locale aplikasi saat ini (yang sudah diatur oleh middleware).
        // Kita panggil metode resmi dari trait untuk mengganti tab.
        $this->setActiveLocale(app()->getLocale());
    }
 
    protected function getHeaderActions(): array
    {
        return [
            // Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
            // Actions\Action::make('auto_translate_fields')
            //     ->label(__('Terjemahkan Otomatis'))
            //     ->icon('heroicon-o-language')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->modalDescription(__('Ini akan menerjemahkan semua field dari Bahasa Indonesia ke bahasa lain yang kosong. Pastikan field Bahasa Indonesia sudah terisi.'))
            //     ->action(function (Get $get, Set $set) {
            //         $sourceLocale = 'id';
            //         $targetLocales = ['en', 'vi']; // Add other target languages here

            //         $translatableFields = (new \App\Models\Indicator())->getTranslatableAttributes();

            //         try {
            //             $tr = new GoogleTranslate();
            //             $tr->setSource($sourceLocale);

            //             foreach ($translatableFields as $field) {
            //                 $sourceText = $get($field);

            //                 if (!empty($sourceText)) {
            //                     foreach ($targetLocales as $targetLocale) {
            //                         $tr->setTarget($targetLocale);
            //                         $translatedText = $tr->translate($sourceText);

            //                         // Set the value for the field in the target language tab
            //                         $set($field . '.' . $targetLocale, $translatedText);
            //                     }
            //                 }
            //             }
            //             Notification::make()->success()->title(__('Penerjemahan Selesai'))->send();
            //         } catch (\Exception $e) {
            //             Notification::make()->danger()->title(__('Error Terjemahan'))->body($e->getMessage())->send();
            //         }
            //     }),
        ];
    }
}