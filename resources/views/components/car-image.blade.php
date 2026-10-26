{{--
    Foto mobil, atau tampilan pengganti bila belum ada foto.
    <x-car-image :file="$mobil->gambar" :alt="$nama" class="h-48" />
    File disimpan admin di public/gambar_mobil (lihat Admin\MobilController).
--}}
@props(['file' => null, 'alt' => 'Mobil'])

<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden bg-mint']) }}>
    @if ($file)
        <img src="{{ asset('gambar_mobil/' . $file) }}" alt="{{ $alt }}" loading="lazy"
            class="relative h-full w-full object-contain p-4">
    @else
        <div class="flex flex-col items-center gap-2 text-forest/35">
            <x-line-icon name="car" class="h-16 w-16 stroke-[1.1]" />
            <span class="text-xs font-medium">Foto belum tersedia</span>
        </div>
    @endif
</div>
