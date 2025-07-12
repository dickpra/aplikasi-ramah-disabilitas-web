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
    <script>
      // Konfigurasi Tailwind CSS untuk tema light & dark
      tailwind.config = {
        darkMode: 'class', // Mengaktifkan mode gelap berbasis kelas
        theme: {
          extend: {
            fontFamily: {
              sans: ['Poppins', 'sans-serif']
            },
            colors: {
              primary: { 100: '#e0e7ff', 300: '#7c3aed', 500: '#4f46e5', 700: '#3730a3', 900: '#1e1b4b' },
              accent: { 500: '#a3e635', 700: '#65a30d' },
              dark: { bg: '#0f172a', card: '#1e293b', border: '#334155' },
              light: { bg: '#f1f5f9', card: '#ffffff', border: '#e2e8f0' } // Menambahkan warna untuk tema terang
            }
          }
        }
      }
    </script>

   <style>
        body { font-family: 'Poppins', sans-serif; transition: background-color 0.3s, color 0.3s; }
        
        /* Tema Terang (Default) */
        #map-container { position: relative; width: 100%; height: 65vh; min-height: 500px; border-radius: 1rem; overflow: hidden; border: 1px solid var(--light-border, #e2e8f0); }
        #map { height: 100%; width: 100%; z-index: 1; }
        #loading-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; backdrop-filter: blur(4px); z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: opacity 0.3s ease-in-out; }
        .loading-light { background-color: rgba(255, 255, 255, 0.8); }
        .spinner { border: 5px solid rgba(79, 70, 229, 0.2); width: 50px; height: 50px; border-radius: 50%; border-left-color: #4f46e5; animation: spin 1s linear infinite; }
        .marker-pin { width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
        .marker-pin i { transform: rotate(45deg); font-size: 16px; color: white; }
        .custom-popup .leaflet-popup-content-wrapper { background: #ffffff; color: #1f2937; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .custom-popup .leaflet-popup-content { margin: 0; width: 280px !important; }
        .custom-popup .leaflet-popup-tip { background: #ffffff; }
        .legend-control { padding: 12px; background: rgba(255, 255, 255, 0.9); box-shadow: 0 2px 8px rgba(0,0,0,0.15); border-radius: 8px; line-height: 24px; color: #334155; width: 200px; border: 1px solid #e2e8f0; }
        .legend-control i { width: 18px; height: 18px; float: left; margin-right: 8px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); }

        /* Tema Gelap */
        .dark #map-container { border-color: #334155; }
        .dark #loading-overlay { background-color: rgba(15, 23, 42, 0.8); }
        .dark .spinner { border-color: rgba(163, 230, 53, 0.2); border-left-color: #a3e635; }
        .dark .custom-popup .leaflet-popup-content-wrapper { background: #1e293b; color: white; border-color: #334155; }
        .dark .custom-popup .leaflet-popup-tip { background: #1e293b; }
        .dark .legend-control { background: rgba(30, 41, 59, 0.9); color: #e2e8f0; border-color: #334155; }
        .dark .dark-tiles { filter: invert(1) hue-rotate(180deg) brightness(0.9) contrast(0.9); }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-light-bg text-gray-800 dark:bg-dark-bg dark:text-white">

    <header class="bg-light-card dark:bg-dark-card border-b border-light-border dark:border-dark-border px-4 sm:px-6 py-4 flex justify-between items-center sticky top-0 z-20 transition-colors">
        <a href="{{ route('dashboard.public') }}">
            <img src="{{ asset('img/navbar.png') }}" class="h-10" alt="Logo Indeks Inklusi" />
        </a>
        <nav class="flex items-center space-x-4">
            <a href="{{ route('dashboard.public') }}" class="text-primary-500 dark:text-accent-500 hover:underline font-semibold text-sm sm:text-base">Kembali ke Dashboard</a>
            
            {{-- Tombol Pengalih Tema --}}
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-lg text-sm p-2.5">
                <i id="theme-toggle-dark-icon" class="fas fa-moon text-lg hidden"></i>
                <i id="theme-toggle-light-icon" class="fas fa-sun text-lg hidden"></i>
            </button>
        </nav>
    </header>

    <main class="py-12">
        <section id="peta-lokasi" class="container mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 text-center sm:text-left">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-2">Peta Sebaran Inklusi</h2>
                    <p class="text-lg text-gray-600 dark:text-white/80 max-w-3xl">
                        Jelajahi peta interaktif untuk melihat peringkat fasilitas publik yang telah terverifikasi.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button onclick="forceMapRefresh()" title="Paksa muat ulang data lokasi dari server"
                            class="inline-flex items-center px-5 py-2.5 bg-primary-500 hover:bg-primary-700 text-white rounded-lg font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Refresh Data
                    </button>
                </div>
            </div>

            <div id="map-container">
                <div id="loading-overlay">
                    <div class="spinner"></div>
                    <p class="mt-4 font-semibold text-gray-800 dark:text-white/90 text-lg">Memproses data lokasi...</p>
                </div>
                <div id="map"></div>
            </div>
            
            <div class="mt-12 bg-light-card dark:bg-dark-card p-6 sm:p-8 rounded-2xl shadow-xl border border-light-border dark:border-dark-border transition-colors">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Legenda Peringkat</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
                    <div class="flex items-center gap-2 bg-blue-100 dark:bg-blue-600/20 p-3 rounded-lg border border-blue-200 dark:border-blue-600">
                        <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                        <span class="font-semibold text-gray-800 dark:text-white">Diamond</span><span class="text-gray-500 dark:text-gray-400 ml-auto">≥ 90</span>
                    </div>
                    <div class="flex items-center gap-2 bg-yellow-100 dark:bg-yellow-500/20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-400">
                        <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                        <span class="font-semibold text-gray-800 dark:text-white">Gold</span><span class="text-gray-500 dark:text-gray-300 ml-auto">75–89.9</span>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-200 dark:bg-gray-400/20 p-3 rounded-lg border border-gray-300 dark:border-gray-500">
                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                        <span class="font-semibold text-gray-800 dark:text-white">Silver</span><span class="text-gray-500 dark:text-gray-300 ml-auto">50–74.9</span>
                    </div>
                    <div class="flex items-center gap-2 bg-amber-100 dark:bg-amber-600/20 p-3 rounded-lg border border-amber-200 dark:border-amber-500">
                        <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                        <span class="font-semibold text-gray-800 dark:text-white">Bronze</span><span class="text-gray-500 dark:text-gray-300 ml-auto">25–49.9</span>
                    </div>
                    <div class="flex items-center gap-2 bg-red-100 dark:bg-red-600/20 p-3 rounded-lg border border-red-200 dark:border-red-500">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="font-semibold text-gray-800 dark:text-white">Participant</span><span class="text-gray-500 dark:text-gray-300 ml-auto">&lt; 25</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-light-card dark:bg-dark-card border-t border-light-border dark:border-dark-border mt-12 transition-colors">
        <div class="container mx-auto px-6 py-8 text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm">&copy; {{ date('Y') }} UMIX. Hak cipta dilindungi.</p>
        </div>
    </footer>

    <script>
        const rawLocations = @json($locations);

        // Deklarasi variabel map dan mapTileLayers di global scope
        let map = null;
        let mapTileLayers = {};

        function forceMapRefresh() {
            console.log("CACHE DIHAPUS: Memaksa pembaruan data dari server pada refresh berikutnya.");
            localStorage.removeItem('geocodedLocationsData');
            localStorage.removeItem('locationsCacheVersion');
            alert('Cache peta telah dihapus. Halaman akan dimuat ulang untuk mendapatkan data terbaru.');
            window.location.reload();
        }
        // --- SKRIP PENGALIH TEMA ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const htmlEl = document.documentElement;

        // Fungsi untuk mengubah tema
        const toggleTheme = () => {
            htmlEl.classList.toggle('dark');
            const isDarkMode = htmlEl.classList.contains('dark');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeUI(isDarkMode);
        };

        // Fungsi untuk memperbarui UI
        const updateThemeUI = (isDarkMode) => {
            darkIcon.classList.toggle('hidden', !isDarkMode);
            lightIcon.classList.toggle('hidden', isDarkMode);
            
            // Tambah/hapus kelas filter pada tile layer yang aktif
            if (map && mapTileLayers.cartoVoyager) {
                 mapTileLayers.cartoVoyager.getContainer().classList.toggle('dark-tiles', isDarkMode);
                 mapTileLayers.tonerLite.getContainer().classList.toggle('dark-tiles', isDarkMode);
                 mapTileLayers.osm.getContainer().classList.toggle('dark-tiles', isDarkMode);
            }
        };

        // Event listener untuk tombol
        themeToggleBtn.addEventListener('click', toggleTheme);

        // Terapkan tema saat halaman dimuat
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDarkMode = savedTheme === 'dark' || (savedTheme === null && prefersDark);

        if (isDarkMode) {
            htmlEl.classList.add('dark');
        } else {
            htmlEl.classList.remove('dark');
        }
        updateThemeUI(isDarkMode);

        document.addEventListener('DOMContentLoaded', async function() {
            
            const loadingOverlay = document.getElementById('loading-overlay');
            const mapElement = document.getElementById('map');
            
            if (!mapElement) {
                console.error("Elemen peta dengan id 'map' tidak ditemukan.");
                return;
            }
            if (loadingOverlay) loadingOverlay.style.display = 'flex';

            map = L.map('map', { zoomControl: false }).setView([-2.548926, 118.0148634], 5);
            L.control.zoom({ position: 'topright' }).addTo(map);

            // Simpan tile layers ke mapTileLayers agar bisa diakses global
            mapTileLayers.cartoVoyager = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO' });
            mapTileLayers.cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO' });
            mapTileLayers.osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' });
            mapTileLayers.tonerLite = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CARTO', className: 'grayscale-tiles' });
            mapTileLayers.esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' });
            mapTileLayers.cartoVoyager.addTo(map);

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
                    <div class="p-3">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">${loc.name}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${loc.province ? loc.province.name : ''}, ${loc.province && loc.province.country ? loc.province.country.name : 'Indonesia'}</p>
                    </div>
                    <div class="mt-1 pt-3 px-4 pb-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Peringkat:</span>
                            <span class="px-3 py-1 text-xs font-bold text-white rounded-full" style="background-color: ${rank.color};">${rank.name}</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Skor:</span>
                            <span class="font-bold text-gray-900 dark:text-white">${score.toFixed(2)}</span>
                        </div>
                    </div>
                </div>
                `;
                
                marker.bindPopup(popupContent, { className: 'custom-popup' });
                markers.addLayer(marker);
                markerBounds.push([loc.lat, loc.lng]);
            }
            
            map.addLayer(markers);
            if (markerBounds.length > 0) {
                map.fitBounds(markerBounds, { padding: [50, 50], maxZoom: 16 });
            }

            const baseMaps = {
                "Minimalis": mapTileLayers.cartoVoyager, "Hitam Putih": mapTileLayers.tonerLite, "Mode Gelap": mapTileLayers.cartoDark,
                "Satelit": mapTileLayers.esriSatellite, "Peta Jalan": mapTileLayers.osm
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