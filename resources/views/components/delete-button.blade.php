@props([
    'id', // ID unik untuk modal (biasanya pakai ID item)
    'route', // route buat hapus
    'item', // nama item buat ditampilin
])

{{-- Tombol delete --}}
<button 
    @click="$dispatch('open-modal', 'delete-{{ $id }}')" 
    class="text-red-600 hover:text-indigo-900 font-medium"
    title="Hapus"
>
    <i data-lucide="trash-2" class="w-5 h-5"></i>
</button>

{{-- Modal konfirmasi --}}
<x-modal name="delete-{{ $id }}">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900">
            Yakin ingin menghapus <span class="font-bold">{{ $item }}</span>?
        </h2>

        <p class="mt-2 text-sm text-gray-600">
            Tindakan ini tidak dapat dibatalkan.
        </p>

        <div class="mt-6 flex justify-end gap-3">
            <button 
                type="button" 
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400"
                @click="$dispatch('close-modal', 'delete-{{ $id }}')"
            >
                Batal
            </button>

            <form action="{{ $route }}" method="POST">
                @csrf
                @method('DELETE')
                <button 
                    type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                >
                    Hapus
                </button>
            </form>
        </div>
    </div>
</x-modal>
