<x-filament-panels::page>
    <form wire:submit="save">
        {{-- Ini akan secara otomatis merender semua form field yang Anda definisikan di file PHP --}}
        {{ $this->form }}

        {{-- Ini akan menampilkan tombol "Simpan Perubahan" --}}
        <x-filament-panels::form.actions 
            :actions="$this->getFormActions()"
        />
    </form>
</x-filament-panels::page>