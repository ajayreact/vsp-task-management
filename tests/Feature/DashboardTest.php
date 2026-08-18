<?php

namespace Tests\Feature;

use App\Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')->has('snapshot.kpis'));
    }
}
