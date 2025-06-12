<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Indeks Inklusi | Peta Peringkat Lokasi</title>

    {{-- Library CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    {{-- Library JS (semua dengan 'defer' untuk pemuatan yang benar dan berurutan) --}}
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" defer></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" defer></script>
    {{-- Alpine.js tidak lagi digunakan, jadi tidak perlu dimuat --}}

    <style>
        body { background-color: #f8fafc; /* gray-50 */ font-family: 'Inter', sans-serif; }
        @import url('https://rsms.me/inter/inter.css');
        
        #map-container { position: relative; width: 100%; height: 65vh; min-height: 500px; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
        #map { height: 100%; width: 100%; z-index: 1; }
        
        #loading-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px); z-index: 10;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: opacity 0.3s ease-in-out;
        }
        .spinner {
            border: 5px solid rgba(0, 0, 0, 0.1); width: 50px; height: 50px;
            border-radius: 50%; border-left-color: #3b82f6; /* blue-500 */
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .marker-pin {
            width: 32px; height: 32px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .marker-pin i { transform: rotate(45deg); font-size: 16px; color: white; }

        .custom-popup .leaflet-popup-content-wrapper { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .custom-popup .leaflet-popup-content { margin: 0; width: 280px !important; }
        .custom-popup .leaflet-popup-tip { background: #fff; }

        .legend-control {
            padding: 12px; background: rgba(255,255,255,0.95);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2); border-radius: 8px;
            line-height: 24px; color: #333; width: 200px;
        }
        .legend-control i { width: 18px; height: 18px; float: left; margin-right: 8px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.2); }
        
        .grayscale-tiles { filter: grayscale(100%); }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">

    <header class="bg-white shadow-sm sticky top-0 z-20">
        {{-- <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard.public') }}" class="text-2xl font-bold text-blue-700">Indeks Inklusi</a>
            <nav>
                <a href="{{ route('dashboard.public') }}" class="text-gray-600 hover:text-blue-700 font-medium">Kembali ke Dashboard</a>
            </nav>
        </div> --}}
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard.public') }}">
                <img src="{{ asset('img/navbar.png') }}" alt="Logo Indeks Inklusi" class="h-10"> {{-- Sesuaikan tinggi (h-10) sesuai kebutuhan Anda --}}
            </a>
            <nav>
                <a href="{{ route('dashboard.public') }}" class="text-gray-600 hover:text-blue-700 font-medium">Kembali ke Dashboard</a>
            </nav>
        </div>
    </header>

    <main class="py-12">
        <section id="peta-lokasi" class="container mx-auto px-6">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 text-center sm:text-left">
                    <div>
                        <h2 class="text-4xl font-extrabold text-gray-800 tracking-tight mb-2">Peta Sebaran Inklusi</h2>
                        <p class="text-lg text-gray-500 max-w-3xl">
                            Jelajahi peta interaktif untuk melihat peringkat fasilitas publik yang telah terverifikasi.
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <button onclick="forceMapRefresh()" title="Paksa muat ulang data lokasi dari server"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-transform hover:scale-105">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Refresh Data Peta
                        </button>
                    </div>
                </div>

                <div id="map-container">
                    <div id="loading-overlay">
                        <div class="spinner"></div>
                        <p class="mt-4 font-semibold text-gray-700 text-lg">Memproses data lokasi...</p>
                    </div>
                    <div id="map"></div>
                </div>
            </div>
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
        </section>
    </main>

    <footer class="bg-white border-t border-gray-100 mt-12">
        <div class="container mx-auto px-6 py-8 text-center">
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Indeks Inklusi. Semua hak cipta dilindungi.</p>
        </div>
    </footer>

    <script>
        const rawLocations = @json($locations);

        function forceMapRefresh() {
            console.log("CACHE DIHAPUS: Memaksa pembaruan data dari server pada refresh berikutnya.");
            localStorage.removeItem('geocodedLocationsData');
            localStorage.removeItem('locationsCacheVersion');
            alert('Cache peta telah dihapus. Halaman akan dimuat ulang untuk mendapatkan data terbaru.');
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', async function() {
            
            const loadingOverlay = document.getElementById('loading-overlay');
            const mapElement = document.getElementById('map');
            
            if (!mapElement) {
                console.error("Elemen peta dengan id 'map' tidak ditemukan.");
                return;
            }
            if (loadingOverlay) loadingOverlay.style.display = 'flex';

            const map = L.map('map', { zoomControl: false }).setView([-2.548926, 118.0148634], 5);
            L.control.zoom({ position: 'topright' }).addTo(map);

            const cartoVoyager = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO' });
            const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO' });
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' });
            const tonerLite = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO', className: 'grayscale-tiles' });
            const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' });
            cartoVoyager.addTo(map);

            const markers = L.markerClusterGroup();
            const markerBounds = [];

            const cacheKey = 'geocodedLocationsData';
            const cacheVersionKey = 'locationsCacheVersion';
            const currentVersion = '1.0';
            let locationsToPlot;

            try {
                const storedVersion = localStorage.getItem(cacheVersionKey);
                const cachedData = JSON.parse(localStorage.getItem(cacheKey));
                if (storedVersion === currentVersion && cachedData && cachedData.length > 0) {
                    locationsToPlot = cachedData;
                } else {
                    throw new Error("Cache tidak valid atau versi berbeda.");
                }
            } catch (e) {
                console.log("Memulai proses geocoding (hanya terjadi sekali)...");
                
                const geocode = async (query) => {
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
                    try {
                        const res = await fetch(url, { headers: { 'Accept-Language': 'id,en' } });
                        if (!res.ok) return null;
                        const data = await res.json();
                        return data.length > 0 ? { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) } : null;
                    } catch (err) { console.warn(`Geocoding gagal: ${query}`); return null; }
                };
                const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
                const geocodedLocations = [];
                for (const loc of rawLocations) {
                    const provinceName = loc.province ? loc.province.name : '';
                    const countryName = loc.province && loc.province.country ? loc.province.country.name : 'Indonesia';
                    const query = `${loc.name}, ${provinceName}, ${countryName}`;
                    
                    const coords = await geocode(query);
                    if (coords) {
                        loc.lat = coords.lat;
                        loc.lng = coords.lng;
                        geocodedLocations.push(loc);
                    }
                    await sleep(1000);
                }
                locationsToPlot = geocodedLocations;
                localStorage.setItem(cacheKey, JSON.stringify(locationsToPlot));
                localStorage.setItem(cacheVersionKey, currentVersion);
            }

            for (const loc of locationsToPlot) {
                const score = parseFloat(loc.final_score);
                let rank;
                if (score >= 90)      { rank = { name: 'Diamond', color: '#0ea5e9', icon: 'fa-gem' }; }
                else if (score >= 75) { rank = { name: 'Gold', color: '#f59e0b', icon: 'fa-medal' }; }
                else if (score >= 50) { rank = { name: 'Silver', color: '#64748b', icon: 'fa-award' }; }
                else if (score >= 25) { rank = { name: 'Bronze', color: '#a16207', icon: 'fa-ribbon' }; }
                else                  { rank = { name: 'Participant', color: '#ef4444', icon: 'fa-certificate' }; }

                const icon = L.divIcon({
                    html: `<div class="marker-pin" style="background-color: ${rank.color};"><i class="fas ${rank.icon}"></i></div>`,
                    className: '', iconSize: [32, 32], iconAnchor: [16, 32]
                });

                const marker = L.marker([loc.lat, loc.lng], { icon: icon });
                
                const popupContent = `
                    <div class="font-sans p-1">
                        <div class="p-3">
                            <h3 class="text-base font-bold text-gray-900">${loc.name}</h3>
                            <p class="text-sm text-gray-500">${loc.province ? loc.province.name : ''}, ${loc.province && loc.province.country ? loc.province.country.name : 'Indonesia'}</p>
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
                markers.addLayer(marker);
                markerBounds.push([loc.lat, loc.lng]);
            }
            
            map.addLayer(markers);
            if (markerBounds.length > 0) {
                map.fitBounds(markerBounds, { padding: [50, 50], maxZoom: 16 });
            }

            const baseMaps = {
                "Minimalis": cartoVoyager, "Hitam Putih": tonerLite, "Mode Gelap": cartoDark,
                "Satelit": esriSatellite, "Peta Jalan": osm
            };
            const overlayMaps = { "Tampilkan Lokasi": markers };
            L.control.layers(baseMaps, overlayMaps, { position: 'topright' }).addTo(map);

            const legend = L.control({ position: 'bottomright' });
            legend.onAdd = function (map) {
                const div = L.DomUtil.create('div', 'legend-control');
                const grades = [
                    { name: 'Diamond (90+)', color: '#0ea5e9'}, { name: 'Gold (75 - 89.9)', color: '#f59e0b'},
                    { name: 'Silver (50 - 74.9)', color: '#64748b'}, { name: 'Bronze (25 - 49.9)', color: '#a16207'},
                    { name: 'Participant (<25)', color: '#ef4444'}
                ];
                div.innerHTML += '<h4 class="font-bold mb-2 text-sm">Legenda Peringkat</h4>';
                grades.forEach(g => { div.innerHTML += `<i style="background:${g.color}"></i> ${g.name}<br>`; });
                return div;
            };
            legend.addTo(map);
            
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        });
    </script>
</body>
</html>