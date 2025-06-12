<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Location; // Import
use Filament\Notifications\Notification; // Import

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(), // Tombol create standar

            // --- AKSI BARU UNTUK MENGHITUNG SKOR ---
            Actions\Action::make('calculateAllScores')
                ->label(__('Hitung Ulang Semua Skor & Peringkat'))
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('Proses ini akan menghitung ulang skor akhir dan peringkat untuk semua lokasi yang penilaiannya sudah disetujui. Lanjutkan?'))
                ->action(function () {
                    $locationsToRecalculate = Location::whereHas('assignments', fn($q) => $q->where('status', 'approved'))->get();
                    $count = 0;
                    foreach ($locationsToRecalculate as $location) {
                        $result = $location->calculateFinalScore(); // Panggil metode yang sudah kita buat
                        if ($result) {
                            $location->final_score = $result['final_score'];
                            $location->rank = $result['rank'];
                            $location->save();
                            $count++;
                        }
                    }
                    Notification::make()
                        ->success()
                        ->title(__('Perhitungan Selesai'))
                        ->body(__("{$count} lokasi telah berhasil dihitung ulang skornya."))
                        ->send();
                }),
        ];
    }
}