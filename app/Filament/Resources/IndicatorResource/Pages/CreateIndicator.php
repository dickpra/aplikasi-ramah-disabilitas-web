<?php

namespace App\Filament\Resources\IndicatorResource\Pages;

use App\Filament\Resources\IndicatorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateIndicator extends CreateRecord
{
    use Translatable;

    protected static string $resource = IndicatorResource::class;

    /**
     * Kita menimpa (override) metode mountTranslatable dari trait.
     * Ini akan MENCEGAHNYA mengatur ulang locale ke bahasa default.
     */
    public function mountTranslatable(): void
    {
        // Secara paksa mengatur locale aktif di halaman ini agar cocok dengan
        // locale aplikasi saat ini (yang sudah diatur oleh middleware Anda).
        // Karena tidak ada metode setActiveLocale() di trait ini,
        // kita langsung mengubah propertinya.
        $this->activeLocale = app()->getLocale();
    }

    // Anda bisa mengosongkan getHeaderActions jika tidak ada tombol lain.
    // LocaleSwitcher dari Spatie tidak lagi kita perlukan di sini.
    protected function getHeaderActions(): array
    {
        return [];
    }
}