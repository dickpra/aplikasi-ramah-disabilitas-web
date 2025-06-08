<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class PublicDashboardController extends Controller
{
    /**
     * Menampilkan halaman dasbor utama dengan data statistik dan peringkat.
     */
    public function dashboard()
    {
        // =================================================================
        // FIX: Tambahkan logika ini untuk mengambil data $locations
        // dan mengirimkannya ke view 'public.dashboard'.
        // =================================================================
        $locations = Location::query()
            ->whereNotNull('final_score')
            ->with('province.country') // Eager load provinsi
            ->orderBy('final_score', 'desc')
            ->paginate(10); // Gunakan paginate() karena view Anda memiliki {{ $locations->links() }}

        return view('public.dashboard', [
            'locations' => $locations,
        ]);
    }

    /**
     * Menampilkan halaman peta lokasi.
     * Method ini tetap sama, tetapi kita gunakan get() karena peta perlu semua data sekaligus.
     */
    public function map()
    {
        $locations = Location::query()
            ->whereNotNull('final_score')
            // ->with('province')
            ->with('province.country')
            ->orderBy('final_score', 'desc')
            ->get(); // Gunakan get() untuk peta agar semua marker ditampilkan

        return view('public.map', [
            'locations' => $locations,
        ]);
    }
}
