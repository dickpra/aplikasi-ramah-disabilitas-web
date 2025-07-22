<!DOCTYPE html>
<html lang="id" class="">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Indeks Inklusi</title>
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
    body {
      font-family: 'Poppins', sans-serif;
      scroll-behavior: smooth;
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
  </style>
</head>

<body class="bg-white text-gray-900 dark:bg-dark-bg dark:text-white">

  <header class="bg-white dark:bg-dark-card border-b border-gray-200 dark:border-dark-border px-4 sm:px-6 py-4">
    <div class="container mx-auto flex flex-wrap justify-between items-center">
      <img src="{{ asset('img/navbar.png') }}" class="h-10" alt="Logo" />
      <nav class="space-x-2 sm:space-x-4 flex items-center mt-4 sm:mt-0">
        <a href="{{ route('map.public') }}" class="text-primary-700 dark:text-accent-500 hover:underline font-semibold text-sm sm:text-base">Peta Persebaran</a>
        
        <button onclick="toggleTheme()" class="text-sm font-medium px-3 py-2 rounded-md bg-gray-100 dark:bg-dark-card dark:text-white border dark:border-dark-border transition">
          <span id="themeToggleText">🌙 Dark</span>
        </button>
      </nav>
    </div>
  </header>

  <section class="relative bg-gradient-to-br from-blue-100 via-indigo-200 to-purple-200 dark:from-primary-900 dark:via-primary-700 dark:to-primary-500 py-20 sm:py-24 px-4 sm:px-6 text-center overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-0 left-0 w-32 h-32 md:w-64 md:h-64 bg-accent-500 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float"></div>
      <div class="absolute bottom-0 right-0 w-32 h-32 md:w-64 md:h-64 bg-primary-300 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative max-w-5xl mx-auto animate-fade-in">
      <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold mb-6 leading-tight">
        Selamat Datang di <br>
        <span class="dark:gradient-text2 gradient-text-light">UMIX</span>
      </h1>
      <p class="text-lg sm:text-xl md:text-2xl text-gray-800 dark:text-white/90 max-w-3xl mx-auto mb-10">
        Menilai tingkat aksesibilitas lokasi publik untuk penyandang disabilitas. 
        <span class="block mt-2 font-medium">Mari ciptakan dunia yang lebih inklusif!</span>
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
        {{-- <a href="{{ url(config('filament.panels.administrator.path', '/administrator/login')) }}"
          class="w-full sm:w-auto px-8 py-4 bg-accent-500 hover:bg-accent-600 text-dark-bg rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
          <span class="relative z-10">Login Admin</span>
        </a> --}}
        <a href="{{ url('/login') }}"
          class="dark:bg-accent-500 w-full sm:w-auto px-8 py-4 bg-white hover:bg-gray-100 text-primary-700 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
          <span class="relative z-10">Login</span>
        </a>
      </div>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
    <div class="text-center mb-12 sm:mb-16">
      <h2 class="text-3xl sm:text-4xl font-bold mb-4">Statistik Indeks Inklusi</h2>
      <div class="w-24 h-1 bg-accent-500 mx-auto"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
      @php
        $cards = [
          ['title' => 'Lokasi Terdaftar', 'icon' => 'fa-building', 'color' => 'bg-primary-500', 'value' => \App\Models\Location::count()],
          ['title' => 'Sudah Dinilai', 'icon' => 'fa-check-circle', 'color' => 'bg-emerald-500', 'value' => $locations->total()],
          ['title' => 'Rata-rata Skor', 'icon' => 'fa-star', 'color' => 'bg-yellow-400', 'value' => number_format($locations->avg('final_score'), 2)],
          ['title' => 'Asesor Terdaftar', 'icon' => 'fa-users', 'color' => 'bg-sky-500', 'value' => \App\Models\Assessor::count()],
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

  <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <div class="bg-white dark:bg-dark-card p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-200 dark:border-dark-border">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">🏆 Peringkat Lokasi Terbaik</h2>
        <a href="{{ route('map.public') }}" class="text-accent-700 hover:text-accent-500 dark:text-accent-500 dark:hover:text-accent-400 flex items-center gap-2 transition font-semibold">
          Lihat Peta <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-sm text-gray-700 dark:text-white mb-10">
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
        <div class="flex items-center gap-2 bg-red-100 dark:bg-red-600/20 p-3 rounded-lg border border-red-400 dark:border-red-500">
          <div class="w-3 h-3 bg-red-400 rounded-full flex-shrink-0"></div>
          <span class="font-semibold">Participant</span>
          <span class="text-gray-500 dark:text-gray-300 ml-auto">&lt; 25</span>
        </div>
      </div>

      <div class="space-y-4">
        @forelse ($locations as $location)
          @php
              $score = $location->final_score;
              if ($score >= 90) {
                  $rank = 'Diamond';
                  $color = 'bg-blue-600 text-white';
              } elseif ($score >= 75) {
                  $rank = 'Gold';
                  $color = 'bg-yellow-400 text-gray-900';
              } elseif ($score >= 50) {
                  $rank = 'Silver';
                  $color = 'bg-gray-300 text-gray-900';
              } elseif ($score >= 25) {
                  $rank = 'Bronze';
                  $color = 'bg-orange-500 text-white';
              } else {
                  $rank = 'Participant';
                  $color = 'bg-red-600 text-white';
              }
          @endphp

          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white dark:bg-dark-bg border border-gray-200 dark:border-dark-border p-4 sm:p-5 rounded-xl hover:bg-gray-50 dark:hover:bg-primary-900/50 transition-all duration-300 group gap-4">
            <div class="flex items-center gap-4 w-full sm:w-auto">
              <div class="p-3 bg-primary-500 text-white rounded-full hidden sm:block">
                <i class="fas fa-university"></i>
              </div>
              <div class="flex-grow">
                <h4 class="font-medium text-gray-800 dark:text-white">{{ $location->name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $location->province->name ?? 'N/A' }}, {{ $location->province->country->name ?? 'N/A' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto justify-between">
              <div class="text-yellow-500 dark:text-yellow-400 font-bold flex items-center gap-1 text-sm sm:text-base">
                <i class="fas fa-star"></i>
                {{ number_format($location->final_score, 2) }}
              </div>
              <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $color }}">
                {{ $rank }}
              </span>
            </div>
          </div>
        @empty
          <p class="text-center text-gray-500 dark:text-gray-400 py-8">Belum ada data peringkat.</p>
        @endforelse
      </div>

      <div class="mt-8">
        {{ $locations->links() }}
      </div>
    </div>
  </section>

  <footer class="bg-white dark:bg-dark-card border-t border-gray-200 dark:border-dark-border py-5 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
      <div class="flex flex-col items-center md:items-start">
        <img src="{{ asset('img/navbar.png') }}" class="h-12 mb-4" alt="Logo" />
        <p class="text-gray-600 dark:text-gray-400">Membangun masyarakat inklusif melalui aksesibilitas yang setara.</p>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">Tautan Cepat</h3>
        <ul class="space-y-2">
          <li><a href="{{ route('map.public') }}" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">Peta Persebaran</a></li>
          <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">Tentang Kami</a></li>
          {{-- <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-accent-500 transition">Kontak</a></li> --}}
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">Hubungi Kami</h3>
        <ul class="space-y-2">
          <li class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400"><i class="fas fa-envelope"></i> info@indeksinklusi.id</li>
          <li class="flex items-center justify-center md:justify-start gap-2 text-gray-600 dark:text-gray-400"><i class="fas fa-phone-alt"></i> +62 123 4567 890</li>
        </ul>
      </div>
    </div>
    <div class="max-w-7xl mx-auto border-t border-gray-200 dark:border-dark-border mt-5 pt-5 text-center text-gray-500 dark:text-gray-400">
      <p>&copy; {{ date('Y') }} UMIX. All rights reserved.</p>
    </div>
  </footer>

  <script>
    const themeToggleBtn = document.getElementById('themeToggleText');
    const htmlEl = document.documentElement;

    function toggleTheme() {
      const isDark = htmlEl.classList.toggle('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      themeToggleBtn.innerText = isDark ? '☀️ Light' : '🌙 Dark';
    }

    // Set theme on initial page load
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
  </script>
</body>
</html>