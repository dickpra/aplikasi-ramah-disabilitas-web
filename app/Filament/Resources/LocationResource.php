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
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Lokasi')
                    ->rules([
                    fn (Forms\Get $get): Unique => (new Unique('locations', 'name'))
                        ->where('province_id', $get('province_id'))
                        ->ignore($form->getModelInstance()),
                ])
                ->validationMessages([
                    'unique' => 'Nama lokasi ini sudah ada di provinsi yang dipilih.',
                ]),
                
                Forms\Components\Select::make('location_type')
                    ->options([
                        'Kota' => 'Kota',
                        'Kabupaten' => 'Kabupaten',
                        'Perguruan Tinggi' => 'Perguruan Tinggi',
                        'Sekolah' => 'Sekolah',
                        'Ruang Publik' => 'Ruang Publik',
                    ])
                    ->required()
                    ->label('Jenis Lokasi'),
                
                // --- FIELD BARU: PEMILIHAN NEGARA ---
                Forms\Components\Select::make('country_id')
                    ->label('Negara')
                    ->options(Country::query()->pluck('name', 'id'))
                    ->live() // <-- Membuat field ini "live" atau reaktif
                    ->afterStateUpdated(fn (Set $set) => $set('province_id', null)) // Reset pilihan provinsi jika negara diubah
                    ->searchable()
                    ->required()
                    ->dehydrated(false), // Penting: agar field ini tidak disimpan ke tabel 'locations'
                
                // --- FIELD PROVINSI YANG DISESUAIKAN ---
                Forms\Components\Select::make('province_id')
                    ->label('Provinsi')
                    // Opsi provinsi sekarang bergantung pada pilihan negara
                    ->options(function (Get $get): Collection {
                        $countryId = $get('country_id'); // Ambil ID negara yang dipilih
                        if (!$countryId) {
                            return collect(); // Jika tidak ada negara dipilih, kosongkan pilihan provinsi
                        }
                        return Province::query()
                            ->where('country_id', $countryId)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    // ->createOptionForm(...) // Untuk saat ini kita nonaktifkan createable province di sini
                                             // karena perlu penanganan lebih lanjut untuk dependent field
                    ->label('Provinsi'),
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