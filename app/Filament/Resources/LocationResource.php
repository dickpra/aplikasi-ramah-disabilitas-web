<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
// use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use App\Models\Country;
use App\Models\Province;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Tables\Columns;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique; // <-- Import untuk aturan unik


class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin'; // Ganti ikon
    protected static ?string $navigationGroup = 'Entitas'; // Atau grup lain yang sesuai
    protected static ?int $navigationSort = 3; // Atur urutan jika perlu

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()->maxLength(255)->label('Nama Lokasi')
                ->rules([
                    fn (Get $get): Unique => (new Unique('locations', 'name'))
                        ->where('province_id', $get('province_id'))
                        ->ignore($form->getModelInstance()),
                ])
                ->validationMessages(['unique' => 'Nama lokasi ini sudah ada di provinsi yang dipilih.']),
            
            Forms\Components\Select::make('location_type')
                ->options([
                    'Kota' => 'Kota', 'Kabupaten' => 'Kabupaten', 'Perguruan Tinggi' => 'Perguruan Tinggi',
                    'Sekolah' => 'Sekolah', 'Ruang Publik' => 'Ruang Publik',
                ])
                ->required()->label('Jenis Lokasi'),
            
            // --- FIELD PEMILIHAN NEGARA (Sudah Benar) ---
            Forms\Components\Select::make('country_id')
                    ->label('Negara')
                    ->options(Country::query()->pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('province_id', null))
                    ->searchable()
                    ->required()
                    ->dehydrated(false)
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nama Negara'),
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Negara'),
                    ])
                    ->createOptionModalHeading('Buat Negara Baru')
                    // --- TAMBAHKAN METODE INI ---
                    ->createOptionUsing(function (array $data): int {
                        // Logika untuk membuat record Country baru
                        $newCountry = Country::create($data);
                        // Kembalikan ID dari record yang baru dibuat
                        return $newCountry->id;
                    }),
            
            // --- FIELD PROVINSI YANG DIPERBAIKI ---
            Forms\Components\Select::make('province_id')
                    ->label('Provinsi')
                    ->options(function (Get $get): Collection {
                        $countryId = $get('country_id');
                        if (!$countryId) {
                            return collect();
                        }
                        return Province::query()->where('country_id', $countryId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->createOptionForm(function (Form $form, Get $get) {
                        return $form->schema([
                            // Field country_id sudah otomatis ada dari createOptionUsing,
                            // jadi kita tidak perlu menampilkannya di form modal.
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Nama Provinsi Baru')
                                ->rules([
                                    fn (): Unique => (new Unique('provinces', 'name'))
                                        ->where('country_id', $get('country_id')),
                                ]),
                        ]);
                    })
                    ->createOptionModalHeading('Buat Provinsi Baru')
                    // --- TAMBAHKAN METODE INI ---
                    ->createOptionUsing(function (array $data, Get $get): int {
                        // Ambil country_id dari form utama dan gabungkan dengan data dari form modal
                        $data['country_id'] = $get('country_id');
                        $newProvince = Province::create($data);
                        return $newProvince->id;
                    }),
            ]);
}
    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\TextInput::make('name')
    //                 ->required()
    //                 ->maxLength(255)
    //                 ->label('Nama Lokasi'),
    //             // Forms\Components\TextInput::make('location_type')
    //             //     ->required() // Jadikan required jika tipe selalu ada
    //             //     ->maxLength(255)
    //             //     ->label('Jenis Lokasi')
    //             //     ->helperText('Contoh: Kota, Kabupaten, Perguruan Tinggi, Sekolah, Taman, dll.'),
    //                 // Anda bisa ganti dengan Select jika jenisnya baku:
    //                 Forms\Components\Select::make('location_type')
    //                     // ->options([
    //                     //     'city' => 'Kota',
    //                     //     'regency' => 'Kabupaten',
    //                     //     'university' => 'Perguruan Tinggi',
    //                     //     'school' => 'Sekolah',
    //                     //     'public_space' => 'Ruang Publik',
    //                     // ])
    //                 ->options([
    //                         'Kota' => 'Kota',
    //                         'Kabupaten' => 'Kabupaten',
    //                         'Perguruan Tinggi' => 'Perguruan Tinggi',
    //                         'Sekolah' => 'Sekolah',
    //                         'Ruang Publik' => 'Ruang Publik',
    //                     ])
    //                     ->required()
    //                     ->label('Jenis Lokasi'),

    //             Forms\Components\Select::make('province_id')
    //                 ->relationship('province', 'name')
    //                 ->searchable()
    //                 ->preload()
    //                 ->required()
    //                 ->label('Provinsi')
    //                 ->createOptionForm(fn(Form $form) => \App\Filament\Resources\ProvinceResource::form($form)) // Menggunakan form dari ProvinceResource
    //                 ->createOptionModalHeading('Buat Provinsi Baru'),
                    

    //             // Tambahkan field opsional lain yang sudah ada di tabel locations jika ingin diinput dari sini
    //             // Misalnya, jika Anda menambahkan 'address', 'latitude', 'longitude' ke tabel locations di masa depan.
    //             // Forms\Components\Textarea::make('address')->columnSpanFull(),
    //             // Forms\Components\TextInput::make('latitude')->numeric(),
    //             // Forms\Components\TextInput::make('longitude')->numeric(),
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('final_score', 'desc')
            // ->defaultSort('final_assessment.final_score') // Urutkan berdasarkan tanggal dibuat
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->label('Nama Lokasi'),
                Tables\Columns\TextColumn::make('location_type')->searchable()->sortable()->label('Jenis Lokasi'),
                Tables\Columns\TextColumn::make('province.name')->searchable()->sortable()->label('Provinsi'),
                Tables\Columns\TextColumn::make('province.country.name')->searchable()->sortable()->label('Negara'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // --- KOLOM BARU UNTUK SKOR AKHIR ---
                Tables\Columns\TextColumn::make('final_assessment.final_score')
                    ->label('Skor Akhir')
                    ->numeric(3) // Tampilkan 3 angka di belakang koma
                    ->sortable(false) // Sorting pada kolom kalkulasi perlu query custom, nonaktifkan dulu
                    ->placeholder('Belum Dinilai'),

                // --- KOLOM BARU UNTUK PERINGKAT ---
                Tables\Columns\TextColumn::make('final_assessment.rank')
                    ->label('Peringkat')
                    ->badge()
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'DIAMOND' => 'info',
                        'GOLD' => 'success',
                        'SILVER' => 'warning',
                        'BRONZE' => 'gray',
                        default => 'danger',
                    })
                    ->placeholder('N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('province_id')
                    ->relationship('province', 'name')
                    ->label('Filter berdasarkan Provinsi'),
                Tables\Filters\SelectFilter::make('location_type') // Jika jenis lokasi beragam
                    ->options(fn () => Location::query()->select('location_type')->distinct()->pluck('location_type', 'location_type')->all())
                    ->label('Filter berdasarkan Jenis Lokasi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            // 'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}