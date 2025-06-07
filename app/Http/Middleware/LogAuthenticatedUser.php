<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogAuthenticatedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya jalankan jika kita sedang dalam mode debug untuk tidak memenuhi log di produksi
        if (config('app.debug')) {
            Log::info('--- AUTHENTICATION CHECK ---');
            Log::info('Request Path: ' . $request->path());
            Log::info('Is Livewire Update Request: ' . ($request->hasHeader('X-Livewire') ? 'Yes' : 'No'));

            // Cek guard 'assessor'
            $assessorUser = Auth::guard('assessor')->user();
            if ($assessorUser) {
                Log::info('Guard [assessor] AUTHENTICATED as: ID ' . $assessorUser->id . ' - ' . $assessorUser->email);
            } else {
                Log::warning('Guard [assessor] NOT AUTHENTICATED.');
            }

            // Cek guard 'web' (untuk admin)
            $adminUser = Auth::guard('web')->user();
            if ($adminUser) {
                Log::info('Guard [web] AUTHENTICATED as: ID ' . $adminUser->id . ' - ' . $adminUser->email);
            } else {
                Log::warning('Guard [web] NOT AUTHENTICATED.');
            }
            
            Log::info('----------------------------');
        }
        
        return $next($request);
    }
}