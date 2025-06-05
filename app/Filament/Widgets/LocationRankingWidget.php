<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Location;

class LocationRankingWidget extends BaseWidget
{
    protected static ?int $sort = 2; // Urutan widget di dashboard
    protected int | string | array $columnSpan = 'full'; // Agar widget ini memakai lebar penuh

    public function table(Table $table): Table
    {
        return $table
            // Query sekarang lebih sederhana, hanya filter yang skornya tidak null
            ->query(Location::query('final_score'))
            ->defaultSort('final_score', 'desc') // <-- SEKARANG BISA DI-SORTING!
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->label('Nama Lokasi'),
                Tables\Columns\TextColumn::make('province.country.name')
                    ->searchable()
                    ->sortable()
                    ->label('Negara'),
                Tables\Columns\TextColumn::make('province.name')->searchable()->label('Provinsi'),
                // Tables\Columns\TextColumn::make('country.name')->searchable()->label('Negara'),

                // Gunakan kolom fisik, bukan accessor lagi
                Tables\Columns\TextColumn::make('final_score')
                    ->label('Skor Akhir')
                    ->numeric(3)
                    ->sortable() // <-- Aktifkan sorting
                    ->placeholder('Belum Dinilai'),

                Tables\Columns\TextColumn::make('rank')
                    ->label('Peringkat')
                    ->badge()
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'DIAMOND' => 'info',
                        'GOLD' => 'success',
                        'SILVER' => 'warning',
                        'BRONZE' => 'gray',
                        default => 'danger',
                    })
                    ->sortable() // <-- Aktifkan sorting
                    ->placeholder('Belum Ada Peringkat')
            ]);
    }
}