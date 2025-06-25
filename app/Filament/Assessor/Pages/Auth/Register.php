<?php

namespace App\Filament\Assessor\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse; // <-- DIPERBAIKI
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered;

class Register extends BaseRegister
{
    /**
     * Menimpa (override) seluruh proses registrasi untuk kontrol penuh.
     */
    public function register(): ?RegistrationResponse // <-- DIPERBAIKI
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();
        $user = $this->handleRegistration($data);
        event(new Registered($user));

        Notification::make()
            ->title(__('Registrasi Berhasil!'))
            ->body(__('Akun Anda telah dibuat dan sedang menunggu persetujuan dari Admin.'))
            ->success()
            ->persistent()
            ->send();
        
        // Kembalikan objek Response yang benar
        return app(RegistrationResponse::class); // <-- DIPERBAIKI
    }

    /**
     * Override method ini untuk memastikan tujuan redirect adalah halaman login.
     */
    protected function getRedirectUrl(): string
    {
        return filament()->getLoginUrl();
    }
}