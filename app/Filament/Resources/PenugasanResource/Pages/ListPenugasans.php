<?php

namespace App\Filament\Resources\PenugasanResource\Pages;

use App\Filament\Resources\PenugasanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder; // <-- IMPORT INI

class ListPenugasans extends ListRecords
{
    protected static string $resource = PenugasanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hapus atau komentari CreateAction standar jika ada:
            // Actions\CreateAction::make(), // Ini akan membuat City baru menggunakan form() resource

            // Tombol ini mengarah ke halaman batch assignment kustom Anda (CreatePenugasan.php)
            Actions\Action::make('create_batch_assignment')
                ->label('Buat Penugasan Baru (Batch)')
                ->url(CreatePenugasan::getUrl()), // Pastikan CreatePenugasan::class bisa di-resolve
        ];
        
    }
    // protected function getTableQuery(): Builder
    // {
    //     // Ambil query dasar dari parent, lalu tambahkan kondisi whereHas
    //     return parent::getTableQuery()
    //                 // Hanya tampilkan City yang memiliki setidaknya satu relasi 'assessors'
    //                 // atau bisa juga menggunakan 'assignments' jika itu lebih sesuai dengan logika Anda
    //                 ->whereHas('assessors');
    //                 // ->whereHas('assignments'); // Alternatif jika menggunakan relasi assignments
    // }
}
