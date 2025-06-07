<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as LaravelNotification;
use App\Models\Assignment;
use App\Filament\Assessor\Resources\MyAssignmentResource; // Untuk link ke riwayat
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class AssessmentApprovedNotification extends LaravelNotification
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Kirim ke ikon lonceng di panel asesor
    }

    public function toDatabase(object $notifiable): array
    {
        // Karena tugas sudah selesai, kita bisa arahkan ke halaman riwayat
        // Asumsi Anda sudah membuat AssignmentHistoryResource seperti diskusi sebelumnya
        $url = route('filament.assessor.resources.my-assignments.edit', ['record' => $this->assignment->id]);

        return FilamentNotification::make()
            ->title('Penilaian Anda Telah Disetujui!')
            ->icon('heroicon-o-check-circle')
            ->color('success') // Warna hijau untuk menandakan keberhasilan
            ->body("Kerja bagus! Penilaian Anda untuk lokasi {$this->assignment->location->name} telah disetujui oleh admin.")
            ->actions([
                Action::make('view_history')
                    ->label('Lihat Riwayat Tugas')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}