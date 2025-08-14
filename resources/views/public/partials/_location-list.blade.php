{{-- resources/views/public/partials/_location-list.blade.php --}}

<div class="space-y-4">
    @forelse ($locations as $location)
        @php
            // Peringkat (rank) tetap dihitung berdasarkan skor total (final_score)
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
                continue; // Jangan tampilkan jika skor di bawah 25
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
                
                {{-- Bagian Skor yang Dibuat Dinamis --}}
                <div class="text-yellow-500 dark:text-yellow-400 font-bold flex items-center gap-1 text-sm sm:text-base">
                    <i class="fas fa-star"></i>
                    @if(isset($selected_category) && !empty($selected_category))
                        {{-- Jika ada filter kategori, tampilkan skor kategori --}}
                        {{ number_format($location->category_score, 2) }}
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1 whitespace-nowrap">({{ $selected_category }})</span>
                    @else
                        {{-- Jika tidak, tampilkan skor total --}}
                        {{ number_format($location->final_score, 2) }}
                    @endif
                </div>

                <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $color }}">
                    {{ $rank }}
                </span>

                                {{-- === TOMBOL DOWNLOAD BARU === --}}
                    <a href="{{ route('locations.report.preview', $location) }}" 
                    target="_blank" 
                    class="text-gray-400 hover:text-primary-500 dark:hover:text-accent-500 transition ml-2"
                    title="Unduh Pratinjau Laporan">
                        <i class="fas fa-download"></i>
                    </a>
                    {{-- ========================== --}}
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 dark:text-gray-400 py-12 px-6">
            <div class="mx-auto w-16 h-16 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-800 dark:text-gray-200">Tidak Ada Hasil Ditemukan</h3>
            <p class="mt-2 text-sm">Coba sesuaikan atau reset filter Anda untuk menemukan hasil yang lain.</p>
        </div>
    @endforelse
</div>

{{-- Tampilkan link paginasi --}}
<div class="mt-8">
    {{ $locations->links() }}
</div>
