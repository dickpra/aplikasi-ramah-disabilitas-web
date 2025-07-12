<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Indeks Inklusi</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Tambahan font & config Tailwind --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#2563eb',
                        secondary: '#1e40af',
                    }
                }
            }
        }
    </script>

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-100 via-white to-blue-200 min-h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-6">
            {{-- <img src="{{ asset('img/logo.png') }}" alt="Logo" class="mx-auto h-16 mb-2"> --}}
            <h1 class="text-2xl font-bold text-gray-800">Selamat Datang</h1>
            <p class="text-sm text-gray-500">Silakan login untuk melanjutkan</p>
        </div>

        {{-- Error Alert --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.authenticate') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:outline-none text-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:outline-none text-sm">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" class="form-checkbox rounded border-gray-300 text-primary">
                    <span>Ingat saya</span>
                </label>
                {{-- <a href="#" class="text-primary hover:underline">Lupa password?</a> --}}
            </div>

            <button type="submit"
                class="w-full py-2 px-4 bg-primary hover:bg-secondary text-white font-semibold rounded-md transition duration-200">
                Login
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-gray-600">
            Belum punya akun? <a href="{{ url(config('filament.panels.assessor.path', '/assessor/register')) }}" class="text-primary hover:underline">Daftar di sini</a>
        </div>
    </div>

</body>
</html>
