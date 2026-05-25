<?php

namespace App\Events;

use App\Models\JobOffer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $jobOffer;

    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer->load('booking_detail'); // biar ada info booking
    }

    public function broadcastOn()
    {
        // channel khusus supir yg dituju
        return new Channel('supir.' . $this->jobOffer->supir_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->jobOffer->id,
            'mobil' => $this->jobOffer->booking_detail->mobil->nama_mobil ?? 'Unknown',
            'tanggal_sewa' => $this->jobOffer->booking_detail->tanggal_sewa,
            'tanggal_kembali' => $this->jobOffer->booking_detail->tanggal_kembali,
        ];
    }
}
