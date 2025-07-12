<?php

namespace App\Filament\Assessor\Pages\Auth;

use App\Models\Assessor; // <-- Ganti dari User ke Assessor
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Menimpa metode autentikasi utama dengan logika kustom kita.
     */
    public function authenticate(): ?LoginResponse
    {
        // Ambil data email dan password dari form yang diisi pengguna
        $data = $this->form->getState();

        // Cari asesor di database HANYA berdasarkan email yang diinput
        $assessor = Assessor::where('email', $data['email'])->first();

        // --- INI LOGIKA UTAMANYA ---

        if (!$assessor) {
        throw ValidationException::withMessages([
            'data.email' => __('Akun tidak ditemukan'),
        ]);
        }
        // Pengecekan 1: Apakah asesornya ada? DAN apakah statusnya BUKAN 'approved'?
        if ($assessor && $assessor->status == 'pending') {
            // Jika ya, langsung lemparkan pesan error spesifik kita.
            // Proses autentikasi berhenti di sini dan tidak akan melanjutkan ke pengecekan password.
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda sedang menunggu persetujuan admin'),
            ]);
        }if (!$assessor || $assessor->status == 'rejected') {
            // Jika tidak, atau jika asesornya sudah 'approved', maka kita melanjutkan ke proses
            // pengecekan password.
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda ditolak admin'),
            ]);
        }

        // Pengecekan 2: Jika lolos dari pengecekan di atas (artinya asesor tidak ada,
        // atau jika ada maka statusnya sudah 'approved'), maka kita serahkan proses selanjutnya
        // ke metode autentikasi standar dari Filament.
        // Metode standar ini akan menangani pengecekan password. Jika password salah,
        // ia akan secara otomatis menampilkan pesan "These credentials do not match our records."
        return parent::authenticate();
    }
}