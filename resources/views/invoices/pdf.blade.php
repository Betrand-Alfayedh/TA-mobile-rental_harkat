<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $pembayaran->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .summary {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Invoice Pembayaran</h2>
        <p><strong>No. Invoice:</strong> #{{ $pembayaran->id }}</p>
        <p><strong>Customer:</strong> {{ $booking->user->name }} ({{ $booking->user->email }})</p>
        <p><strong>Tanggal:</strong> {{ $pembayaran->tanggal_pembayaran_format }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Mobil</th>
                <th>Tanggal Sewa</th>
                <th>Tanggal Kembali</th>
                <th>Lama Sewa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($booking->details as $detail)
                <tr>
                    <td>{{ $detail->mobil->nama_mobil }}</td>
                    <td>{{ $detail->tanggal_mulai_format }}</td>
                    <td>{{ $detail->tanggal_selesai_format }}</td>
                    <td>{{ $detail->lama_sewa }} hari</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Uang Muka:</strong> Rp {{ number_format($booking->uang_muka, 0, ',', '.') }}</p>
        <p><strong>Total Harga Booking:</strong> Rp {{ number_format($booking->total_harga, 0, ',', '.') }}</p>
        <p><strong>Jenis Pembayaran:</strong> {{ $pembayaran->jenis_text }}</p>
        <p><strong>Jumlah Dibayar:</strong> Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</p>
    </div>
</body>

</html>
