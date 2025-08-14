<?php

namespace App\Filament\Pages\Admin;

use App\Models\DashboardSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageDashboardSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static string $view = 'filament.pages.admin.manage-dashboard-settings';
    protected static ?string $title = 'Pengaturan Tampilan Dashboard';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public DashboardSetting $settings;

    public function mount(): void
    {
        // Ambil baris pertama, atau buat jika belum ada. Ini memastikan selalu ada 1 baris.
        $this->settings = DashboardSetting::firstOrCreate([]);
        $this->form->fill($this->settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hero/Dashboard Utama')->schema([
                    TextInput::make('hero_title')->label('Judul Utama'),
                    Textarea::make('hero_subtitle')->label('Subjudul/Deskripsi'),
                ]),

                Section::make('Konten Halaman')->schema([
                    RichEditor::make('about_me')->label('Konten About Me'),
                    RichEditor::make('credit')->label('Konten Credit'),
                    RichEditor::make('guidebook')->label('Konten Guidebook'),
                    RichEditor::make('metodologi')->label('Konten Metodologi'),
                ]),

                Section::make('Kontak (Footer)')->schema([
                    TextInput::make('contact_email')->label('Email Kontak')->email(),
                    TextInput::make('contact_phone')->label('Telepon Kontak'),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $this->settings->update($data);

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->submit('save'),
        ];
    }
}