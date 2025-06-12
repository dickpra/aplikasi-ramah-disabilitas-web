<?php

namespace App\Filament\Assessor\Pages;

use Faker\Provider\ar_EG\Text;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static string $view = 'filament.assessor.pages.edit-profile';
    protected static ?string $navigationLabel = null;
    protected static ?string $title = null;
    protected static ?string $navigationGroup = null; // Grup menu 'Akun'

    public ?array $data = [];

    // Method ini akan dijalankan saat halaman pertama kali dibuka
    public function mount(): void
    {
        $this->form->fill(Auth::guard('assessor')->user()->attributesToArray());
    }

    public static function getNavigationLabel(): string
    {
        return __('Profil Saya');
    }

    public function getTitle(): string
    {
        return __('Profil Saya');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Akun');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->label(__('Nama Lengkap')),
                TextInput::make('email')->email()->required()->label(__('Alamat Email')),
                TextInput::make('phone_number')->tel()->label(__('Nomor HP')),
                TextInput::make('country')->label(__('Negara')),
                // Select::make('country')
                //     ->label('Negara')
                //     ->options([
                //         'Indonesia' => 'Indonesia',
                //         'Malaysia' => 'Malaysia',
                //         'Singapura' => 'Singapura',
                //         // Tambahkan negara lain jika perlu
                //     ])
                //     ->searchable(),
                TextInput::make('password')
                    ->password()
                    ->label(__('Password Baru'))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(__('Kosongkan jika tidak ingin mengubah password.')),
                TextInput::make('password_confirmation')
                    ->password()
                    ->label(__('Konfirmasi Password Baru'))
                    ->requiredWith('password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $user = Auth::guard('assessor')->user();

            $user->update($data);

            // Refresh data di form setelah update
            $this->form->fill($user->attributesToArray());
            if (isset($data['password'])) {
                // Kosongkan field password setelah berhasil diubah
                $this->form->fill(['password' => null, 'password_confirmation' => null]);
            }
            
            Notification::make()
                ->success()
                ->title(__('Profil Disimpan'))
                ->body(__('Data pribadi Anda telah berhasil diperbarui.'))
                ->send();

        } catch (\Exception $e) {
            Notification::make()->danger()->title(__('Gagal Menyimpan'))->body($e->getMessage())->send();
        }
    }
}