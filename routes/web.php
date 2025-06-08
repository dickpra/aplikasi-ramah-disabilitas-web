<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicDashboardController;
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
Route::get('/', [PublicDashboardController::class, 'dashboard'])->name('dashboard.public');

// Rute untuk halaman peta
Route::get('/peta-lokasi', [PublicDashboardController::class, 'map'])->name('map.public');


