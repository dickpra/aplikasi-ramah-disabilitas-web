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
    protected static ?string $navigationLabel = 'Profil Saya';
    protected static ?string $title = 'Profil Saya';
    protected static ?string $navigationGroup = 'Akun'; // Grup menu 'Akun'

    public ?array $data = [];

    // Method ini akan dijalankan saat halaman pertama kali dibuka
    public function mount(): void
    {
        $this->form->fill(Auth::guard('assessor')->user()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->label('Nama Lengkap'),
                TextInput::make('email')->email()->required()->label('Alamat Email'),
                TextInput::make('phone_number')->tel()->label('Nomor HP'),
                TextInput::make('country')->label('Negara'),
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
                    ->label('Password Baru')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Kosongkan jika tidak ingin mengubah password.'),
                TextInput::make('password_confirmation')
                    ->password()
                    ->label('Konfirmasi Password Baru')
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
                ->title('Profil Disimpan')
                ->body('Data pribadi Anda telah berhasil diperbarui.')
                ->send();

        } catch (\Exception $e) {
            Notification::make()->danger()->title('Gagal Menyimpan')->body($e->getMessage())->send();
        }
    }
}