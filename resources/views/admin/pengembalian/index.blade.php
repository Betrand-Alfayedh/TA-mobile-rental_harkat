@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="text-2xl font-bold mb-6">Daftar Booking</h1>

        @if (session('success'))
            <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 rounded-md shadow">
                {{ session('success') }}
            </div>
        @endif
        
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2">Booking ID</th>
                    <th class="border px-4 py-2">Customer</th>
                    <th class="border px-4 py-2">Mobil</th>
                    <th class="border px-4 py-2">Tanggal Kembali (seharusnya)</th>
                    <th class="border px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pelunasan as $p)
                    <tr>
                        <td class="border px-4 py-2">{{ $p->booking_id }}</td>
                        <td class="border px-4 py-2">{{ $p->booking->user->name }}</td>
                        <td>
                            @foreach ($p->booking->details as $detail)
                                {{ $detail->mobil->nama_mobil }} <br>
                            @endforeach
                        </td>
                        <td class="border px-4 py-2">
                            @foreach ($p->booking->details as $detail)
                                {{ $detail->tanggal_selesai_format }} <br>
                            @endforeach
                        </td>

                        <td class="border px-4 py-2">
                            <a href="{{ route('admin.pengembalian.show', $p->id) }}"
                                class="px-3 py-1 bg-blue-500 text-white rounded-lg">Proses</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
