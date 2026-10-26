@props(['pakaiSupir', 'supir', 'detail'])

@if ($pakaiSupir == 0)
    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
        Tanpa Supir
    </span>
@elseif ($pakaiSupir == 2 || ($pakaiSupir == 1 && !$supir && $detail->menunggu_supir))
    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">
        🚕 Menunggu supir merespon...
    </span>
@elseif ($pakaiSupir == 1 && !$supir)
    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
        ✅ Pakai Supir (Belum Ditentukan)
    </span>
    <form action="{{ route('admin.assignJob', ['bookingDtl' => $detail->id]) }}" method="POST"
        onsubmit="return confirm('Kirim job ke semua supir available?')" class="inline-block mt-1">
        @csrf
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded-full">
            Assign Job
        </button>
    </form>
@elseif ($pakaiSupir == 1 && $detail->supir->user->name)
    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
        🧍 Supir: {{ $detail->supir->user->name }}
    </span>
@endif
