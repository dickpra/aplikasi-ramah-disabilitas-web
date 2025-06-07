<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessorResource\Pages;
use App\Filament\Resources\AssessorResource\RelationManagers;
use App\Models\Assessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash; // Untuk hashing password


class AssessorResource extends Resource
{
    protected static ?string $model = Assessor::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Icon untuk user/asesor
    protected static ?string $navigationLabel = 'Manajemen Asesor'; // Label di navigasi
    protected static ?string $navigationGroup = 'Pengguna'; // Grup navigasi (opsional)
    // protected static ?string $recordTitleAttribute = 'name'; // Menampilkan nama di breadcrumb saat edit (opsional)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true), // Email harus unik, kecuali untuk record yang sedang diedit
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    // Hanya wajib diisi saat membuat asesor baru
                    ->required(fn (string $context): bool => $context === 'create')
                    // Hanya di-hash dan disimpan jika field password diisi (untuk edit)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state)) // Hanya kirim ke database jika diisi
                    ->maxLength(255)
                    ->helperText('Kosongkan jika tidak ingin mengubah password saat edit.'),
                // Tambahkan field lain di sini jika ada, contoh:
                // Forms\Components\TextInput::make('nomor_lisensi')
                //     ->label('Nomor Lisensi')
                //     ->nullable()
                //     ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Alamat Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignments_count')
                    ->counts('assignments') // Menghitung jumlah relasi 'assignments'
                    ->label('Total Tugas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignments_max_updated_at')
                    ->max('assignments', 'updated_at') // Mengambil tanggal update terakhir dari relasi
                    ->label('Aktivitas Terakhir')
                    ->since() // Tampilkan dalam format "x hari yang lalu"
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Terdaftar')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tanggal Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessors::route('/'),
            'create' => Pages\CreateAssessor::route('/create'),
            'edit' => Pages\EditAssessor::route('/{record}/edit'),
            'view' => Pages\ViewAssessor::route('/{record}'), // <-- Halaman View akan menampilkan widget

        ];
    }
}
