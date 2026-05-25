<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobAssignedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingDetail;
    /**
     * Create a new notification instance.
     */
    public function __construct($bookingDetail)
    {
        $this->bookingDetail = $bookingDetail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mobil = $this->bookingDetail->mobil;
        $tanggalMulai = $this->bookingDetail->tanggal_mulai_format; // field di booking_details
        $tanggalSelesai = $this->bookingDetail->tanggal_selesai_format;
        return (new MailMessage)
            ->subject('Job Baru Tersedia 🚗')
            ->greeting("Halo {$notifiable->name},")
            ->line("Ada booking baru yang membutuhkan supir.")
            ->line("Mobil: {$mobil->nama_mobil}")
            ->line("Tanggal Sewa: {$tanggalMulai} s/d {$tanggalSelesai}")
            ->action('Terima Job', route('supir.acceptJob')) // link ke halaman supir
            ->line('Segera cek job dan terima jika tersedia.');
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
