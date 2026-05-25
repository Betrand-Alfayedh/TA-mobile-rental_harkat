<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCanceledNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $booking)
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
            ->subject('Booking Dibatalkan - Harkat RentCar')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Booking kamu dengan ID #' . $this->booking->id . ' dibatalkan.')
            ->line('Alasan: Tidak membayar DP sebelum jatuh tempo.')
            ->line('Jika masih ingin sewa mobil, silakan buat booking baru.')
            ->action('Booking Lagi', url('/booking'))
            ->line('Terima kasih sudah menggunakan layanan kami.');
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
