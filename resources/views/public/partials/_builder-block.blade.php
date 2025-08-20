{{-- resources/views/public/partials/_builder-block.blade.php --}}
@switch($block['type'])
    @case('heading')
        <{{ $block['data']['level'] }} class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $block['data']['content'] }}
        </{{ $block['data']['level'] }}>
        @break

    @case('paragraph')
        <div class="prose dark:prose-invert max-w-none text-lg leading-relaxed">
            {!! $block['data']['content'] !!}
        </div>
        @break

    @case('image')
        <figure>
            <img src="{{ Storage::url($block['data']['url']) }}" alt="{{ $block['data']['alt'] ?? '' }}" class="rounded-lg shadow-lg w-full h-auto">
        </figure>
        @break
    
    @case('pdf_document')
        <iframe 
            src="{{ Storage::url($block['data']['url']) }}" 
            width="100%" 
            height="{{ $block['data']['height'] ?? '800px' }}" 
            style="border: 1px solid #ccc; border-radius: 8px;"
            class="shadow-lg"
        >
            <p>Browser Anda tidak mendukung PDF embed. <a href="{{ Storage::url($block['data']['url']) }}">Unduh PDF</a>.</p>
        </iframe>
        @break

    {{-- ==== LOGIKA BARU UNTUK SMART EMBED ==== --}}
    @case('smart_embed')
        @php
            $url = $block['data']['url'];
            $embedUrl = null;

            // Cek YouTube
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
                $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
            }
            // Cek Google Drive
            elseif (str_contains($url, 'drive.google.com/file')) {
                $embedUrl = str_replace('/view', '/preview', $url);
            }
            // Cek Canva
            elseif (str_contains($url, 'canva.com/design')) {
                // Hapus parameter query yang mungkin sudah ada dan tambahkan ?embed
                $urlParts = parse_url($url);
                $embedUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?embed';
            }
        @endphp

        @if($embedUrl)
            <div style="position: relative; width: 100%; height: 0; padding-top: 56.25%;" class="rounded-lg shadow-lg overflow-hidden">
                <iframe 
                    loading="lazy" 
                    style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; border: none; padding: 0;margin: 0;" 
                    src="{{ $embedUrl }}" 
                    allowfullscreen="allowfullscreen" 
                    allow="fullscreen">
                </iframe>
            </div>
        @else
            <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <p><strong>Tautan tidak didukung:</strong> Tautan yang Anda masukkan tidak dapat disisipkan secara otomatis. Pastikan tautan berasal dari YouTube, Google Drive, atau Canva.</p>
            </div>
        @endif
        @break
@endswitch
