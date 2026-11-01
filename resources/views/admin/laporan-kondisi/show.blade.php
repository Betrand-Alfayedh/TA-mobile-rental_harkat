@extends('layouts.admin')

@section('content')
    <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-1">Laporan Kondisi Mobil</h2>
        <p class="text-gray-600 mb-4">
            {{ $detail->mobil->masterMobil->nama ?? '-' }} ({{ $detail->mobil->plat_nomor ?? '-' }}) &middot;
            {{ $detail->booking->user->name ?? '-' }} &middot; {{ $detail->status_label }}
        </p>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 px-4 py-2 bg-red-100 text-red-800 rounded-md shadow">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 px-4 py-2 bg-red-100 text-red-800 rounded-md shadow">{{ $errors->first() }}</div>
        @endif

        <div class="grid md:grid-cols-2 gap-4">
            @foreach ([1 => 'Sebelum sewa', 2 => 'Sesudah sewa'] as $jenis => $judul)
                @php $l = $laporans[$jenis] ?? null; @endphp
                <form method="POST" action="{{ route('admin.laporan-kondisi.store', $detail->id) }}"
                    enctype="multipart/form-data" class="bg-white rounded-lg shadow p-5 space-y-3">
                    @csrf
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                    <h3 class="font-semibold">{{ $judul }}
                        @if ($l)
                            <span class="text-xs text-green-600">(sudah ada)</span>
                        @endif
                    </h3>
                    <select name="kondisi" class="w-full border rounded px-3 py-2" required>
                        @foreach (\App\Models\LaporanKondisi::KONDISI as $v => $t)
                            <option value="{{ $v }}" @selected(old('kondisi', $l?->kondisi) == $v)>{{ $t }}</option>
                        @endforeach
                    </select>
                    <textarea name="catatan" rows="3" maxlength="500" placeholder="Catatan (opsional)"
                        class="w-full border rounded px-3 py-2">{{ old('catatan', $l?->catatan) }}</textarea>
                    @if ($l?->foto)
                        <img src="{{ asset('storage/' . $l->foto) }}" alt="Foto kondisi" class="w-full rounded">
                    @endif
                    <input type="file" name="foto" accept="image/jpeg,image/png" class="text-sm">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">Simpan</button>
                </form>
            @endforeach
        </div>

        <a href="{{ route('admin.booking.index') }}" class="inline-block mt-6 text-blue-600 hover:underline">&larr; Kembali ke booking</a>
    </div>
@endsection
