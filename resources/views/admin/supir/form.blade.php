@php
    $isEdit = isset($supir);
    $foto = $isEdit && $supir->gambar ? asset('storage/' . $supir->gambar) : null;
    $inp = 'mt-1 block w-full px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400';
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
        <div class="mb-1 font-semibold">Data belum bisa disimpan:</div>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@csrf

<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Akun sopir</h3>
    <p class="mb-5 text-sm text-gray-500">Email dan password dipakai sopir untuk masuk ke aplikasi.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label for="name" class="text-sm font-medium text-gray-700">Nama lengkap</label>
            <input type="text" name="name" id="name" class="{{ $inp }}" placeholder="Contoh: Budi Santoso"
                value="{{ old('name', $isEdit ? $supir->user->name : '') }}" required>
        </div>

        <div>
            <label for="email" class="text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" class="{{ $inp }}" placeholder="nama@email.com"
                value="{{ old('email', $isEdit ? $supir->user->email : '') }}" required>
        </div>

        <div>
            <label for="password" class="text-sm font-medium text-gray-700">
                {{ $isEdit ? 'Password baru (opsional)' : 'Password' }}
            </label>
            <input type="password" name="password" id="password" class="{{ $inp }}"
                minlength="8" placeholder="{{ $isEdit ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter' }}"
                @if (!$isEdit) required @endif>
        </div>
    </div>
</section>

<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Kontak dan status</h3>
    <p class="mb-5 text-sm text-gray-500">Dipakai admin untuk menghubungi dan menugaskan sopir.</p>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label for="no_hp" class="text-sm font-medium text-gray-700">No. telepon</label>
            <input type="number" name="no_hp" id="no_hp" class="{{ $inp }}" placeholder="08xxxxxxxxxx"
                value="{{ old('no_hp', $isEdit ? $supir->user->no_hp : '') }}" required>
        </div>

        @if ($isEdit)
            @php $bertugas = (int) $supir->status === 2; @endphp
            <div>
                <label for="status" class="text-sm font-medium text-gray-700">Status</label>
                @if ($bertugas)
                    <input type="text" value="Sedang bertugas" disabled class="{{ $inp }} bg-hk-panel text-gray-500">
                    <p class="mt-1 text-xs text-gray-500">Status berubah otomatis setelah tugas selesai.</p>
                @else
                    <select name="status" id="status" class="{{ $inp }}">
                        <option value="1" @selected(old('status', $supir->status) == 1)>Siap bertugas</option>
                        <option value="0" @selected(old('status', $supir->status) == 0)>Tidak tersedia</option>
                    </select>
                @endif
            </div>

            <div class="md:col-span-2">
                <input type="hidden" name="aktif" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-hk-line bg-white px-4 py-3">
                    <input type="checkbox" name="aktif" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 accent-hk-ink"
                        @checked(old('aktif', $supir->user->aktif ?? true))>
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Akun aktif</span>
                        <span class="block text-xs text-gray-500">Sopir yang dinonaktifkan tidak bisa masuk ke aplikasi. Tidak bisa dinonaktifkan saat sedang bertugas.</span>
                    </span>
                </label>
            </div>
        @endif

        <div class="md:col-span-2">
            <label for="alamat" class="text-sm font-medium text-gray-700">Alamat</label>
            <textarea name="alamat" id="alamat" rows="3" class="{{ $inp }}" placeholder="Alamat lengkap" required>{{ old('alamat', $isEdit ? $supir->user->alamat : '') }}</textarea>
        </div>
    </div>
</section>

<section class="rounded-2xl border border-hk-line bg-white p-6 ">
    <h3 class="text-base font-bold text-gray-900">Foto sopir</h3>
    <p class="mb-5 text-sm text-gray-500">Format JPG, PNG, atau WebP, maksimal 8 MB.</p>

    <div class="flex items-center gap-5">
        <label for="gambar"
            class="flex h-32 w-32 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-hk-line bg-hk-panel text-center transition hover:border-hk-ink hover:bg-hk-panel">
            <img id="foto-preview" src="{{ $foto }}" alt="Foto sopir"
                class="{{ $foto ? '' : 'hidden' }} h-full w-full object-cover">
            <span id="foto-hint" class="{{ $foto ? 'hidden' : '' }} px-2 text-xs font-semibold text-hk-ink">Pilih foto</span>
        </label>
        <div class="text-sm text-gray-500">
            Klik kotak di samping untuk memilih foto.
            @if ($isEdit)
                <br>Biarkan kosong jika tidak ingin mengganti foto.
            @endif
        </div>
        <input type="file" name="gambar" id="gambar" accept="image/*" class="sr-only">
    </div>
</section>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.supir.index') }}"
        class="rounded-xl border border-hk-line bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
    <button type="submit"
        class="rounded-xl bg-hk-ink px-6 py-2.5 text-sm font-semibold text-white  hover:bg-black">
        {{ $isEdit ? 'Simpan perubahan' : 'Simpan sopir' }}
    </button>
</div>

<script>
    document.getElementById('gambar').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const p = document.getElementById('foto-preview');
            p.src = URL.createObjectURL(this.files[0]);
            p.classList.remove('hidden');
            document.getElementById('foto-hint').classList.add('hidden');
        }
    });
</script>
