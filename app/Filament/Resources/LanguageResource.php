<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    // Konfigurasi untuk menempatkannya di sidebar
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?int $navigationSort = 1; // Tampil sebelum Translation

    public static function getNavigationGroup(): ?string
    {
        return __('Pengaturan');
    }

    public static function getNavigationLabel(): string
    {
        return __('Bahasa');
    }
    public static function getPluralModelLabel(): string
    {
        return __('Bahasa');
    }
    public static function getModelLabel(): string
    {
        return __('Bahasa');
    }
    /**
     * Mendefinisikan form untuk membuat dan mengedit data bahasa.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nama Bahasa'))
                    ->placeholder(__('e.g., Indonesia'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->label(__('Kode Bahasa'))
                    ->placeholder(__('e.g., id'))
                    ->helperText(__('Gunakan kode standar ISO 639-1 (contoh: id, en, es, fr).'))
                    ->required()
                    ->unique(ignoreRecord: true) // Kode harus unik
                    ->maxLength(10),

                Forms\Components\Toggle::make('is_default')
                    ->label(__('Jadikan Bahasa Default?'))
                    ->helperText(__('Hanya satu bahasa yang bisa menjadi default. Mengaktifkan ini akan menonaktifkan default yang lama.'))
                    ->required()
                    // Logika untuk memastikan hanya ada satu default
                    ->afterStateUpdated(function (bool $state, ?Model $record) {
                        if ($state) {
                            // Jika toggle diaktifkan, set semua record lain ke false
                            Language::whereNot('id', $record?->id)->update(['is_default' => false]);
                        }
                    }),
            ]);
    }

    /**
     * Mendefinisikan tabel untuk menampilkan daftar bahasa.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nama Bahasa'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label(__('Kode'))
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('Default'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Dibuat Pada'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            // Cek jika ada yang mencoba menghapus bahasa default
                            if ($records->where('is_default', true)->isNotEmpty()) {
                                Notification::make()
                                    ->title(__('Aksi Dibatalkan'))
                                    ->body(__('Anda tidak dapat menghapus bahasa yang menjadi default.'))
                                    ->danger()
                                    ->send();
                                return;
                            }
                            // Jika aman, hapus record
                            $records->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
