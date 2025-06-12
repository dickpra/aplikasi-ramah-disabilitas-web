    <div x-data="{ open: false }" class="relative ms-4">
        {{-- Tombol utama yang menampilkan bahasa saat ini --}}
        <button
            x-on:click="open = ! open"
            type="button"
            class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-semibold text-gray-950 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-white dark:hover:bg-white/5"
        >
            <span>{{ strtoupper($currentLocale) }}</span>

            <svg class="ms-auto h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        {{-- Panel dropdown yang berisi daftar bahasa --}}
        <div
            x-show="open"
            x-on:click.away="open = false"
            x-transition:enter-start="opacity-0"
            x-transition:leave-end="opacity-0"
            class="absolute z-10 mt-2 w-32 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
            style="display: none;"
        >
            <div class="py-1">
                @foreach ($languages as $language)
                    <button
                        wire:click="switchLocale('{{ $language->code }}')"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm @if($currentLocale === $language->code) bg-gray-100 dark:bg-gray-700 @endif text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <span class="font-semibold">{{ strtoupper($language->code) }}</span>
                        <span>{{ $language->name }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
    