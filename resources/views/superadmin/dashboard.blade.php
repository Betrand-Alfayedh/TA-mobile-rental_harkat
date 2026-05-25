@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('content')
    <x-card title="Ringkasan Sistem">
        <p>Selamat datang di panel Super Admin 🎉</p>
    </x-card>
    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-6">Dashboard Analisis Penyewaan Mobil</h1>

        <div class="bg-white p-6 rounded-lg shadow">
            <canvas id="bookingChart"></canvas>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('bookingChart').getContext('2d');
        const bookingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Jumlah Booking per Bulan',
                    data: @json($chartData),
                    backgroundColor: 'rgba(34,197,94,0.6)',
                    borderColor: 'rgba(34,197,94,1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
@endpush
