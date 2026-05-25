<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DpReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $booking, public $pembayaranDp)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Segera Bayar DP Booking - Harkat RentCar')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Terima kasih sudah melakukan booking dengan ID #' . $this->booking->id . '.')
            ->line('Untuk mengamankan booking kamu, segera lakukan pembayaran DP.')
            ->line('Batas waktu pembayaran DP: ' . $this->pembayaranDp->jatuh_tempo->format('d M Y H:i'))
            ->action('Bayar DP Sekarang', url('/customer/pembayaran/' . $this->pembayaranDp->id))
            ->line('Jika tidak membayar sebelum jatuh tempo, booking kamu otomatis dibatalkan.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
