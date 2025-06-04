<?php

namespace App\Filament\Resources\PenugasanResource\Pages;

use App\Filament\Resources\PenugasanResource;
use App\Models\Assignment;
use App\Models\Assessor;
use App\Models\City;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreatePenugasan extends CreateRecord
{
    
    protected static string $resource = PenugasanResource::class;

    // Menonaktifkan tombol "Create another" karena kita membuat batch
    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()->visible(false);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('city_id')
                ->label('Kota')
                ->relationship('city', 'name')
                // ->options(City::pluck('name', 'id')) // Atau gunakan ->relationship() jika lebih disukai
                ->searchable()
                ->required()
                ->live() // Menggunakan live() agar perubahan city_id langsung direspon
                ->afterStateUpdated(fn (Set $set) => $set('assessor_ids', [])), // Reset pilihan asesor jika kota diubah

            Select::make('assessor_ids')
                ->label('Pilih Asesor (Maksimal 3 Asesor Baru)')
                ->multiple() // Memungkinkan pemilihan lebih dari satu asesor
                ->options(function (Get $get): array {
                    $cityId = $get('city_id');
                    if (!$cityId) {
                        // Jika kota belum dipilih, tampilkan semua asesor atau kosongkan
                        // Lebih baik kosongkan agar pengguna memilih kota dulu
                        return [];
                        // Atau jika ingin menampilkan semua: return Assessor::pluck('name', 'id')->all();
                    }
                    // Ambil asesor yang BELUM ditugaskan ke kota yang dipilih
                    return Assessor::whereDoesntHave('assignments', function (Builder $query) use ($cityId) {
                        $query->where('city_id', $cityId);
                    })->pluck('name', 'id')->all();
                })
                ->maxItems(3) // Batasan UI untuk memilih maksimal 3 item baru
                ->searchable()
                ->required()
                ->helperText('Pilih asesor yang akan ditugaskan ke kota ini. Asesor yang sudah ditugaskan ke kota ini tidak akan muncul di pilihan.')
                ->rules([ // Aturan validasi custom untuk jumlah total
                    fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                        $cityId = $get('city_id');
                        $selectedAssessorIds = $value; // Ini adalah array ID asesor yang dipilih

                        if (!$cityId || !is_array($selectedAssessorIds) || empty($selectedAssessorIds)) {
                            // Jika kota belum dipilih atau tidak ada asesor dipilih, lewati validasi ini
                            return;
                        }

                        $existingAssignmentsCount = Assignment::where('city_id', $cityId)->count();
                        $newAssignmentsCount = count($selectedAssessorIds);

                        if (($existingAssignmentsCount + $newAssignmentsCount) > 3) {
                            $fail("Kota ini sudah memiliki {$existingAssignmentsCount} asesor. Anda mencoba menambahkan {$newAssignmentsCount} asesor baru. Total penugasan akan melebihi 3 asesor.");
                        }
                    },
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // dd($data);
        $cityId = $data['city_id'];
        $assessorIds = $data['assessor_id'];
        $assignmentDate = $data['assignment_date'];
        $description = $data['description'] ?? null; // Gunakan null jika tidak diisi dan field nullable
    
        // Validasi ulang (seharusnya sudah ditangani oleh 'rules' di form, tapi sebagai pengaman)
        $existingAssignmentsCount = Assignment::where('city_id', $cityId)->count();
        if (($existingAssignmentsCount + count($assessorIds)) > 3) {
            throw ValidationException::withMessages([
                'assessor_id' => "Gagal: Kota ini sudah memiliki {$existingAssignmentsCount} asesor. Maksimal total 3 asesor.",
            ]);
        }

        $createdAssignments = [];
        DB::transaction(function () use ($cityId, $assessorIds, $assignmentDate, $description, &$createdAssignments) {
            foreach ($assessorIds as $assessorId) {
                if (!Assignment::where('city_id', $cityId)->where('assessor_id', $assessorId)->exists()) {
                    $createdAssignments[] = Assignment::create([
                        'city_id' => $cityId,
                        'assessor_id' => $assessorId,
                        'assignment_date' => $assignmentDate, // Simpan tanggal
                        'description' => $description,     // Simpan keterangan
                    ]);
                }
            }
        });

        if (empty($createdAssignments)) {
             Notification::make()
                ->title('Tidak Ada Penugasan Baru')
                ->body('Semua asesor yang dipilih mungkin sudah ditugaskan sebelumnya atau tidak ada asesor yang dipilih.')
                ->warning()
                ->send();
            // Mengembalikan model dummy atau throw error agar flow tidak berlanjut ke notifikasi sukses standar.
            // Namun, karena kita menimpa getCreatedNotificationTitle dan getRedirectUrl, ini mungkin tidak terlalu masalah.
            // Kita tetap butuh mengembalikan satu model untuk memenuhi kontrak method.
            // Jika tidak ada yang dibuat, lebih baik throw exception.
             throw ValidationException::withMessages([
                'assessor_ids' => 'Tidak ada penugasan baru yang berhasil dibuat. Asesor mungkin sudah ada atau tidak valid.',
            ]);
        }

        // Mengembalikan record pertama yang dibuat agar method signature terpenuhi
        return $createdAssignments[0];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penugasan asesor berhasil disimpan';
    }

    // Arahkan ke halaman index setelah berhasil
    // Di dalam CreatePenugasan.php
protected function getRedirectUrl(): string
{
    return PenugasanResource::getUrl('index'); // Resource saat ini adalah PenugasanResource
}
}
