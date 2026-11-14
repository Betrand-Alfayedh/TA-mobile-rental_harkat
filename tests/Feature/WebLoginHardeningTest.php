<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebLoginHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $u, string $role, string $pass = 'password')
    {
        return $this->post('/login', ['email' => $u->email, 'password' => $pass, 'role' => $role]);
    }

    public function test_deactivated_account_cannot_log_in_on_the_web(): void
    {
        $u = User::factory()->create(['role' => 3, 'aktif' => false]);
        $this->login($u, 'supir')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_wrong_role_and_wrong_password_give_the_same_message(): void
    {
        $u = User::factory()->create(['role' => 4]);
        $salahPeran = $this->login($u, 'admin')->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');
        $this->assertGuest();
        $salahSandi = $this->login($u, 'customer', 'salah-sekali')->getSession()->get('errors')->first('email');
        $this->assertSame($salahSandi, $salahPeran);
    }

    public function test_routes_without_a_controller_method_answer_405_not_500(): void
    {
        $admin = User::factory()->create(['role' => 2]);
        $this->actingAs($admin)->delete('/admin/mobil/1')->assertStatus(405);
        $this->actingAs($admin)->delete('/admin/pelanggan/1')->assertStatus(405);
        $this->delete('/mobil/1')->assertStatus(405);
        $this->post('/mobil')->assertStatus(405);
    }
}
