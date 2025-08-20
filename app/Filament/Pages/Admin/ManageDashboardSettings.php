<?php

namespace App\Filament\Pages\Admin;

use App\Models\DashboardSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

                Section::make('Konten Halaman')
                    ->description('Bangun konten halaman Anda dengan menambahkan dan menyusun blok di bawah ini.')
                    ->collapsible()
                    ->schema([
                        // Builder untuk Konten "About Me"
                        Builder::make('about_me')
                            ->label('Konten About Me')
                            ->blocks($this->getContentBlocks()) // Memanggil metode untuk blok
                            ->columnSpanFull(),

                        // Builder untuk Konten "Credit"
                        Builder::make('credit')
                            ->label('Konten Credit')
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),
                        
                        // Builder untuk Konten "Guidebook"
                        Builder::make('guidebook')
                            ->label('Konten Guidebook')
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),

                        // Builder untuk Konten "Metodologi"
                        Builder::make('metodologi')
                            ->label('Konten Metodologi')
                            ->blocks($this->getContentBlocks())
                            ->columnSpanFull(),
                    ]),

                Section::make('Kontak (Footer)')->schema([
                    TextInput::make('contact_email')->label('Email Kontak')->email(),
                    TextInput::make('contact_phone')->label('Telepon Kontak'),
                ]),
            ])
            ->statePath('data');
    }

    /**
     * Mendefinisikan blok-blok yang bisa digunakan di dalam Builder.
     * Dibuat menjadi metode terpisah agar bisa digunakan kembali.
     */
    protected function getContentBlocks(): array
{
    return [
        Builder\Block::make('heading')
            ->label('Judul')
            ->icon('heroicon-o-bookmark')
            ->schema([
                TextInput::make('content')->label('Teks Judul')->required(),
                Select::make('level')->options(['h2' => 'Besar', 'h3' => 'Sedang', 'h4' => 'Kecil'])->default('h2')->required(),
            ]),
        Builder\Block::make('paragraph')
            ->label('Paragraf')
            ->icon('heroicon-o-bars-3-bottom-left')
            ->schema([
                RichEditor::make('content')->label('Konten Paragraf')->required(),
            ]),
        Builder\Block::make('image')
            ->label('Gambar')
            ->icon('heroicon-o-photo')
            ->schema([
                FileUpload::make('url')->label('Upload Gambar')->image()->required(),
                TextInput::make('alt')->label('Teks Alternatif (untuk SEO)'),
            ]),

            Builder\Block::make('pdf_document')
            ->label('Dokumen PDF')
            ->icon('heroicon-o-document-text')
            ->schema([
                FileUpload::make('url')
                    ->label('Upload PDF')
                    ->acceptedFileTypes(['application/pdf']) // Hanya izinkan PDF
                    ->required(),
                TextInput::make('height')
                    ->label('Tinggi Tampilan (opsional)')
                    ->default('800px')
                    ->helperText('Contoh: 800px atau 100%'),
            ]),
        
        // ==== BLOK BARU YANG PINTAR UNTUK SEMUA EMBED ====
        Builder\Block::make('smart_embed')
            ->label('Embed Cerdas (YouTube, GDrive, Canva)')
            ->icon('heroicon-o-link')
            ->schema([
                TextInput::make('url')
                    ->label('URL (YouTube, Google Drive, Canva, dll.)')
                    ->helperText('Cukup tempelkan link "share" dari layanan yang Anda inginkan. Sistem akan mengubahnya secara otomatis.')
                    ->required(),
            ]),
    ];
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
