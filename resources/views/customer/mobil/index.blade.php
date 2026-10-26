@extends('layouts.landing')

@section('title', 'Armada | Harkat Rent Car')

@section('content')
    <div class="pt-24">
        <div class="reveal max-w-2xl">
            <h1 class="text-[clamp(2.2rem,5vw,3.4rem)] font-extrabold leading-[1.05] tracking-[-0.03em] text-forest">
                Armada Harkat
            </h1>
            <p class="mt-4 text-[17px] leading-relaxed text-ink/65">
                Cari berdasarkan nama mobil, atau saring menurut tipe. Harga di bawah adalah tarif per hari.
            </p>
        </div>

        <form id="filterForm" method="GET" action="{{ route('mobil.index') }}"
            class="reveal mt-10 flex flex-col gap-4 lg:flex-row lg:items-center" style="--d: 100ms">
            <div class="relative w-full lg:max-w-sm">
                <x-line-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink/35" />
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                    placeholder="Cari nama mobil" autocomplete="off" class="field !py-3 pl-12">
            </div>

            <input type="hidden" name="type" id="typeInput" value="{{ request('type') }}">
            <div id="typeChips" class="no-scrollbar flex gap-2 overflow-x-auto" role="group" aria-label="Filter tipe">
                @foreach (array_merge([['', 'Semua']], $tipeMobils->map(fn($t) => [$t->nama_tipe, $t->nama_tipe])->all()) as [$value, $label])
                    @php $on = (string) request('type') === (string) $value; @endphp
                    <button type="button" data-type="{{ $value }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                        class="type-chip shrink-0 rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-500 ease-spring {{ $on ? 'bg-forest text-white' : 'bg-white text-ink/70 ring-1 ring-forest/10 hover:text-forest' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </form>

        <div id="mobilList" class="mt-10">
            @include('customer.mobil.partials.list-mobil', ['mobils' => $mobils])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('filterForm');
            const list = document.getElementById('mobilList');
            const typeInput = document.getElementById('typeInput');
            let timer;

            async function load() {
                const qs = new URLSearchParams(new FormData(form)).toString();
                list.style.opacity = '.5';
                try {
                    const res = await fetch(form.action + '?' + qs, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    list.innerHTML = await res.text();
                    history.replaceState(null, '', qs ? '?' + qs : form.action);
                } finally {
                    list.style.opacity = '';
                }
            }

            // Cari langsung saat mengetik (jeda 300 ms)
            document.getElementById('searchInput').addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(load, 300);
            });
            form.addEventListener('submit', e => {
                e.preventDefault();
                load();
            });

            document.querySelectorAll('.type-chip').forEach(chip => chip.addEventListener('click', () => {
                typeInput.value = chip.dataset.type;
                document.querySelectorAll('.type-chip').forEach(c => {
                    const on = c === chip;
                    c.setAttribute('aria-pressed', on);
                    c.classList.toggle('bg-forest', on);
                    c.classList.toggle('text-white', on);
                    c.classList.toggle('bg-white', !on);
                    c.classList.toggle('text-ink/70', !on);
                    c.classList.toggle('ring-1', !on);
                });
                load();
            }));
        })();
    </script>
@endpush
