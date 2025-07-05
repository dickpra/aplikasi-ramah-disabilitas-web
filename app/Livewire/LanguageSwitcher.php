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
        $this->languages = Language::all();
        $this->currentLocale = session('locale', App::getLocale());
    }

    public function switchLocale(string $localeCode)
    {
        // 1. Simpan pilihan bahasa ke sesi pengguna
        session(['locale' => $localeCode]);

        // 2. Kirim sinyal ke komponen lain di halaman bahwa bahasa telah berganti
        $this->dispatch('localeSwitched', locale: $localeCode);

        // 3. Muat ulang halaman saat ini untuk menerapkan perubahan bahasa pada UI statis (seperti __())
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}