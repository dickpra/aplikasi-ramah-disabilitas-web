@php
    $admin = auth()->guard('admin')->user();
@endphp

<x-filament::widget>
    <x-filament::card>
        <h2 class="text-2xl font-bold">
            Selamat Datang, {{ $admin->name ?? 'Admin' }}!
        </h2>
        <p class="text-gray-500">Semoga harimu menyenangkan 😊</p>
    </x-filament::card>
</x-filament::widget>
