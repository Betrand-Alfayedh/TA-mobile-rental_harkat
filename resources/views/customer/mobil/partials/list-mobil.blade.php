@php
    $isCustomer = auth('web')->check() && auth('web')->user()->isRole('Customer');
    $rp = fn($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp

<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse ($mobils as $mobil)
        <article class="bezel">
            <div class="bezel-core flex h-full flex-col overflow-hidden">
                <div class="relative">
                    <x-car-image :file="$mobil->gambar" :alt="$mobil->masterMobil->nama" class="h-44" />

                    @if ($mobil->masterMobil->tipe)
                        <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-forest shadow-sm">
                            {{ $mobil->masterMobil->tipe->nama_tipe }}
                        </span>
                    @endif

                    @if ($mobil->status === \App\Models\Mobil::STATUS_MAINTENANCE)
                        <span class="absolute right-3 top-3 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                            {{ $mobil->status_text }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <h2 class="text-lg font-bold tracking-tight text-forest">{{ $mobil->masterMobil->nama }}</h2>
                    <p class="mt-0.5 text-sm text-ink/55">{{ $mobil->merk }} &middot; {{ $mobil->tahun }}</p>

                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="text-ink/55">Lepas kunci</dt>
                            <dd class="font-bold text-ink">{{ $rp($mobil->harga_sewa) }}<span class="font-medium text-ink/50"> /hari</span></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <dt class="text-ink/55">Dengan supir</dt>
                            <dd class="font-bold text-ink">{{ $rp($mobil->harga_all_in) }}<span class="font-medium text-ink/50"> /hari</span></dd>
                        </div>
                    </dl>

                    <div class="mt-auto pt-5">
                        @if ($mobil->status === \App\Models\Mobil::STATUS_MAINTENANCE)
                            <button disabled class="w-full cursor-not-allowed rounded-full bg-ink/5 px-5 py-3 text-sm font-semibold text-ink/40">
                                Tersedia dalam 2-4 jam lagi
                            </button>
                        @elseif ($isCustomer)
                            <a href="{{ route('booking.create', ['mobil_id' => $mobil->id]) }}" class="btn btn-primary w-full justify-between">
                                Booking <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                            </a>
                        @else
                            <a href="#" data-open-login class="btn btn-primary w-full justify-between"
                                onclick="event.preventDefault(); openModal(loginModal)">
                                Booking <span class="btn-icon"><x-line-icon name="arrow-up-right" class="h-4 w-4" /></span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="col-span-full">
            <div class="bezel">
                <div class="bezel-core px-8 py-16 text-center">
                    <x-line-icon name="search" class="mx-auto h-10 w-10 text-forest/30" />
                    <p class="mt-4 font-semibold text-forest">Mobil tidak ditemukan</p>
                    <p class="mt-1 text-ink/60">Coba kata kunci lain atau pilih tipe &quot;Semua&quot;.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-10">
    {{ $mobils->links() }}
</div>
