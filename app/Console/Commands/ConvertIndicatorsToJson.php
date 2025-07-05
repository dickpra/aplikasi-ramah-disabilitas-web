<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertIndicatorsToJson extends Command
{
    protected $signature = 'translations:convert-indicators';
    protected $description = 'Convert existing indicator data to JSON format safely without using the Eloquent model.';

    public function handle()
    {
        $this->info('Memulai konversi data indikator (Mode Aman)...');

        // Daftar kolom yang akan diubah. PASTIKAN INI SESUAI DENGAN $translatable DI MODEL ANDA.
        $translatableFields = [
            'name',
            'category',
            'keywords',
            'measurement_method',
            'scoring_criteria_text'
        ];

        $indicators = DB::table('indicators')->get();

        if ($indicators->isEmpty()) {
            $this->info('Tabel indikator kosong, tidak ada yang perlu dikonversi.');
            return 0;
        }

        $defaultLocale = config('app.locale', 'id');
        $convertedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($indicators as $indicator) {
                $updatePayload = [];
                $needsUpdate = false;

                foreach ($translatableFields as $field) {
                    $value = $indicator->{$field};

                    // Cek jika nilainya adalah string biasa dan bukan JSON
                    if (is_string($value) && json_decode($value) === null) {
                        $this->line("  -> Mengkonversi field '{$field}' untuk Indikator ID: {$indicator->id}");
                        $updatePayload[$field] = json_encode([$defaultLocale => $value]);
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    DB::table('indicators')->where('id', $indicator->id)->update($updatePayload);
                    $convertedCount++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan saat konversi: ' . $e->getMessage());
            return 1;
        }

        if ($convertedCount > 0) {
            $this->info("Selesai! {$convertedCount} record indikator telah berhasil dikonversi.");
        } else {
            $this->info('Selesai! Tidak ada data yang perlu dikonversi (semua sudah dalam format JSON).');
        }

        return 0;
    }
}