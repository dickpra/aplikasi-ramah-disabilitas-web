<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
// use App\Filament\Resources\CountryResource\RelationManagers; // Kita belum buat relation manager
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    // protected static ?string $navigationGroup = 'Entitas'; // Grup navigasi
    protected static ?int $navigationSort = 1; // Urutan di grup
    public static function getNavigationGroup(): ?string
    {
        return __('Entitas');
    }
    public static function getNavigationLabel(): string
    {
        return __('Negara');
    }
    public static function getPluralModelLabel(): string
    {
        return __('Negara');
    }
    public static function getModelLabel(): string
    {
        return __('Negara');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, table: 'countries', column: 'name') // Pastikan nama unik
                    ->label(__('Nama Negara')),
                Forms\Components\TextInput::make('code')
                    ->maxLength(10)
                    ->unique(ignoreRecord: true, table: 'countries', column: 'code') // Pastikan kode unik
                    ->label(__('Kode Negara (Singkat)')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label(__('Nama'))
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('code')
                ->label(__('Kode'))
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(), // Tambahkan view action
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ProvincesRelationManager::class, // Bisa ditambahkan nanti jika perlu
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
            // 'view' => Pages\ViewCountry::route('/{record}'), // Tambahkan route view
        ];
    }
}