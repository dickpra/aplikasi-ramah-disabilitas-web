<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Indeks Inklusi</title>
  <link rel="icon" href="{{ asset('img/favicon.png') }}" />
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = {
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
              500: '#a3e635', // Lime
              700: '#65a30d',
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
      background: linear-gradient(90deg, #a3e635 0%, #4f46e5 100%);
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
<body class="bg-dark-bg text-white">
  <!-- Header -->
  <header class="bg-dark-card border-b border-dark-border px-6 py-4 flex justify-between items-center">
    <img src="{{ asset('img/navbar.png') }}" class="h-10" alt="Logo" />
    <nav class="space-x-4">
      <a href="{{ route('map.public') }}" class="text-accent-500 hover:underline font-semibold">Peta Persebaran</a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="relative bg-gradient-to-br from-primary-900 via-primary-700 to-primary-500 py-24 px-6 text-center overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-0 left-0 w-64 h-64 bg-accent-500 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float"></div>
      <div class="absolute bottom-0 right-0 w-64 h-64 bg-primary-300 rounded-full mix-blend-overlay filter blur-3xl opacity-70 animate-float" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative max-w-5xl mx-auto animate-fade-in">
      <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
        Selamat Datang di <br>
        <span class="gradient-text">Indeks Inklusi</span>
      </h1>
      <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-10">
        Menilai tingkat aksesibilitas lokasi publik untuk penyandang disabilitas. 
        <span class="block mt-2 font-medium">Mari ciptakan dunia yang lebih inklusif!</span>
      </p>
      <div class="flex flex-wrap justify-center gap-6">
        <a href="{{ url(config('filament.panels.administrator.path', '/administrator/login')) }}"
           class="px-8 py-4 bg-accent-500 hover:bg-accent-600 text-dark-bg rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
           <span class="relative z-10">Login Admin</span>
        </a>
        <a href="{{ url(config('filament.panels.assessor.path', '/assessor/login')) }}"
           class="px-8 py-4 bg-white hover:bg-gray-100 text-primary-700 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
           <span class="relative z-10">Login Asesor</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="max-w-7xl mx-auto px-6 py-20">
    <div class="text-center mb-16">
      <h2 class="text-3xl font-bold mb-4">Statistik Indeks Inklusi</h2>
      <div class="w-24 h-1 bg-accent-500 mx-auto"></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="bg-dark-card p-8 rounded-2xl shadow-lg border border-dark-border card-hover">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-primary-500 text-white rounded-full shadow-md">
            <i class="fas fa-building text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-300 uppercase tracking-wider">Lokasi Terdaftar</p>
            <h3 class="text-2xl font-bold text-white">{{ \App\Models\Location::count() }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-dark-card p-6 rounded-xl shadow hover:shadow-lg border border-dark-border">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-emerald-500 text-white rounded-full">
            <i class="fas fa-check-circle text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-400">Sudah Dinilai</p>
            <h3 class="text-2xl font-bold">{{ $locations->total() }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-dark-card p-6 rounded-xl shadow hover:shadow-lg border border-dark-border">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-yellow-400 text-white rounded-full">
            <i class="fas fa-star text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-400">Rata-rata Skor</p>
            <h3 class="text-2xl font-bold">{{ number_format($locations->avg('final_score'), 2) }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-dark-card p-6 rounded-xl shadow hover:shadow-lg border border-dark-border">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-sky-500 text-white rounded-full">
            <i class="fas fa-users text-xl"></i>
          </div>
          <div>
            <p class="text-sm text-gray-400">Asesor Terdaftar</p>
            <h3 class="text-2xl font-bold">{{ \App\Models\Assessor::count() }}</h3>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Peringkat Section -->
  <section class="max-w-5xl mx-auto px-6 pb-24">
    <div class="bg-dark-card p-8 rounded-2xl shadow-xl border border-dark-border">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-white">🏆 Peringkat Lokasi Terbaik</h2>
        <a href="{{ route('map.public') }}" class="text-accent-500 hover:text-accent-400 flex items-center gap-2 transition">
          Lihat Peta <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="space-y-4">
        @forelse ($locations as $location)
          <div class="flex items-center justify-between bg-dark-bg border border-dark-border p-5 rounded-xl hover:bg-primary-900/50 transition-all duration-300 group">
            <div class="flex items-center gap-4">
              <div class="p-3 bg-primary-500 text-white rounded-full">
                <i class="fas fa-university"></i>
              </div>
              <div>
                <h4 class="font-medium text-white">{{ $location->name }}</h4>
                <p class="text-sm text-gray-400">{{ $location->province->name ?? 'N/A' }}, {{ $location->province->country->name ?? 'N/A' }}</p>
              </div>
            </div>
            <div class="text-yellow-400 font-bold flex items-center gap-1">
              <i class="fas fa-star"></i>
              {{ number_format($location->final_score, 2) }}
            </div>
          </div>
        @empty
          <p class="text-center text-gray-400">Belum ada data peringkat.</p>
        @endforelse
      </div>

      <div class="mt-6">
        {{ $locations->links() }}
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark-card border-t border-dark-border py-12 px-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
      <div>
        <img src="{{ asset('img/navbar.png') }}" class="h-12 mb-4" alt="Logo" />
        <p class="text-gray-400">Membangun masyarakat inklusif melalui aksesibilitas yang setara.</p>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">Tautan Cepat</h3>
        <ul class="space-y-2">
          <li><a href="{{ route('map.public') }}" class="text-gray-400 hover:text-accent-500 transition">Peta Persebaran</a></li>
          <li><a href="#" class="text-gray-400 hover:text-accent-500 transition">Tentang Kami</a></li>
          <li><a href="#" class="text-gray-400 hover:text-accent-500 transition">Kontak</a></li>
        </ul>
      </div>
      <div>
        <h3 class="text-lg font-semibold mb-4">Hubungi Kami</h3>
        <div class="flex items-center gap-3 text-gray-400 mb-2">
          <i class="fas fa-envelope"></i>
          <span>info@indeksinklusi.id</span>
        </div>
        <div class="flex items-center gap-3 text-gray-400">
          <i class="fas fa-phone-alt"></i>
          <span>+62 123 4567 890</span>
        </div>
      </div>
    </div>
    <div class="max-w-7xl mx-auto border-t border-dark-border mt-12 pt-8 text-center text-gray-500">
      <p>© 2023 Indeks Inklusi. All rights reserved.</p>
    </div>
  </footer>

  <!-- Font Awesome 6 -->
  <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous" loading="lazy"></script>
</body>
</html>
