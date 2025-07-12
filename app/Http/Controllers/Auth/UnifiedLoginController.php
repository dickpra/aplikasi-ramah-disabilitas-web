<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Assessor; // Import model Assessor

class UnifiedLoginController extends Controller
{
    /**
     * Menampilkan halaman form login.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Memproses upaya login dari pengguna.
     */
    public function store(Request $request)
    {
        // Validasi input dasar
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // --- INI LOGIKA UTAMA YANG SUDAH DIPERBAIKI ---

        // 1. Coba login sebagai Admin (menggunakan guard 'admin')
        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            
            // Jika berhasil, arahkan ke path panel admin secara dinamis
            return redirect()->intended(config('filament.panels.administrator.path'));
        }

        // 2. Jika gagal sebagai admin, cek dulu apakah user ini adalah asesor yang belum disetujui
        $assessor = Assessor::where('email', $credentials['email'])->first();
        // if ($assessor && $assessor->status !== 'approved') {
        //     throw ValidationException::withMessages([
        //         'email' => __('Akun Anda sedang menunggu persetujuan admin atau telah ditolak.'),
        //     ]);
        // }
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

        // 3. Jika bukan masalah status, coba login sebagai Asesor (menggunakan guard 'assessor')
        if (Auth::guard('assessor')->attempt($credentials)) {
            $request->session()->regenerate();

            // Jika berhasil, arahkan ke path panel asesor secara dinamis
            return redirect()->intended(config('filament.panels.assessor.path'));
        }

        // 4. Jika semua upaya gagal, lemparkan error kredensial standar
        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }
}