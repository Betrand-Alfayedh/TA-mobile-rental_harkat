<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_open_or_pay_someone_elses_booking(): void
    {
        [$owner, $other] = User::factory()->count(2)->create(['role' => 4]);
        $booking = Booking::create(['user_id' => $owner->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1, 'status' => 1, 'total_harga' => 1, 'uang_muka' => 1]);
        Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 1, 'jumlah' => 1, 'jatuh_tempo' => now()->addDay()]);

        $this->actingAs($other)->get("/pembayaran/{$booking->id}")->assertNotFound();
        $this->actingAs($other)->post("/pembayaran/{$booking->id}")->assertNotFound();
        $this->actingAs($owner)->get("/pembayaran/{$booking->id}")->assertOk();
    }

    public function test_superadmin_account_and_users_with_bookings_cannot_be_deleted(): void
    {
        $sa       = User::factory()->create(['role' => 1]);
        $customer = User::factory()->create(['role' => 4]);
        Booking::create(['user_id' => $customer->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1, 'status' => 1, 'total_harga' => 1, 'uang_muka' => 1]);

        $this->actingAs($sa)->delete(route('superadmin.users.destroy', $sa))->assertForbidden();
        $this->actingAs($sa)->delete(route('superadmin.users.destroy', $customer))->assertSessionHas('error');
        $this->assertModelExists($sa);
        $this->assertModelExists($customer);
    }

    public function test_api_login_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson('/api/login', ['email' => 'x@x.com', 'password' => 'salah'])->assertStatus(429);
    }
}
