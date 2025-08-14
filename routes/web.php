<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\Auth\UnifiedLoginController; // <-- Import
use App\Http\Middleware\SetLocale;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });


// Route::get('/', [PublicDashboardController::class, 'index'])->name('public.dashboard');


// Rute untuk halaman dasbor utama
// Route::get('/', [PublicDashboardController::class, 'dashboard'])->name('dashboard.public');
// // Route::get('/2', [PublicDashboardController::class, 'dashboard2'])->name('dashboard.public2');
// // Rute untuk halaman peta
// Route::get('/peta-lokasi', [PublicDashboardController::class, 'map'])->name('map.public');
// // Route::get('/peta-lokasi-2', [PublicDashboardController::class, 'map2'])->name('map.public2');
// Route::get('/get-provinces-by-country/{country}', [PublicDashboardController::class, 'getProvincesByCountry'])->name('api.provinces');
// Route::get('/locations/{location}/report/preview', [PublicDashboardController::class, 'downloadReportPreview'])->name('locations.report.preview');

Route::middleware([SetLocale::class])->group(function () {
    
    Route::get('/', [PublicDashboardController::class, 'dashboard'])->name('dashboard.public');
    
    // Rute untuk halaman peta
    Route::get('/peta-lokasi', [PublicDashboardController::class, 'map'])->name('map.public');
    
    // Rute untuk mengambil provinsi (API)
    Route::get('/get-provinces-by-country/{country}', [PublicDashboardController::class, 'getProvincesByCountry'])->name('api.provinces');
    
    // Rute untuk mengunduh laporan
    Route::get('/locations/{location}/report/preview', [PublicDashboardController::class, 'downloadReportPreview'])->name('locations.report.preview');

});
// Route untuk menampilkan halaman login
Route::middleware('filamentcustomlogin')->group(function () {
    Route::get('/login', [UnifiedLoginController::class, 'create'])->name('login');
    Route::post('/login', [UnifiedLoginController::class, 'store'])->name('login.authenticate');
});
