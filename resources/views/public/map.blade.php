<!DOCTYPE html>
<html lang="en" x-data="{ highContrast: false, grayscaleMode: false }" :class="{ 'high-contrast': highContrast, 'grayscale': grayscaleMode }">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Indeks Inklusi | Peta Lokasi Terverifikasi</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
                        },
                    },
                },
            },
        };
    </script>

    <style>
        body { scroll-behavior: smooth; }
        #map {
            height: 600px;
            width: 100%;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 0;
        }
        .high-contrast { background-color: black !important; color: white !important; }
        .high-contrast .bg-white,
        .high-contrast .border-t {
            background-color: black !important;
            color: yellow !important;
            border-color: yellow !important;
        }
        .high-contrast .text-gray-500,
        .high-contrast .text-gray-700,
        .high-contrast .text-gray-800,
        .high-contrast .text-gray-900 {
            color: white !important;
        }
        .grayscale { filter: grayscale(100%); }

        /* Loader Overlay */
        .loader-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex; align-items: center; justify-content: center;
            z-index: 500;
        }
        /* Accessibility Button */
        .accessibility-btn {
            position: fixed;
            bottom: 20px; right: 20px;
            z-index: 1000;
            background-color: white;
            padding: 0.75rem;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: flex; gap: 0.75rem;
            border: 1px solid #e5e7eb;
        }
        .high-contrast .accessibility-btn {
            background-color: black !important;
            border-color: yellow !important;
        }
        .accessibility-btn button {
            color: #374151; /* gray-700 */
        }
        .high-contrast .accessibility-btn button {
            color: yellow !important;
        }
        .legend-control {
            padding: 12px; background: rgba(255,255,255,0.95);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2); border-radius: 8px;
            line-height: 24px; color: #333; width: 180px;
        }
        .legend-control i {
            width: 18px; height: 18px; float: left; margin-right: 8px;
            border-radius: 50%; border: 1px solid rgba(0,0,0,0.2);
        }
        .MarkerCluster div {
            color: white; font-weight: bold;
        }
        /* Tambahkan ini di dalam tag <style> Anda */
        .marker-pin {
            width: 32px; 
            height: 32px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex; 
            align-items: center; 
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .marker-pin i {
            transform: rotate(45deg);
            font-size: 16px; 
            color: white;
        }
        .custom-popup .leaflet-popup-content-wrapper { 
            border-radius: 0.75rem; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        }
        .custom-popup .leaflet-popup-content { 
            margin: 0; 
            width: 280px !important; 
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans overflow-x-hidden relative">

    {{-- HEADER --}}
     <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard.public') }}" class="text-2xl font-bold text-blue-700">Indeks Inklusi</a>
            <nav>
                <a href="{{ route('dashboard.public') }}" class="text-gray-600 hover:text-blue-700 font-medium">Kembali ke Dashboard</a>
            </nav>
        </div>
    </header>

    {{-- PAGE CONTENT --}}
    <main class="pt-8">
        {{-- MAP SECTION --}}
        <section id="peta-lokasi" class="container mx-auto px-6 py-12">
            <div x-data="mapComponent()" class="relative">
                <h2 class="text-3xl font-semibold text-gray-800 mb-4">Peta Lokasi Terverifikasi</h2>
                <p class="text-gray-600 mb-6 max-w-2xl">
                    Berikut tampilan peta interaktif yang menunjukkan lokasi‐lokasi fasilitas di Indonesia yang telah diverifikasi.
                </p>

                <div class="relative">
                    {{-- Loader Overlay --}}
                    <div x-show="loading" class="loader-overlay" x-cloak>
                        <svg class="animate-spin h-12 w-12 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>
                    <div id="map"></div>
                </div>

                {{-- Legend Card --}}
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                    <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Legenda Peringkat</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-x-4 gap-y-5">
                            
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-sky-500">
                                    <i class="fas fa-gem text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">Diamond</p>
                                    {{-- Teks Diubah --}}
                                    <p class="text-xs text-gray-500">Skor &ge; 90</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-amber-500">
                                    <i class="fas fa-medal text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">Gold</p>
                                    {{-- Teks Diubah --}}
                                    <p class="text-xs text-gray-500">75 - 89.9</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-slate-500">
                                    <i class="fas fa-award text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">Silver</p>
                                    {{-- Teks Diubah --}}
                                    <p class="text-xs text-gray-500">50 - 74.9</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-amber-800">
                                    <i class="fas fa-ribbon text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">Bronze</p>
                                    {{-- Teks Diubah --}}
                                    <p class="text-xs text-gray-500">25 - 49.9</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-red-500">
                                    <i class="fas fa-certificate text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">Participant</p>
                                    {{-- Teks Diubah --}}
                                    <p class="text-xs text-gray-500">&lt; 25</p>
                                </div>
                            </div>

                        </div>
                    </div>
            </div>
        </section>

        {{-- FOOTER --}}
        <footer id="kontak" class="bg-white border-t border-gray-200">
            <div class="container mx-auto px-6 py-8 text-center">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Indeks Inklusi. Semua hak cipta dilindungi.</p>
            </div>
        </footer>
    </main>

    {{-- ACCESSIBILITY FLOATING BUTTON --}}
    <div class="accessibility-btn">
        <button title="Toggle High Contrast" @click="highContrast = !highContrast" class="focus:outline-none">
            <i class="fas fa-adjust fa-lg"></i>
        </button>
        <button title="Toggle Grayscale" @click="grayscaleMode = !grayscaleMode" class="focus:outline-none">
            <i class="fas fa-eye-slash fa-lg"></i>
        </button>
    </div>

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    {{-- Data Lokasi dari Controller --}}
    <script>
        const rawLocations = @json($locations);
    </script>

    <script>
    function mapComponent() {
        return {
            loading: true,
            async init() {
                const map = L.map('map').setView([-2.548926, 118.0148634], 5);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                    maxZoom: 20
                }).addTo(map);

                // Geocoder menggunakan Nominatim (tanpa API Key)
                const geocode = async (query) => {
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
                    try {
                        const res = await fetch(url, { headers: { 'Accept-Language': 'id,en' } });
                        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                        const data = await res.json();
                        return data.length > 0 ? { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) } : null;
                    } catch (err) {
                        console.warn('Geocoding gagal:', query, err);
                        return null;
                    }
                };

                const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
                const markerBounds = [];

                for (const loc of rawLocations) {
                    const provinceName = loc.province ? loc.province.name : '';
                    const countryName = loc.province && loc.province.country ? loc.province.country.name : 'N/A';
                    const query = `${loc.name}, ${provinceName}`;
                    const coords = await geocode(query);

                    if (coords) {
                        const score = parseFloat(loc.final_score);

                        // ==========================================
                        // === 1. LOGIKA PERINGKAT BARU (FIXED) ===
                        // ==========================================
                        let rank;
                        if (score >= 90)      { rank = { name: 'Diamond', color: '#0ea5e9', icon: 'fa-gem' }; }
                        else if (score >= 75) { rank = { name: 'Gold', color: '#f59e0b', icon: 'fa-medal' }; }
                        else if (score >= 50) { rank = { name: 'Silver', color: '#64748b', icon: 'fa-award' }; }
                        else if (score >= 25) { rank = { name: 'Bronze', color: '#a16207', icon: 'fa-ribbon' }; }
                        else                  { rank = { name: 'Participant', color: '#ef4444', icon: 'fa-certificate' }; }
                        
                        // ==================================================
                        // === 2. IKON MARKER KUSTOM SESUAI PERINGKAT ===
                        // ==================================================
                        // Menggunakan L.marker dengan L.divIcon agar bisa memakai ikon FontAwesome
                        const icon = L.divIcon({
                            html: `<div class="marker-pin" style="background-color: ${rank.color};"><i class="fas ${rank.icon}"></i></div>`,
                            className: '', // classname kosong agar tidak ada style default leaflet
                            iconSize: [32, 32],
                            iconAnchor: [16, 32] // Titik pin di bagian bawah tengah
                        });

                        const marker = L.marker([coords.lat, coords.lng], { icon: icon })
                            .addTo(map);

                        // =============================================
                        // === 3. POPUP BARU DENGAN INFO PERINGKAT ===
                        // =============================================
                        const popupContent = `
                            <div class="font-sans p-1">
                                <div class="p-3">
                                    <h3 class="text-base font-bold text-gray-900">${loc.name}</h3>
                                    <p class="text-sm text-gray-500">${provinceName}, ${countryName}</p>
                                </div>
                                <div class="mt-1 pt-3 px-4 pb-3 border-t border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Peringkat:</span>
                                        <span class="px-3 py-1 text-xs font-bold text-white rounded-full" style="background-color: ${rank.color};">${rank.name}</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-sm font-medium text-gray-600">Skor:</span>
                                        <span class="font-bold text-gray-800">${score.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>`;
                        
                        marker.bindPopup(popupContent, { className: 'custom-popup' });
                        markerBounds.push([coords.lat, coords.lng]);
                    }
                    // Delay 1 detik untuk menghormati kebijakan Nominatim
                    await sleep(1000); 
                }

                // Auto-zoom setelah semua marker ditambahkan
                if (markerBounds.length > 0) {
                    map.fitBounds(markerBounds, { padding: [50, 50], maxZoom: 16 });
                }

                // Legenda sudah benar, tidak perlu diubah
                const legend = L.control({position: 'bottomright'});
                        legend.onAdd = function (map) {
                            const div = L.DomUtil.create('div', 'legend-control');
                            const grades = [
                                { name: 'Diamond (90+)', color: '#0ea5e9'},
                                { name: 'Gold (75 - 89.9)', color: '#f59e0b'},
                                { name: 'Silver (50 - 74.9)', color: '#64748b'},
                                { name: 'Bronze (25 - 49.9)', color: '#a16207'},
                                { name: 'Participant (<25)', color: '#ef4444'},
                            ];
                            div.innerHTML += '<h4 class="font-bold mb-2 text-sm">Legenda Peringkat</h4>';
                            grades.forEach(g => { div.innerHTML += `<i style="background:${g.color}"></i> ${g.name}<br>`; });
                            return div;
                        };
                        legend.addTo(map);
                
                this.loading = false;
            }
        }
    }
</script>
</body>
</html>
