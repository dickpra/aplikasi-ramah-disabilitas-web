<?php

namespace App\Filament\Resources\IndicatorResource\Pages;

use App\Filament\Resources\IndicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Jobs\AutoTranslateIndicatorFields; // <-- 1. Import Job
use App\Models\Indicator; // <-- 2. Import Model
use Filament\Notifications\Notification; // <-- 3. Import Notifikasi
use Livewire\Attributes\On; // <-- 1. Import Atribut On


class ListIndicators extends ListRecords
{
    protected static string $resource = IndicatorResource::class;

    use ListRecords\Concerns\Translatable;
    
    public function mountTranslatable(): void
    {
        // Secara paksa mengatur locale aktif di halaman ini agar cocok dengan
        // locale aplikasi saat ini (yang sudah diatur oleh middleware).
        $this->activeLocale = app()->getLocale();
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\LocaleSwitcher::make(),
            // Actions\CreateAction::make(),
            Actions\CreateAction::make()
            ->label(__('Buat Indikator/Instrument Baru')),
            
            // --- TOMBOL AKSI BARU UNTUK TERJEMAHKAN SEMUA ---
            Actions\Action::make('auto_translate_all')
                ->label(__('Terjemahkan Semua yang Kosong'))
                ->icon('heroicon-o-globe-alt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('Terjemahkan Semua Indikator'))
                ->modalDescription(__('Ini akan memulai proses penerjemahan otomatis untuk semua indikator yang terjemahannya masih kosong. Proses berjalan di latar belakang. Lanjutkan?'))
                ->action(function () {
                    // Ambil semua indikator
                    $indicators = Indicator::all();
                    $dispatchedCount = 0;

                    foreach ($indicators as $indicator) {
                        // Memicu job untuk setiap indikator
                        AutoTranslateIndicatorFields::dispatch($indicator, auth()->user());
                        $dispatchedCount++;
                    }
                    
                    // Beri feedback instan ke admin
                    Notification::make()
                        ->title(__('Proses Terjemahan Massal Dimulai'))
                        ->body(__("{$dispatchedCount} indikator telah ditambahkan ke antrian untuk diterjemahkan."))
                        ->info()
                        ->send();
                }),
        ];
    }
}
