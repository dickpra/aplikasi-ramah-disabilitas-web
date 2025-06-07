<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class PublicDashboardController extends Controller
{
    public function index()
    {
        // Ambil semua lokasi yang sudah ada skor akhirnya (artinya sudah dinilai & dikalkulasi)
        // dan urutkan berdasarkan skor tertinggi.
        $locations = Location::query()
            ->whereNotNull('final_score') // Hanya ambil yang sudah dihitung skornya
            ->with('province.country') // Eager load untuk menampilkan info provinsi & negara
            ->orderBy('final_score', 'desc')
            ->paginate(10); // Gunakan paginasi jika datanya banyak

        // Kirim data ke view
        return view('public-dashboard', [
            'locations' => $locations,
        ]);
    }
}