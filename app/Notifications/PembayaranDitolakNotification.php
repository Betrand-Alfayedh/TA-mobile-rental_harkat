<?php

namespace App\Notifications;

use App\Models\Pembayaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PembayaranDitolakNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pembayaran;

    public function __construct(Pembayaran $pembayaran)
    {
        $this->pembayaran = $pembayaran;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('❌ Pembayaran Anda Ditolak')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pembayaran untuk Booking #' . $this->pembayaran->booking_id . ' telah ditolak oleh admin.')
            ->line('Alasan: ' . ($this->pembayaran->catatan_admin ?? 'Tidak sesuai'))
            ->line('Silakan upload bukti pembayaran yang benar agar pesanan Anda bisa diproses.')
            ->action('Lihat Detail Booking', url(route('booking.index', $this->pembayaran->booking_id)))
            ->line('Terima kasih sudah menggunakan layanan kami 🙏');
    }
}
