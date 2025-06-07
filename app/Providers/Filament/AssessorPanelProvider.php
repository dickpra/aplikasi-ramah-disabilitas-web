<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Assessor\Pages\Dashboard;
use App\Http\Middleware\SetAssessorGuard; // <-- 1. IMPORT MIDDLEWARE BARU
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Support\Facades\Auth;  


class AssessorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Assessor Panel')
            ->id('assessor')
            ->path('assessor')
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Assessor/Resources'), for: 'App\\Filament\\Assessor\\Resources')
            ->discoverPages(in: app_path('Filament/Assessor/Pages'), for: 'App\\Filament\\Assessor\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                Dashboard::class,
            ])
            
            ->databaseNotifications()
            ->databaseNotificationsPolling('3s')
            ->discoverWidgets(in: app_path('Filament/Assessor/Widgets'), for: 'App\\Filament\\Assessor\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                \Illuminate\Session\Middleware\StartSession::class . ':assessor_session', // Nama cookie kustom
                EncryptCookies::class,
                \App\Http\Middleware\LogAuthenticatedUser::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('assessor')
            ->authMiddleware([
                Authenticate::class . ':assessor',
                SetAssessorGuard::class,
                // Authenticate::class,
            ]);
    }
}
