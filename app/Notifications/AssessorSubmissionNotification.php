<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as LaravelNotification;
use App\Models\Assignment;
use App\Filament\Resources\AssignmentResource; // Resource di panel Admin
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class AssessorSubmissionNotification extends LaravelNotification
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Kirim ke database untuk ikon lonceng admin
    }

    public function toDatabase(object $notifiable): array
    {
        // URL akan mengarah ke halaman review kustom yang sudah kita buat
        // Di dalam metode toDatabase() pada AssessorSubmissionNotification.php

        $url = route('filament.administrator.resources.assignments.review', ['record' => $this->assignment->id]);
        $assessorName = $this->assignment->assessor->name;
        $locationName = $this->assignment->location->name;

        return FilamentNotification::make()
            ->title(__('Penilaian Baru Siap Direview'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('info')
            ->body(__("Asesor '{$assessorName}' telah mengirimkan hasil penilaian untuk lokasi '{$locationName}'."))
            ->actions([
                Action::make('review')
                    ->label(__('Review Sekarang'))
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}