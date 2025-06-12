<?php

namespace App\Livewire;

use App\Models\Language;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public $languages;
    public string $currentLocale;

    public function mount(): void
    {
        // Ambil semua bahasa yang tersedia dari database
        $this->languages = Language::all();
        // Atur locale saat ini dari sesi, atau gunakan default aplikasi
        $this->currentLocale = session('locale', App::getLocale());
    }

    /**
     * Fungsi yang akan dipanggil saat bahasa baru dipilih.
     *
     * == FIX DI SINI ==
     * Menghapus return type hint ': \Illuminate\Http\RedirectResponse'
     */
    public function switchLocale(string $localeCode)
    {
        // Simpan pilihan bahasa ke sesi pengguna
        session(['locale' => $localeCode]);

        // Muat ulang halaman saat ini untuk menerapkan bahasa baru
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
