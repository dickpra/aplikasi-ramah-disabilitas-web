<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aplikasi Penilaian Ramah Disabilitas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                        accent: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, rgba(6, 78, 142, 0.95), rgba(9, 9, 121, 0.9)), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
        }
        .rank-card {
            border-radius: 1rem;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .rank-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
        .rank-badge {
            font-size: 0.75rem;
            padding: 0.5em 1em;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .rank-DIAMOND { color: #fff; background-color: #3b82f6; }
        .rank-GOLD { color: #fff; background-color: #f59e0b; }
        .rank-SILVER { color: #fff; background-color: #64748b; }
        .rank-BRONZE { color: #fff; background-color: #a16207; }
        .rank-PARTICIPANT { color: #fff; background-color: #ef4444; }
        /* Custom styles */
        .accessibility-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .sidebar-minimized {
            width: 80px;
        }
        
        .sidebar-minimized .nav-text {
            display: none;
        }
        
        .main-content-expanded {
            margin-left: 80px;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* High contrast mode */
        .high-contrast {
            background-color: black !important;
            color: white !important;
        }
        
        .high-contrast .bg-white {
            background-color: black !important;
            color: yellow !important;
            border-color: yellow !important;
        }
        
        .high-contrast .text-gray-600 {
            color: white !important;
        }
        
        .grayscale {
            filter: grayscale(100%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar (statis seperti di kode Anda, atau bisa dibuat sebagai komponen Blade terpisah) --}}
        {{-- <div id="sidebar" class="bg-blue-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out">
            <a href="#" class="text-white text-2xl font-extrabold px-4">RADIS</a>
             <nav>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 bg-blue-700 text-white">Dashboard</a>
                <a href="#" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 hover:text-white">Penilaian</a>
             </nav>
        </div> --}}
        

        {{-- Main Content --}}
        <div class="flex-1 overflow-auto">
            {{-- Top Navigation (statis seperti di kode Anda) --}}
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Penilaian Ramah Disabilitas</h1>
                    {{-- Konten Top Navigation lain --}}
                    
                </div>
            </header>

            {{-- ================================================================== --}}
            {{-- KONTEN DINAMIS DIMULAI DARI SINI --}}
            {{-- ================================================================== --}}
            <main class="p-6">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-md p-6 text-white mb-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Selamat Datang di Indeks Inklusi</h2>
                            <p class="max-w-2xl">Sistem penilaian untuk mengukur tingkat kesesuaian fasilitas publik bagi penyandang disabilitas. Mari bersama-sama menciptakan lingkungan yang inklusif!</p>
                        
                        </div>
                    </div>
                </div>

                <div class="hero-gradient text-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight">Papan Peringkat Inklusi</h1>
            <p class="mt-4 max-w-3xl mx-auto text-lg md:text-xl text-blue-100">
                Mewujudkan lingkungan yang adil dan aksesibel untuk semua. Lihat peringkat lokasi berdasarkan penilaian komprehensif kami.
            </p>

            {{-- === TOMBOL LOGIN BARU === --}}
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ url(config('filament.panels.administrator.path', '/administrator/login')) }}"  
                   class="w-full sm:w-auto inline-block px-8 py-3 text-base font-medium text-center text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900 transition-transform hover:scale-105">
                    Login sebagai Admin
                </a>
                <a href="{{ url(config('filament.panels.assessor.path', '/assessor/login')) }}" 
                   class="w-full sm:w-auto inline-block px-8 py-3 text-base font-medium text-center text-gray-900 bg-white rounded-lg hover:bg-gray-200 focus:ring-4 focus:ring-gray-100 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-gray-300 dark:focus:ring-gray-200 transition-transform hover:scale-105">
                    Login sebagai Asesor
                </a>
            </div>
            {{-- ======================== --}}

        </div>
    </div>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
    </div>
                {{-- Stats Overview Dinamis --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-building text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Total Lokasi Terdaftar</h3>
                                <p class="text-2xl font-bold">{{ \App\Models\Location::count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Telah Dinilai & Disetujui</h3>
                                <p class="text-2xl font-bold">{{ $locations->total() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-star text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Rata-rata Skor</h3>
                                <p class="text-2xl font-bold">{{ number_format($locations->avg('final_score'), 2) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm font-medium">Asesor Terdaftar</h3>
                                <p class="text-2xl font-bold">{{ \App\Models\Assessor::count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Daftar Lokasi Terbaik (Dinamis) --}}
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Peringkat Fasilitas Terbaik</h2>
                    <div class="space-y-4">
                        @forelse ($locations as $location)
                            <div class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors">
                                <div class="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 mr-4">
                                    {{-- Anda bisa membuat ikon dinamis berdasarkan location_type --}}
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium truncate">{{ $location->name }}</h4>
                                    <p class="text-sm text-gray-500 truncate">{{ $location->province->name ?? '' }}</p>
                                </div>
                                <div class="ml-4 flex items-center">
                                    <span class="text-yellow-500 mr-1"><i class="fas fa-star"></i></span>
                                    <span class="font-medium">{{ number_format($location->final_score, 3) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8">Belum ada data peringkat yang tersedia.</p>
                        @endforelse
                    </div>
                    
                    {{-- Link Paginasi --}}
                    <div class="mt-6">
                        {{ $locations->links() }}
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
