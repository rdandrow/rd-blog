<?php

/**
 * Authentication Test Suite
 *
 * Tests user authentication functionality including login, logout,
 * and authentication state management.
 */

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

describe('Login', function () {
    it('renders login screen', function () {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    })->group('auth', 'login', 'guest');

    it('allows users to authenticate with valid credentials', function () {
        $user = User::factory()->admin()->withoutTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('register.setup-two-factor', absolute: false));
    })->group('auth', 'login', 'guest');

    it('redirects users with two factor enabled to two factor challenge', function () {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
    })->group('auth', 'login', 'two-factor');

    it('denies authentication with invalid password', function () {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    })->group('auth', 'login', 'validation');

    it('rate limits failed login attempts', function () {
        $user = User::factory()->create();

        RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        expect($response)->toBeRateLimited();
    })->group('auth', 'login', 'rate-limiting');
});

describe('Logout', function () {
    it('allows users to logout', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    })->group('auth', 'logout', 'authenticated');
});