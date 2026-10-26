<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandCommand;

class CancelUnpaidBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:check-dp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek jatuh tempo DP booking dan auto cancel jika telat';

    /**
     * Execute the console command.
     */
    public function handle(BookingService $service)
    {
        $now = Carbon::now();

        $bookings = Booking::where('status', Booking::STATUS_BOOKED)
            ->whereHas('pembayaranDp', function ($q) use ($now) {
                $q->where('status_pembayaran', 0) // belum bayar
                    ->where('jatuh_tempo', '<', $now); // lewat tempo
            })
            ->get();
        $this->info("Found {$bookings->count()} bookings to cancel.");

        foreach ($bookings as $booking) {
            try {
                $service->cancel($booking, 'Tidak membayar DP sebelum jatuh tempo.');
                $this->info("❌ Booking #{$booking->id} expired & canceled.");
            } catch (\Throwable $e) {
                $this->error("Booking #{$booking->id} gagal dibatalkan: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
