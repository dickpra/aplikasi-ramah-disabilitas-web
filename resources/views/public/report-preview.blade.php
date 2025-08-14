<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pratinjau Laporan - {{ $location->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #4f46e5; }
        .header p { margin: 5px 0; font-size: 14px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 18px; font-weight: bold; color: #3730a3; border-bottom: 2px solid #e0e7ff; padding-bottom: 5px; margin-bottom: 15px; }
        .info-box { background-color: #f3f4f6; border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; }
        .info-box p { margin: 0 0 8px 0; }
        .info-box strong { color: #1e1b4b; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e0e7ff; font-weight: bold; }
        .cta-box { margin-top: 30px; padding: 20px; border: 2px dashed #7c3aed; border-radius: 8px; text-align: center; }
        .cta-box h3 { margin-top: 0; font-size: 16px; color: #3730a3; }
        .cta-box a { color: #4f46e5; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>UMIX</h1>
            <p>Pratinjau Laporan Indeks Inklusi</p>
        </div>

        <div class="section">
            <h2 class="section-title">Detail Lokasi</h2>
            <div class="info-box">
                <p><strong>Nama Lokasi:</strong> {{ $location->name }}</p>
                <p><strong>Tipe:</strong> {{ $location->location_type }}</p>
                <p><strong>Provinsi:</strong> {{ $location->province->name ?? 'N/A' }}</p>
                <p><strong>Negara:</strong> {{ $location->province->country->name ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Hasil Penilaian Umum</h2>
            <div class="info-box">
                <p><strong>Skor Akhir:</strong> {{ number_format($location->final_score, 2) }}</p>
                <p><strong>Peringkat:</strong> {{ $location->rank }}</p>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Ringkasan Penilaian per Kategori (Top 5)</h2>
            <p>Berikut adalah ringkasan skor rata-rata untuk 5 kategori penilaian teratas di lokasi ini. Skor penuh hanya tersedia di laporan lengkap.</p>
            <table>
                <thead>
                    <tr>
                        <th>Kategori Penilaian</th>
                        <th>Skor Rata-Rata (dari 100)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoryScores as $score)
                        <tr>
                            {{-- Ganti $score->category menjadi $score->category_name --}}
                            <td>{{ $score->category_name }}</td>
                            <td>{{ number_format($score->average_score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Data skor per kategori tidak tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="cta-box">
            <h3>Dapatkan Laporan Lengkap!</h3>
            <p>
                Pratinjau ini hanya menampilkan sebagian kecil dari data penilaian. Untuk mendapatkan laporan lengkap yang berisi detail skor per indikator, analisis, dan rekomendasi, silakan hubungi kami melalui email:
            </p>
            <p><a href="mailto:ediyanto.fip@um.ac.id">ediyanto.fip@um.ac.id</a></p>
        </div>
    </div>
</body>
</html>