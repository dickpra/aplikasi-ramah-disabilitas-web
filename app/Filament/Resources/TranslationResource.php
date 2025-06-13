<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TranslationResource\Pages;
use App\Models\Language;
use App\Models\Translation;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    // protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    // protected static ?string $label = 'Translation';

    public static function getNavigationGroup(): ?string
    {
        return __('Pengaturan');
    }

    public static function getNavigationLabel(): string
    {
        return __('Translation');
    }
    public static function getPluralModelLabel(): string
    {
        return __('Translation');
    }
    public static function getModelLabel(): string
    {
        return __('Translation');
    }

    public static function form(Form $form): Form
    {
        $languages = Language::all();
        $languageFields = [];

        foreach ($languages as $language) {
            $languageFields[] = TextInput::make("text.{$language->code}")
            ->label(__("Teks ({$language->name})"));
        }

        return $form
            ->schema([
                // Field 'group' sudah dihapus.
                TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),

                Section::make('Translations')
                    ->schema($languageFields),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                // Kolom 'group' sudah dihapus.
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->limit(50)
                    // ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('text')
                    ->label(__('Default Teks'))
                    ->limit(50)
                    ->wrap()
                    ->formatStateUsing(function ($state, Translation $record) {
                        $defaultLangCode = Language::where('is_default', true)->first()?->code ?? config('app.fallback_locale');
                        return $record->text[$defaultLangCode] ?? 'N/A';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Dibuat Pada'))
                    ->sortable()
                    ->formatStateUsing(function ($state, Translation $record) {
                        return $record->created_at->format('d/m/Y H:i:s');
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Diupdate Pada'))
                    ->sortable()
                    ->formatStateUsing(function ($state, Translation $record) {
                        return $record->created_at->format('d/m/Y H:i:s');
                    }),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('created_at_order')
                    ->label('Urutan Waktu')
                    ->options([
                        'newest' => 'Baru Dibuat',
                        'oldest' => 'Terlama',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'newest' => $query->orderByDesc('created_at'),
                            'oldest' => $query->orderBy('created_at'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTranslations::route('/'),
            'create' => Pages\CreateTranslation::route('/create'),
            'edit' => Pages\EditTranslation::route('/{record}/edit'),
        ];
    }
}
