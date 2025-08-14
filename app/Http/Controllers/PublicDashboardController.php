<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Country;
use App\Models\Province;
use App\Models\Indicator;
use Illuminate\Support\Facades\DB; // <-- 1. Import DB Facade
use App\Models\AssessmentScore; // <-- 2. Import AssessmentScore
use Barryvdh\DomPDF\Facade\Pdf; // <-- 1. Import facade PDF


class PublicDashboardController extends Controller
{
    /**
     * Menampilkan halaman dasbor utama dengan filter.
     */
    public function dashboard(Request $request)
    {
        $query = Location::query()
            ->whereNotNull('final_score')
            ->with('province.country');

        // Filter dasar (negara, provinsi, tipe lokasi)
        if ($request->filled('country_id')) {
            $query->whereHas('province.country', function ($q) use ($request) {
                $q->where('id', $request->country_id);
            });
        }
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }
        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        
        // --- LOGIKA BARU UNTUK FILTER & PENGURUTAN BERDASARKAN KATEGORI ---
        if ($request->filled('indicator_category')) {
            $category = $request->indicator_category;

            // 3. Membuat subquery untuk menghitung skor rata-rata per kategori
            $subquery = AssessmentScore::select(DB::raw('avg(score)'))
                ->join('assignments', 'assessment_scores.assignment_id', '=', 'assignments.id')
                ->join('indicators', 'assessment_scores.indicator_id', '=', 'indicators.id')
                ->whereColumn('assignments.location_id', 'locations.id')
                ->where('indicators.category', $category);

            // 4. Menambahkan subquery sebagai kolom baru bernama 'category_score'
            //    dan memfilter hanya lokasi yang memiliki skor di kategori ini.
            $query->addSelect(['*', 'category_score' => $subquery])
                  ->whereHas('assessmentScores.indicator', function ($q) use ($category) {
                      $q->where('category', $category);
                  })
                  ->orderByDesc('category_score'); // 5. Mengurutkan berdasarkan skor kategori
        } else {
            // Jika tidak ada filter kategori, urutkan berdasarkan skor total
            $query->orderByDesc('final_score');
        }
        // -----------------------------------------------------------------

        $locations = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            // Kita perlu menambahkan data 'category_score' ke view parsial jika ada
            return view('public.partials._location-list', [
                'locations' => $locations,
                'selected_category' => $request->indicator_category, // Kirim kategori yang dipilih
            ])->render();
        }

        // Ambil data untuk mengisi dropdown filter
        $countries = Country::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $locationTypes = Location::query()->select('location_type')->whereNotNull('location_type')->distinct()->pluck('location_type');
        $indicatorCategories = Indicator::query()->select('category')->whereNotNull('category')->distinct()->pluck('category');

        return view('public.dashboard', [
            'locations' => $locations,
            'countries' => $countries,
            'provinces' => $provinces,
            'locationTypes' => $locationTypes,
            'indicatorCategories' => $indicatorCategories,
            'selected_category' => $request->indicator_category, // Kirim juga ke view utama
        ]);
    }
    public function getProvincesByCountry(Country $country)
    {
        // Ambil semua provinsi yang dimiliki oleh negara yang dipilih,
        // urutkan berdasarkan nama, lalu kirim sebagai JSON.
        return response()->json($country->provinces()->orderBy('name')->get());
    }

    // Metode dashboard2, map, dan map2 Anda bisa dibiarkan seperti semula
    // atau Anda bisa terapkan logika filter yang sama jika diperlukan.
    public function dashboard2()
    {
        $locations = Location::query()
            ->whereNotNull('final_score')
            ->with('province.country')
            ->orderBy('final_score', 'desc')
            ->paginate(10);

        return view('public.dashboard2', [
            'locations' => $locations,
        ]);
    }

    public function map()
    {
        $locations = Location::query()
            ->whereNotNull('final_score')
            ->with('province.country')
            ->orderBy('final_score', 'desc')
            ->get();

        return view('public.map', [
            'locations' => $locations,
        ]);
    }
    
    public function map2()
    {
        $locations = Location::query()
            ->whereNotNull('final_score')
            ->with('province.country')
            ->orderBy('final_score', 'desc')
            ->get();

        return view('public.map2', [
            'locations' => $locations,
        ]);
    }
    
    public function downloadReportPreview(Location $location)
{
    // Dapatkan kode bahasa yang sedang aktif
    $locale = app()->getLocale();

    // Ambil ringkasan skor per kategori
    $categoryScores = DB::table('assessment_scores')
        ->join('assignments', 'assessment_scores.assignment_id', '=', 'assignments.id')
        ->join('indicators', 'assessment_scores.indicator_id', '=', 'indicators.id')
        ->where('assignments.location_id', $location->id)
        // === PERUBAIKAN DI SINI ===
        // Menggunakan JSON_EXTRACT yang lebih kompatibel
        ->select(
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(indicators.category, '$.{$locale}')) as category_name"),
            DB::raw('AVG(assessment_scores.score) as average_score')
        )
        // ========================
        ->groupBy('category_name') // Group by alias yang baru
        ->orderByDesc('average_score')
        ->limit(5)
        ->get();

    // Buat PDF dari view 'public.report-preview'
    $pdf = Pdf::loadView('public.report-preview', [
        'location' => $location,
        'categoryScores' => $categoryScores,
    ]);

    // Kirim PDF ke browser
    return $pdf->stream('Pratinau Laporan - ' . $location->name . '.pdf');
}
}