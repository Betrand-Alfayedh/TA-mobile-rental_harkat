<?php

namespace App\Jobs;

use App\Models\Mobil;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMobilStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $mobilId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $mobilId)
    {
        $this->mobilId = $mobilId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mobil = Mobil::find($this->mobilId);

        if ($mobil && $mobil->status === Mobil::STATUS_MAINTENANCE) {
            $mobil->update(['status' => Mobil::STATUS_TERSEDIA]);
        }
    }
}
