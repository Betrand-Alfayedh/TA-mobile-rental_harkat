<?php

namespace App\Observers;

use App\Models\BookingDetail;

class BookingDetailObserver
{
    /**
     * Handle the BookingDetail "created" event.
     */
    public function created(BookingDetail $bookingDetail): void
    {
        //
    }

    /**
     * Handle the BookingDetail "updated" event.
     */
    public function saved(BookingDetail $detail): void
    {
        $booking = $detail->booking;

        if (!$booking) return;

        $details = $booking->details;

        // Semua canceled → booking canceled
        if ($details->every(fn($d) => $d->status_detail === 0)) {
            $booking->status = 0;
        }
        // Semua done → booking done
        elseif ($details->every(fn($d) => $d->status_detail === 3)) {
            $booking->status = 3;
        }
        // Ada yang ongoing → booking ongoing
        elseif ($details->contains(fn($d) => $d->status_detail === 2)) {
            $booking->status = 2;
        }
        // Default: booked
        else {
            $booking->status = 1;
        }

        $booking->saveQuietly(); // pake saveQuietly biar ga trigger event loop
    }

    /**
     * Handle the BookingDetail "deleted" event.
     */
    public function deleted(BookingDetail $bookingDetail): void
    {
        //
    }

    /**
     * Handle the BookingDetail "restored" event.
     */
    public function restored(BookingDetail $bookingDetail): void
    {
        //
    }

    /**
     * Handle the BookingDetail "force deleted" event.
     */
    public function forceDeleted(BookingDetail $bookingDetail): void
    {
        //
    }
}
