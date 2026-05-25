@component('mail::message')
    # Invoice Pembayaran

    Halo, {{ $booking->user->name }}

    Terima kasih sudah melakukan pembayaran. Berikut detail booking kamu:

    @foreach ($booking->details as $detail)
        - Mobil: **{{ $detail->mobil->nama_mobil }}**
        - Tanggal Sewa: **{{ $detail->tanggal_sewa }}**
        - Tanggal Kembali: **{{ $detail->tanggal_kembali }}**
        - Lama Sewa: **{{ $detail->lama_sewa }} hari**
    @endforeach

    **Total Harga Booking:** Rp {{ number_format($booking->total_harga, 0, ',', '.') }}
    **Uang Muka:** Rp {{ number_format($booking->uang_muka, 0, ',', '.') }}

    **Jenis Pembayaran:** {{ $pembayaran->jenis_text }}
    **Jumlah Dibayar:** Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}

    Invoice dalam bentuk PDF sudah terlampir di email ini.

    @component('mail::button', ['url' => route('customer.bookings.show', $booking->id)])
        Lihat Detail Booking
    @endcomponent

    Terima kasih,
    **Harkat RentCar**
@endcomponent
