<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as LaravelNotification;
use App\Models\Assignment;
use App\Filament\Assessor\Resources\MyAssignmentResource;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class RevisionRequestNotification extends LaravelNotification
{
    use Queueable;

    public function __construct(public Assignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Kirim ke database untuk ikon lonceng
    }

    public function toDatabase(object $notifiable): array
    {
        // $url = MyAssignmentResource::getUrl('edit', ['record' => $this->assignment->id]);
        $url = route('filament.assessor.resources.my-assignments.edit', ['record' => $this->assignment->id]);

        return FilamentNotification::make()
            ->title(__('Permintaan Revisi Penilaian'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger') // Warna merah untuk menandakan butuh perhatian
            ->body(__("Admin meminta revisi untuk penilaian lokasi: {$this->assignment->location->name}."))
            ->actions([
                Action::make('view_revision')
                    ->label(__('Lihat & Kerjakan Revisi'))
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
