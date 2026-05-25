<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    @forelse ($mobils as $mobil)
        <div
            class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition duration-300 flex flex-col">

            <!-- Gambar Mobil + Badge -->
            <div class="relative bg-gray-50 flex items-center justify-center h-48">
                <img src="{{ asset('public/gambar_mobil/' . $mobil->gambar) }}" alt="{{ $mobil->nama_mobil }}"
                    class="object-contain max-h-full p-3">

                <!-- Badge tipe mobil -->
                @if ($mobil->masterMobil->tipe)
                    <span
                        class="absolute top-3 left-3 bg-green-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">
                        {{ $mobil->masterMobil->tipe->nama_tipe }}
                    </span>
                @endif

                <!-- Badge status -->
                @if ($mobil->status === \App\Models\Mobil::STATUS_MAINTENANCE)
                    <span class="absolute top-3 right-3 bg-yellow-500 text-white text-xs px-3 py-1 rounded-full shadow">
                        {{ $mobil->status_text }}
                    </span>
                @elseif($mobil->status === \App\Models\Mobil::STATUS_RUSAK)
                    <span class="absolute top-3 right-3 bg-red-500 text-white text-xs px-3 py-1 rounded-full shadow">
                        {{ $mobil->status_text }}
                    </span>
                @else
                    <span class="absolute top-3 right-3 bg-emerald-500 text-white text-xs px-3 py-1 rounded-full shadow">
                        {{ $mobil->status_text }}
                    </span>
                @endif
            </div>

            <!-- Info Mobil -->
            <div class="p-4 flex flex-col flex-1">
                <h2 class="text-lg font-bold text-gray-800 mb-1">{{ $mobil->masterMobil->nama }}</h2>
                <p class="text-sm text-gray-500 mb-3">{{ $mobil->merk }}</p>

                <!-- Harga -->
                <div class="mb-4">
                    <p class="text-green-600 font-extrabold text-xl">
                        Rp {{ number_format($mobil->harga_sewa, 0, ',', '.') }}
                        <span class="text-sm font-medium">/hari</span>
                    </p>
                    <p class="text-gray-500 text-sm mt-1">
                        Dengan supir: <span class="font-semibold text-green-600">
                            Rp {{ number_format($mobil->harga_all_in, 0, ',', '.') }}/hari
                        </span>
                    </p>
                </div>

                <!-- Tombol Booking -->
                @if ($mobil->status === \App\Models\Mobil::STATUS_MAINTENANCE)
                    <button disabled
                        class="mt-auto w-full bg-gray-400 text-white px-5 py-2 rounded-lg text-sm font-medium cursor-not-allowed">
                        Tersedia dalam 2-4 jam lagi
                    </button>
                @elseif(Auth::check() && Auth::user()->isRole('Customer'))
                    <a href="{{ route('booking.create', ['mobil_id' => $mobil->id]) }}"
                        class="mt-auto w-full bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-600 transition text-center">
                        Booking Sekarang
                    </a>
                @else
                    <a href="#" id="openCustomerLoginModalMobile"
                        class="mt-auto w-full bg-emerald-500 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-600 transition text-center">
                        Booking Sekarang
                    </a>
                @endif
            </div>
        </div>
    @empty
        <p class="col-span-4 text-center text-gray-500">Mobil tidak ditemukan.</p>
    @endforelse
</div>

<!-- Pagination -->
<div class="mt-8">
    {{ $mobils->links() }}
</div>
