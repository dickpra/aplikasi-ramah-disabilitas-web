<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Location;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class LocationRankingWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    // protected static ?string $heading = null;

    public static function getHeading(): ?string
    {
        return __('Peringkat Lokasi');
    }

    // Metode getHeaderActions() bisa kita hapus karena logikanya dipindah ke dalam table()
    // protected function getHeaderActions(): array { ... }

    public function table(Table $table): Table
    {
        return $table
            // --- 1. UBAH QUERY UNTUK MENGAMBIL SEMUA LOKASI ---
            ->query(
                Location::query()
                    // Tidak ada lagi whereNotNull, jadi semua lokasi akan tampil
                    ->with('province.country') // Tetap gunakan eager loading untuk performa
            )
            ->defaultSort('final_score', 'desc') // Urutkan berdasarkan skor tertinggi (yang null akan di bawah)

            // --- 2. PINDAHKAN AKSI EKSPOR KE SINI ---
            ->headerActions([
                ExportAction::make()
                    ->label(__('Ekspor ke Excel'))
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('Peringkat Lokasi - ' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ]),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->label(__('Nama Lokasi')),
                Tables\Columns\TextColumn::make('province.country.name')->searchable()->sortable()->label(__('Negara')),
                Tables\Columns\TextColumn::make('province.name')->searchable()->sortable()->label(__('Provinsi')),

                Tables\Columns\TextColumn::make('final_score')
                    ->label(__('Skor Akhir'))
                    ->numeric(3)
                    ->sortable()
                    ->placeholder(__('Belum Dinilai')), // Akan tampil untuk lokasi yang skornya null

                Tables\Columns\TextColumn::make('rank')
                    ->label(__('Peringkat'))
                    ->badge()
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'DIAMOND' => 'info',
                        'GOLD' => 'success',
                        'SILVER' => 'warning',
                        'BRONZE' => 'gray',
                        default => 'danger',
                    })
                    ->sortable()
                    ->placeholder(__('Belum Ada Peringkat')), // Akan tampil untuk lokasi yang peringkatnya null
            ]);
    }
}