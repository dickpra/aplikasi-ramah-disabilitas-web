<!DOCTYPE html>
<html lang="id" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UMIX | Indeks Inklusif</title>
  <link rel="icon" href="{{ asset('img/favicon.png') }}" />
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Poppins', 'sans-serif']
          },
          colors: {
            primary: {
              100: '#e0e7ff',
              300: '#7c3aed',
              500: '#4f46e5',
              700: '#3730a3',
              900: '#1e1b4b',
            },
            accent: {
              500: '#a3e635',
              600: '#65a30d',
            },
            dark: {
              bg: '#0f172a',
              card: '#1e293b',
              border: '#334155',
            }
          }
        }
      }
    }
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Poppins', sans-serif;
    }
    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    .animate-fade-in {
      animation: fade-in 1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    .animate-float {
      animation: float 6s ease-in-out infinite;
    }
    .shadow-lg {
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
    }
    .shadow-xl {
      box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .gradient-text {
      background: linear-gradient(90deg, #a3e635 0%, #2e9adc 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .gradient-text-light {
      background: linear-gradient(90deg, #74d618 0%, #2e9adc 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
    }
    .bg-light-card {
  background-color: #fff !important; /* solid putih untuk mode terang */
}
.dark .bg-light-card {
  background-color: #1e293b !important; /* warna gelap untuk mode dark */
}
    /* == GAYA BARU YANG LEBIH STABIL UNTUK MENU AKTIF == */
    .nav-link-active {
        color: #4f46e5 !important; /* Warna primary-500 dengan !important */
        font-weight: 700 !important;
    }
    .dark .nav-link-active {
        color: #a3e635 !important; /* Warna accent-500 dengan !important */
    }
  </style>
</head>

<body class="bg-white text-gray-900 dark:bg-dark-bg dark:text-white">

    {{-- Mengambil data pengaturan dari database --}}
    @php
        $settings = \App\Models\DashboardSetting::first();
    @endphp

    <header class="bg-light-card dark:bg-dark-card border-b border-light-border dark:border-dark-border px-4 sm:px-6 py-4 flex justify-between items-center sticky top-0 z-20 transition-colors">
            <a href="{{ route('dashboard.public') }}">
                {{-- <div class="text-2xl font-bold text-primary-700 dark:text-accent-500">
                  UMIX
                </div> --}}
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('img/UMIX color.png') }}" alt="Logo" class=" h-8 w-auto">
                    {{-- <span class="text-xl font-bold text-primary-700 dark:text-accent-500">UMIX</span> --}}
                </div>
            </a>
            <nav id="main-nav" class="hidden md:flex items-center space-x-6">
                <a href="#dashboard" class="nav-link text-gray-700 dark:text-gray-300 hover:text-primary-500 dark:hover:text-accent-500 transition font-medium">{{ __('Dashboard') }}</a>
                <a href="#about" class="nav-link text-gray-700 dark:text-gray-300 hover:text-primary-500 dark:hover:text-accent-500 transition font-medium">{{ __('About Me') }}</a>
                <a href="#credit" class="nav-link text-gray-700 dark:text-gray-300 hover:text-primary-500 dark:hover:text-accent-500 transition font-medium">{{ __('Credit') }}</a>
                <a href="#guidebook" class="nav-link text-gray-700 dark:text-gray-300 hover:text-primary-500 dark:hover:text-accent-500 transition font-medium">{{ __('Guidebook') }}</a>
                <a href="#metodologi" class="nav-link text-gray-700 dark:text-gray-300 hover:text-primary-500 dark:hover:text-accent-500 transition font-medium">{{ __('Metodologi') }}</a>
            </nav>
            <div class="flex items-center space-x-2">
              {{-- === MEMANGGIL LANGUAGE SWITCHER ANDA YANG SUDAH ADA === --}}
                @livewire('language-switcher')
              {{-- === MEMANGGIL LANGUAGE SWITCHER ANDA YANG SUDAH ADA === --}}
                <button onclick="toggleTheme()" class="text-sm font-medium px-3 py-2 rounded-md bg-gray-100 dark:bg-dark-card dark:text-white border dark:border-dark-border transition">
                    <span id="themeToggleText">🌙 Dark</span>
                </button>
            </div>
        </header>

  <section id="dashboard" class="scroll-mt-20 relative bg-gradient-to-br from-blue-100 via-indigo-200 to-purple-200 dark:from-primary-900 dark:via-primary-700 dark:to-primary-500 py-20 sm:py-24 px-4 sm:px-6 text-center overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-0 left-0 w-32 h-32 md:w-64 md:h-64 bg-accent-500 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float"></div>
      <div class="absolute bottom-0 right-0 w-32 h-32 md:w-64 md:h-64 bg-primary-300 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative max-w-5xl mx-auto animate-fade-in">
      <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold mb-6 leading-tight">
        {{-- Teks dinamis dari pengaturan --}}
        {!! nl2br(e($settings?->hero_title)) !!}
      </h1>
      <p class="text-lg sm:text-xl md:text-2xl text-gray-800 dark:text-white/90 max-w-3xl mx-auto mb-10">
        {{-- Teks dinamis dari pengaturan --}}
        {{ $settings?->hero_subtitle }}
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
        <a href="{{ url('/login') }}"
          class="dark:bg-accent-500 w-full sm:w-auto px-8 py-4 bg-white hover:bg-gray-100 text-primary-700 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
          <span class="relative z-10">{{ __('Log In') }}</span>
        </a>
      </div>
    </div>
  </section>

  <section id="statistics" class="scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    {{-- Konten statistik tetap sama --}}
    <div class="text-center mb-12 sm:mb-16">
      <h2 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('Statistik Indeks Inklusi') }}</h2>
      <div class="w-24 h-1 bg-accent-500 mx-auto"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
      @php
        $cards = [
          ['title' => __('Lokasi Terdaftar'), 'icon' => 'fa-building', 'color' => 'bg-primary-500', 'value' => \App\Models\Location::count()],
          ['title' => __('Sudah Dinilai'), 'icon' => 'fa-check-circle', 'color' => 'bg-emerald-500', 'value' => $locations->total()],
          ['title' => __('Rata-rata Skor'), 'icon' => 'fa-star', 'color' => 'bg-yellow-400', 'value' => number_format($locations->avg('final_score'), 2)],
          ['title' => __('Asesor Terdaftar'), 'icon' => 'fa-users', 'color' => 'bg-sky-500', 'value' => \App\Models\Assessor::count()],
        ];
      @endphp
      @foreach ($cards as $card)
      <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200 dark:border-dark-border">
        <div class="flex items-center gap-4">
          <div class="p-3 {{ $card['color'] }} text-white rounded-full shadow-md">
            <i class="fas {{ $card['icon'] }} text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $card['title'] }}</p>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</h2>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </section>

  <section id="peringkat" class="scroll-mt-20 container mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    {{-- Konten peringkat tetap sama --}}
    <div class="bg-white dark:bg-dark-card p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200 dark:border-dark-border">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">🏆 {{ __('Peringkat Lokasi Terbaik') }}</h2>
        <a href="{{ route('map.public') }}" class="text-accent-700 hover:text-accent-500 dark:text-accent-500 dark:hover:text-accent-400 flex items-center gap-2 transition font-semibold">
          {{ __('Lihat Peta Persebaran') }} <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      {{-- ==== FORM FILTER BARU ==== --}}
      {{-- ==== FORM FILTER BARU (TANPA TOMBOL SUBMIT) ==== --}}
      <div id="filter-form" class="mb-8 p-4 bg-gray-50 dark:bg-dark-bg rounded-lg border dark:border-dark-border">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              
              {{-- Filter Negara --}}
              <div>
                  <label for="country_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Negara') }}</label>
                  <select name="country_id" id="country_id" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-dark-card dark:border-dark-border focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                      <option value="">{{ __('Semua Negara') }}</option>
                      @foreach($countries as $country)
                          <option value="{{ $country->id }}" @selected(request('country_id') == $country->id)>
                              {{ $country->name }}
                          </option>
                      @endforeach
                  </select>
              </div>

              {{-- Filter Provinsi --}}
              <div>
                  <label for="province_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Provinsi') }}</label>
                  <select name="province_id" id="province_id" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-dark-card dark:border-dark-border focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                      <option value="">{{ __('Semua Provinsi') }}</option>
                      {{-- Opsi ini akan diisi oleh JavaScript. --}}
                      {{-- Kode di bawah ini untuk memastikan filter tetap terpilih saat halaman di-load ulang --}}
                      @if(request('country_id'))
                          @foreach(\App\Models\Country::find(request('country_id'))->provinces as $province)
                              <option value="{{ $province->id }}" @selected(request('province_id') == $province->id)>
                                  {{ $province->name }}
                              </option>
                          @endforeach
                      @endif
                  </select>
              </div>

              {{-- Filter Tipe Lokasi --}}
              <div>
                  <label for="location_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tipe Lokasi') }}</label>
                  <select name="location_type" id="location_type" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-dark-card dark:border-dark-border focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                      <option value="">{{ __('Semua Tipe') }}</option>
                      @foreach($locationTypes as $type)
                          <option value="{{ $type }}" @selected(request('location_type') == $type)>
                              {{ $type }}
                          </option>
                      @endforeach
                  </select>
              </div>
              {{-- Filter Kategori Indikator --}}
              {{-- <div>
                  <label for="indicator_category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori Penilaian</label>
                  <select name="indicator_category" id="indicator_category" class="filter-input mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-dark-card dark:border-dark-border focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                      <option value="">Semua Kategori</option>
                      @foreach($indicatorCategories as $category)
                          <option value="{{ $category }}" @selected(request('indicator_category') == $category)>
                              {{ $category }}
                          </option>
                      @endforeach
                  </select>
              </div> --}}
          </div>
      </div>
      {{-- ==== END FORM FILTER BARU ==== --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-700 dark:text-white mb-10">
        <div class="flex items-center gap-2 bg-blue-100 dark:bg-blue-600/20 p-3 rounded-lg border border-blue-400 dark:border-blue-600">
          <div class="w-3 h-3 bg-blue-400 rounded-full flex-shrink-0"></div>
          <span class="font-semibold">Diamond</span>
          <span class="text-gray-500 dark:text-gray-400 ml-auto">≥ 90</span>
        </div>
        <div class="flex items-center gap-2 bg-yellow-100 dark:bg-yellow-500/20 p-3 rounded-lg border border-yellow-400">
          <div class="w-3 h-3 bg-yellow-300 rounded-full flex-shrink-0"></div>
          <span class="font-semibold">Gold</span>
          <span class="text-gray-500 dark:text-gray-300 ml-auto">75–89.9</span>
        </div>
        <div class="flex items-center gap-2 bg-gray-200 dark:bg-gray-400/20 p-3 rounded-lg border border-gray-300">
          <div class="w-3 h-3 bg-gray-300 rounded-full flex-shrink-0"></div>
          <span class="font-semibold">Silver</span>
          <span class="text-gray-500 dark:text-gray-300 ml-auto">50–74.9</span>
        </div>
        <div class="flex items-center gap-2 bg-orange-100 dark:bg-orange-500/20 p-3 rounded-lg border border-orange-400 dark:border-orange-500">
          <div class="w-3 h-3 bg-orange-400 rounded-full flex-shrink-0"></div>
          <span class="font-semibold">Bronze</span>
          <span class="text-gray-500 dark:text-gray-300 ml-auto">25–49.9</span>
        </div>
      </div>
      {{-- ==== WADAH UNTUK HASIL FILTER ==== --}}
      <div id="location-results">
          @include('public.partials._location-list', ['locations' => $locations])
      </div>
      {{-- <div class="mt-8">{{ $locations->links() }}</div>
    </div> --}}
  </section>

  <!-- Contoh untuk About -->
<section id="about" class="scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
  <div class="text-center mb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary-500 to-accent-500 text-white rounded-full shadow-lg animate-float">
      <i class="fas fa-info-circle text-2xl"></i>
    </div>
    <h2 class="mt-6 text-3xl sm:text-4xl font-extrabold gradient-text">{{ __('Tentang Kami') }}</h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">{{ __('Kenali latar belakang dan tujuan dari platform ini.') }}</p>
  </div>
  <div class="backdrop-blur-lg bg-white/70 dark:bg-dark-card/60 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-dark-border card-hover">
    <div class="prose dark:prose-invert max-w-none text-lg leading-relaxed">
      {!! $settings?->about_me !!}
    </div>
  </div>
</section>

<!-- Contoh untuk Credit -->
<section id="credit" class="scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
  <div class="text-center mb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 text-white rounded-full shadow-lg animate-float">
      <i class="fas fa-hands-helping text-2xl"></i>
    </div>
    <h2 class="mt-6 text-3xl sm:text-4xl font-extrabold gradient-text">{{ __('Kredit') }}</h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">{{ __('Penghargaan untuk pihak yang berkontribusi.') }}</p>
  </div>
  <div class="backdrop-blur-lg bg-white/70 dark:bg-dark-card/60 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-dark-border card-hover">
    <div class="prose dark:prose-invert max-w-none text-lg leading-relaxed">
      {!! $settings?->credit !!}
    </div>
  </div>
</section>

<!-- Contoh untuk Guidebook -->
<section id="guidebook" class="scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
  <div class="text-center mb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-sky-500 to-indigo-500 text-white rounded-full shadow-lg animate-float">
      <i class="fas fa-book-open text-2xl"></i>
    </div>
    <h2 class="mt-6 text-3xl sm:text-4xl font-extrabold gradient-text">{{ __('Panduan') }}</h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">{{ __('Panduan resmi untuk memahami dan menggunakan platform.') }}</p>
  </div>
  <div class="backdrop-blur-lg bg-white/70 dark:bg-dark-card/60 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-dark-border card-hover">
    <div class="prose dark:prose-invert max-w-none text-lg leading-relaxed">
      {!! $settings?->guidebook !!}
    </div>
  </div>
</section>

<!-- Contoh untuk Metodologi -->
<section id="metodologi" class="scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
  <div class="text-center mb-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-pink-500 to-red-500 text-white rounded-full shadow-lg animate-float">
      <i class="fas fa-project-diagram text-2xl"></i>
    </div>
    <h2 class="mt-6 text-3xl sm:text-4xl font-extrabold gradient-text">{{ __('Metodologi') }}</h2>
    <p class="mt-2 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">{{ __('Cara dan langkah yang digunakan dalam pengukuran Indeks Inklusi.') }}</p>
  </div>
  <div class="backdrop-blur-lg bg-white/70 dark:bg-dark-card/60 rounded-2xl p-8 shadow-xl border border-gray-200 dark:border-dark-border card-hover">
    <div class="prose dark:prose-invert max-w-none text-lg leading-relaxed">
      {!! $settings?->metodologi !!}
    </div>
  </div>
</section>


  <div class="h-16"></div> <!-- Spacer for footer -->
  
  <footer class="bg-white dark:bg-dark-card border-t border-gray-200 dark:border-dark-border py-5 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
      <div class="flex flex-col items-center md:items-start">
        <div class="mb-4">
          <img src="{{ asset('img/UMIX color.png') }}" alt="Logo" class="h-5 w-auto mx-auto md:mx-0">
        </div>
        <p class="text-gray-600 dark:text-gray-400">{{ __('Membangun masyarakat inklusif melalui aksesibilitas yang setara.') }}</p>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">{{ __('Tautan Cepat') }}</h3>
        <ul class="space-y-2">
          <li><a href="#peringkat" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">{{ __('Peringkat') }}</a></li>
          <li><a href="#about" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">{{ __('Tentang Kami') }}</a></li>
          <li><a href="{{ route('map.public') }}" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">{{ __('Peta Persebaran') }}</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">{{ __('Hubungi Kami') }}</h3>
        <ul class="space-y-2">
          {{-- Kontak dinamis dari pengaturan --}}
          <li class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400"><i class="fas fa-envelope"></i> {{ $settings?->contact_email }}</li>
          <li class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400"><i class="fas fa-phone-alt"></i> {{ $settings?->contact_phone }}</li>
        </ul>
      </div>
    </div>
    <div class="max-w-7xl mx-auto border-t border-gray-200 dark:border-dark-border mt-5 pt-5 text-center text-gray-500 dark:text-gray-400">
      <p>&copy; {{ date('Y') }} UMIX. All rights reserved.</p>
    </div>
  </footer>

@livewireScripts
  <script>

    // ==== SCRIPT BARU UNTUK LIVE FILTER ====
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('filter-form');
        const resultsContainer = document.getElementById('location-results');
        const filterInputs = form.querySelectorAll('.filter-input');
        const countrySelect = document.getElementById('country_id');
        const provinceSelect = document.getElementById('province_id');

        // --- FUNGSI UNTUK LIVE FILTER (TIDAK BERUBAH) ---
        function loadLocations() {
            const params = new URLSearchParams();
            filterInputs.forEach(input => {
                if (input.value) {
                    params.append(input.name, input.value);
                }
            });
            const queryString = params.toString();
            const url = `{{ route('dashboard.public') }}?${queryString}`;

            resultsContainer.style.opacity = '0.5';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.text())
                .then(html => {
                    resultsContainer.innerHTML = html;
                    resultsContainer.style.opacity = '1';
                    window.history.pushState({}, '', url);
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsContainer.style.opacity = '1';
                });
        }
        // --- FUNGSI UNTUK MENGAMBIL PROVINSI ---
        function updateProvinces() {
            const countryId = countrySelect.value;
            // Simpan nilai provinsi yang sedang terpilih
            const selectedProvince = provinceSelect.value;

            provinceSelect.innerHTML = '<option value="">{{ __('Memuat...') }}</option>';

            if (!countryId) {
                provinceSelect.innerHTML = '<option value="">{{ __('Semua Provinsi') }}</option>';
                loadLocations();
                return;
            }

            fetch(`/get-provinces-by-country/${countryId}`)
                .then(response => response.json())
                .then(provinces => {
                    provinceSelect.innerHTML = '<option value="">{{ __('Semua Provinsi') }}</option>';
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        // Jika provinsi ini adalah yang sebelumnya terpilih, pilih kembali
                        if(province.id == selectedProvince) {
                            option.selected = true;
                        }
                        provinceSelect.appendChild(option);
                    });
                    loadLocations(); // Panggil live filter setelah provinsi diperbarui
                })
                .catch(error => {
                    console.error('Error fetching provinces:', error);
                    provinceSelect.innerHTML = '<option value="">{{ __('Gagal memuat') }}</option>';
                });
        }

        // Tambahkan event listener ke semua filter
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Jika yang berubah adalah negara, panggil fungsi provinsi
                if (this.id === 'country_id') {
                    // Reset pilihan provinsi saat negara berubah
                    provinceSelect.value = '';
                    updateProvinces();
                } else {
                    // Jika filter lain yang berubah, langsung load lokasi
                    loadLocations();
                }
            });
        });
    });

    // ... (Skrip toggle theme dan navigasi aktif Anda tetap sama)
    const themeToggleBtn = document.getElementById('themeToggleText');
    const htmlEl = document.documentElement;

    function toggleTheme() {
      const isDark = htmlEl.classList.toggle('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      themeToggleBtn.innerText = isDark ? '☀️ Light' : '🌙 Dark';
    }

    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        htmlEl.classList.add('dark');
        themeToggleBtn.innerText = '☀️ Light';
      } else {
        htmlEl.classList.remove('dark');
        themeToggleBtn.innerText = '🌙 Dark';
      }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('#main-nav a.nav-link');
        if (sections.length === 0 || navLinks.length === 0) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach(link => {
                        link.classList.remove('nav-link-active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('nav-link-active');
                        }
                    });
                }
            });
        }, { rootMargin: '-50% 0px -50% 0px', threshold: 0 });
        sections.forEach(section => { observer.observe(section); });
    });
  </script>
</body>
</html>
