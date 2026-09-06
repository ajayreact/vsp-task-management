<?php

namespace Tests\Feature\Auth;

use App\Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Core/auth/login')
                ->has('superAdminPinLogin.enabled')
                ->has('superAdminPinLogin.pinLength'));
    }

    public function test_guests_see_the_login_page_at_the_root()
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Core/auth/login'));
    }

    public function test_authenticated_users_are_redirected_from_the_root()
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_authenticated_users_are_redirected_from_the_login_page()
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
