<?php

namespace Tests\Feature;

use App\Models\Mobil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 6: pengerasan API yang ditemukan saat pengujian (BUGS: #19, #33, per_page). */
class ApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_per_page_is_clamped_between_1_and_50(): void
    {
        Mobil::factory()->count(3)->create(['status' => 1, 'status_approval' => 1]);

        $this->getJson('/api/mobils?per_page=100000')->assertOk()->assertJsonPath('meta.per_page', 50);
        $this->getJson('/api/mobils?per_page=0')->assertOk()->assertJsonPath('meta.per_page', 1);
        $this->getJson('/api/mobils?per_page=-5')->assertOk()->assertJsonPath('meta.per_page', 1);
        $this->getJson('/api/mobils?per_page=2')->assertOk()->assertJsonPath('meta.per_page', 2)->assertJsonCount(2, 'data');
        $this->getJson('/api/mobils')->assertOk()->assertJsonPath('meta.per_page', 10);
    }

    public function test_changing_password_revokes_other_sessions_but_keeps_the_current_one(): void
    {
        $user = User::factory()->create(['role' => 4]);
        $dipakai = $user->createToken('hp-ini')->plainTextToken;
        $user->createToken('hp-lain');
        $this->assertSame(2, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer ' . $dipakai)
            ->putJson('/api/me/password', ['current_password' => 'password', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertOk();

        $this->assertSame(1, $user->tokens()->count(), 'sesi di perangkat lain harus dicabut');
        $this->withHeader('Authorization', 'Bearer ' . $dipakai)->getJson('/api/me')->assertOk();
    }

    public function test_wrong_current_password_changes_nothing_and_keeps_all_sessions(): void
    {
        $user = User::factory()->create(['role' => 4]);
        $dipakai = $user->createToken('hp-ini')->plainTextToken;
        $user->createToken('hp-lain');

        $this->withHeader('Authorization', 'Bearer ' . $dipakai)
            ->putJson('/api/me/password', ['current_password' => 'salah', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertStatus(422);

        $this->assertSame(2, $user->tokens()->count());
    }

    public function test_api_without_token_is_401_json_never_a_redirect(): void
    {
        foreach (['/api/me', '/api/bookings', '/api/admin/dashboard', '/api/sopir/dashboard'] as $url) {
            // tanpa header Accept: tetap JSON 401, bukan redirect 302 ke halaman login
            $this->get($url)->assertStatus(401)->assertHeader('Content-Type', 'application/json');
        }
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 4]);
        $token = $user->createToken('lama', ['customer'], now()->subMinute())->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_validation_rejects_unknown_email_bad_format_and_empty_fields(): void
    {
        $this->postJson('/api/login', ['email' => 'tidak-ada@example.test', 'password' => 'apapun123'])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Email atau password salah.');
        $this->postJson('/api/login', ['email' => 'bukan-email', 'password' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->postJson('/api/login', ['email' => 'a@b.test'])->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->postJson('/api/login', [])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_register_rejects_duplicate_email_short_password_and_mismatch(): void
    {
        User::factory()->create(['email' => 'sudah@ada.test']);
        $baik = ['name' => 'Budi', 'email' => 'baru@ada.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];

        $this->postJson('/api/register', ['email' => 'sudah@ada.test'] + $baik)->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->postJson('/api/register', ['password' => 'pendek', 'password_confirmation' => 'pendek'] + $baik)->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->postJson('/api/register', ['password_confirmation' => 'beda-sekali'] + $baik)->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->postJson('/api/register', ['name' => ''] + $baik)->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->assertSame(0, User::where('email', 'baru@ada.test')->count());

        $this->postJson('/api/register', $baik)->assertCreated();
    }
}
