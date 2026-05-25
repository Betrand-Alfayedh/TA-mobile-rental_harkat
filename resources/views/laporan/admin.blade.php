<!DOCTYPE html>
<html>

<head>
    <title>Laporan Penyewaan Mobil</title>
    <style>
        @page {
            margin: 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 40px;
        }

        h1,
        h2,
        h3 {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .periode {
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
            /* biar header tabel muncul di halaman berikutnya */
        }

        .ringkasan {
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .footer {
            position: fixed;
            bottom: 40px;
            right: 40px;
            text-align: right;
        }

        .footer p {
            margin: 3px 0;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <h1>Harkat RentCar</h1>
    <h2>Laporan Penyewaan Mobil</h2>
    <p class="periode">Periode: {{ $periode ?? \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>

    <!-- Ringkasan -->
    <div class="ringkasan">
        <p><strong>Jumlah Booking:</strong> {{ $jumlahBooking }}</p>
        <p><strong>Jumlah Mobil Dibooking:</strong> {{ $jumlahMobil }}</p>
        <p><strong>Total Pendapatan:</strong> Rp{{ number_format($totalPendapatan, 0, ',', '.') }}</p>
        <p><strong>Rata-rata Lama Sewa:</strong> {{ $rataRataHari }} hari</p>
    </div>

    <!-- Tabel -->
    <h3>Mobil Paling Sering Dibooking</h3>
    <table>
        <thead>
            <tr>
                <th>Mobil</th>
                <th>Jumlah Booking</th>
                <th>Total Lama Sewa (hari)</th>
                <th>Total Pendapatan</th>
                <th>Kontribusi (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($mobilTerpopuler as $item)
                <tr>
                    <td>{{ $item->mobil->nama_mobil ?? 'Unknown' }}</td>
                    <td>{{ $item->jumlah }}</td>
                    <td>{{ $item->total_hari }}</td>
                    <td>Rp{{ number_format($item->total_pendapatan, 0, ',', '.') }}</td>
                    <td>{{ $item->kontribusi }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>



    <!-- Footer tanda tangan -->
    <div class="footer">
        <p>Yogyakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <p>Mengetahui,</p>
        <br><br> <!-- ruang kosong buat tanda tangan -->
        <p style="font-weight:bold;">Reza Mustaqim</p>
    </div>
</body>

</html>
