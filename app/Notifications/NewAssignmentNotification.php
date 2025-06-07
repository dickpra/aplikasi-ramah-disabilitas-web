<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as LaravelNotification;
use App\Models\Assignment;
use App\Filament\Assessor\Resources\MyAssignmentResource;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action; // <-- PERBAIKI IMPORT INI

class NewAssignmentNotification extends LaravelNotification
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // --- MEMPERBAIKI MASALAH ROUTE ---
        // Gunakan route() helper untuk secara eksplisit menunjuk ke rute di panel 'assessor'
        $url = route('filament.assessor.resources.my-assignments.edit', ['record' => $this->assignment->id]);

        return FilamentNotification::make()
            ->title('Tugas Penilaian Baru Diterima')
            ->icon('heroicon-o-clipboard-document-list')
            ->body("Anda telah ditugaskan untuk menilai lokasi: {$this->assignment->location->name}.")
            ->actions([
                // Gunakan Action yang sudah di-import dengan benar
                Action::make('view')
                    ->label('Lihat Tugas')
                    ->url($url)
                    // ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}