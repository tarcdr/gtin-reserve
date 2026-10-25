<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpgradeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function employee(bool $active = true): User
    {
        return User::create([
            'user_login' => 'upgrade.employee',
            'employee_name' => 'Upgrade Employee',
            'password' => 'password',
            'role' => 'user',
            'is_active' => $active,
        ]);
    }

    public function test_employee_can_login_and_logout_with_string_primary_key(): void
    {
        $user = $this->employee();
        $this->post('/login', ['user_login' => $user->user_login, 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_and_non_string_credentials_are_rejected(): void
    {
        $user = $this->employee();
        foreach (['wrong-password', ['password']] as $password) {
            $this->post('/login', ['user_login' => $user->user_login, 'password' => $password])
                ->assertSessionHasErrors();
            $this->assertGuest();
        }
    }

    public function test_inactive_employee_is_rejected(): void
    {
        $user = $this->employee(false);
        $this->post('/login', ['user_login' => $user->user_login, 'password' => 'password'])
            ->assertSessionHasErrors('user_login');
        $this->assertGuest();
    }

    public function test_login_rehashes_password_using_employee_primary_key(): void
    {
        $user = $this->employee();
        $original = $user->password;
        config(['hashing.bcrypt.rounds' => 5]);
        app('hash')->forgetDrivers();
        $this->post('/login', ['user_login' => $user->user_login, 'password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($original, $user->fresh()->password);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
