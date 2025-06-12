<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndicatorResource\Pages;
// use App\Filament\Resources\IndicatorResource\RelationManagers; // Jika ada relasi nanti
use App\Models\Indicator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Location;
use Filament\Tables\Filters\TernaryFilter;


class IndicatorResource extends Resource
{
    protected static ?string $model = Indicator::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb'; // Atau ikon lain yang sesuai
    // protected static ?string $navigationGroup = 'Operasional'; // Atau grup 'Entitas' jika Anda mau
    protected static ?int $navigationSort = 2;    

    public static function getNavigationGroup(): ?string
    {
        return __('Operasional'); // Grup navigasi untuk Asesor
    }
    public static function getNavigationLabel(): string
    {
        return __('Indikator Penilaian');
    }
    public static function getPluralModelLabel(): string
    {
        return __('Indikator Penilaian');
    }
    public static function getModelLabel(): string
    {
        return __('Indikator');
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informasi Dasar Indikator'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('name')
                            ->required()
                            ->label(__('Nama/Deskripsi Indikator'))
                            ->columnSpanFull()
                            ->helperText(__('Isi dengan pertanyaan atau pernyataan lengkap dari indikator.')),
                        Forms\Components\TextInput::make('category')
                            ->label(__('Kategori Indikator'))
                            ->helperText(__('Contoh: Dukungan Pemerintah, Pendidikan, Infrastruktur.'))
                            ->maxLength(255),
                        Forms\Components\Select::make('target_location_type')
                            ->label(__('Target Jenis Lokasi'))
                            ->options(function (): array {
                                // Ambil semua nilai unik dari kolom 'location_type' di tabel 'locations'
                                $locationTypes = Location::query()
                                    ->select('location_type')
                                    ->whereNotNull('location_type') // Hanya ambil yang tidak null
                                    ->where('location_type', '!=', '') // Hanya ambil yang tidak string kosong
                                    ->distinct()
                                    ->pluck('location_type', 'location_type') // Gunakan location_type sebagai key dan value
                                    ->all();
                                
                                // Tambahkan opsi 'all' secara manual jika selalu diperlukan
                                $locationTypes['all'] = __('Semua Jenis Lokasi (all)');

                                // Urutkan berdasarkan key (opsional, untuk tampilan yang rapi)
                                ksort($locationTypes);

                                return $locationTypes;
                            })
                            ->searchable()
                            ->helperText(__('Untuk jenis lokasi mana indikator ini berlaku.')),
                        Forms\Components\TextInput::make('weight')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->label(__('Bobot Indikator')),
                        Forms\Components\Select::make('scale_type')
                            ->label(__('Tipe Skala Penilaian'))
                            ->options([
                                'Skala 1-2' => __('Skala 1-2'),
                                'Skala 1-3' => __('Skala 1-3'),
                                'Skala 1-4' => __('Skala 1-4'),
                                'Skala 1-5' => __('Skala 1-5'),
                                // 'Ya/Tidak' => 'Ya/Tidak',
                                // 'Ada/Tidak Ada' => 'Ada/Tidak Ada',
                                'Kustom' => __('Kustom (Lihat Kriteria)'),
                            ])
                            ->searchable()
                            ->helperText(__('Jenis skala yang digunakan untuk menilai.')),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->label(__('Status Aktif')),
                    ]),

                Forms\Components\Section::make(__('Detail Panduan untuk Asesor'))
                    ->schema([
                        Forms\Components\Textarea::make('keywords')
                            ->label(__('Kata Kunci (Keywords)'))
                            ->helperText(__('Kata kunci untuk membantu asesor melakukan pencarian atau observasi.'))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('measurement_method')
                            ->label(__('Cara Pengukuran'))
                            ->helperText(__('Metode atau langkah yang disarankan untuk asesor menilai indikator ini.'))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('scoring_criteria_text')
                            ->label(__('Teks Kriteria Penilaian'))
                            ->helperText(__('Deskripsi detail level-level kriteria penilaian dan skor terkaitnya. Ini akan ditampilkan ke asesor.'))
                            ->columnSpanFull()
                            ->rows(6), // Beri lebih banyak baris
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Indikator'))
                    ->limit(50)
                    ->tooltip(fn (Indicator $record): string => $record->name) // Tooltip untuk teks penuh
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('Kategori'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_location_type')
                    ->label(__('Target Lokasi'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label(__('Bobot'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Terakhir Diubah'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => Indicator::query()->select('category')->distinct()->whereNotNull('category')->pluck('category', 'category')->all())
                    ->label(__('Filter Kategori')),
                Tables\Filters\SelectFilter::make('target_location_type')
                    //  ->options([
                    //     'city' => 'Kota (City)',
                    //     'regency' => 'Kabupaten (Regency)',
                    //     'university' => 'Perguruan Tinggi',
                    //     'school' => 'Sekolah',
                    //     'company' => 'Perusahaan',
                    //     'public_facility' => 'Fasilitas Publik',
                    //     'all' => 'Semua Jenis Lokasi',
                    // ])
                    ->options([
                            'Kota' => 'Kota',
                            'Kabupaten' => 'Kabupaten',
                            'Perguruan Tinggi' => 'Perguruan Tinggi',
                            'Sekolah' => 'Sekolah',
                            'Ruang Publik' => 'Ruang Publik',
                            'all' => 'Semua Jenis Lokasi',
                        ])

                    ->label(__('Filter Target Lokasi')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status Aktif')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Tambahkan jika perlu, atau custom action untuk Nonaktifkan
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
            // Jika ada relasi yang ingin ditampilkan sebagai tabel di bawah form (misal, skor yang sudah masuk)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIndicators::route('/'),
            'create' => Pages\CreateIndicator::route('/create'),
            // 'view' => Pages\ViewIndicator::route('/{record}'), // Pastikan ada ViewIndicator page
            'edit' => Pages\EditIndicator::route('/{record}/edit'),
        ];
    }
}