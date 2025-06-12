<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Models\Assignment; // Import Assignment
use App\Models\Location; // Import Location
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Untuk notifikasi
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // Untuk transaksi

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $locationId = $data['location_id'];
        $selectedAssessorIds = $data['assessor_ids']; // Ini array dari form
        $createdAssignments = [];
        $assignmentsAttempted = 0;
        $assignmentsSuccessfullyCreated = 0;

        // Validasi awal di level controller (meskipun sudah ada di form)
        $location = Location::findOrFail($locationId);
        $currentlyAssignedCount = $location->assignments()->count();

        if (($currentlyAssignedCount + count($selectedAssessorIds)) > 3 && count(array_unique($selectedAssessorIds)) > (3-$currentlyAssignedCount) ) {
             Notification::make()
                ->danger()
                ->title(__('Gagal Membuat Penugasan'))
                ->body(__('Jumlah total asesor yang dipilih akan melebihi batas maksimal 3 untuk lokasi ini.'))
                ->send();
            // Melemparkan exception akan menghentikan proses dan menampilkan error form standar jika validasi form tidak menangkapnya
            // throw ValidationException::withMessages(['assessor_ids' => 'Jumlah total asesor akan melebihi batas 3.']);
            // Namun, karena ini di handleRecordCreation, lebih baik return dummy atau handle redirect.
            // Untuk kesederhanaan, kita notifikasi dan return dummy.
            return new Assignment(); // Mengembalikan model kosong agar tidak error, tapi notifikasi sudah dikirim
        }


        DB::beginTransaction();
        try {
            foreach (array_unique($selectedAssessorIds) as $assessorId) {
                $assignmentsAttempted++;
                // Cek lagi sebelum insert per asesor untuk menghindari race condition atau duplikasi jika ada
                $currentTotalForLocation = Assignment::where('location_id', $locationId)->count();
                $alreadyExists = Assignment::where('location_id', $locationId)
                                        ->where('assessor_id', $assessorId)
                                        ->exists();

                if ($alreadyExists) {
                    // Bisa di-skip atau beri notifikasi bahwa asesor ini sudah ada
                    continue;
                }

                if ($currentTotalForLocation < 3) {
                    $assignment = Assignment::create([
                        'location_id' => $locationId,
                        'assessor_id' => $assessorId,
                        'assignment_date' => $data['assignment_date'] ?? now(),
                        'due_date' => $data['due_date'] ?? null,
                        'status' => $data['status'] ?? 'assigned',
                        'notes' => $data['notes'] ?? null,
                    ]);
                    $createdAssignments[] = $assignment;
                    $assignmentsSuccessfullyCreated++;
                } else {
                    // Batas sudah tercapai saat iterasi, hentikan
                    Notification::make()
                        ->warning()
                        ->title(__('Batas Asesor Tercapai'))
                        ->body(__("Beberapa asesor mungkin tidak dapat ditugaskan karena lokasi sudah mencapai batas maksimal 3 asesor."))
                        ->send();
                    break;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title(__('Error Penyimpanan'))
                ->body(__('Terjadi kesalahan saat menyimpan data penugasan: ' . $e->getMessage()))
                ->send();
            // Mengembalikan model kosong jika terjadi error
            return new Assignment();
        }

        if ($assignmentsSuccessfullyCreated > 0) {
             Notification::make()
                ->success()
                ->title(__('Penugasan Berhasil'))
                ->body(__("{$assignmentsSuccessfullyCreated} asesor berhasil ditugaskan."))
                ->send();
        } elseif ($assignmentsAttempted > 0) {
             Notification::make()
                ->info()
                ->title(__('Info Penugasan'))
                ->body(__('Tidak ada asesor baru yang ditugaskan. Mungkin semua yang dipilih sudah ada atau batas lokasi tercapai.'))
                ->send();
        }


        // handleRecordCreation harus mengembalikan sebuah instance Model.
        // Kita bisa kembalikan yang pertama dibuat, atau instance baru jika tidak ada yang dibuat.
        return $createdAssignments[0] ?? new Assignment();
    }

    // Opsional: Override redirect setelah create agar kembali ke halaman index, bukan edit.
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}