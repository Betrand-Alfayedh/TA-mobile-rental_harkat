@props(['url', 'aktif', 'nama'])

{{-- Data tidak dihapus; hanya dinonaktifkan atau diaktifkan kembali. --}}
<form method="POST" action="{{ $url }}" class="inline"
    onsubmit='return confirm(@json(($aktif ? "Nonaktifkan " : "Aktifkan kembali ") . $nama . "?"))'>
    @csrf
    @method('PATCH')
    <button type="submit" title="{{ $aktif ? 'Nonaktifkan' : 'Aktifkan kembali' }}"
        class="text-xs font-medium hover:underline {{ $aktif ? 'text-red-600' : 'text-hk-ink' }}">
        {{ $aktif ? 'Nonaktifkan' : 'Aktifkan' }}
    </button>
</form>
