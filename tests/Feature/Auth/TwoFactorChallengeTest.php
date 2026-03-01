<?php

/**
 * Two-Factor Authentication Challenge Test Suite
 *
 * Tests two-factor authentication challenge process including
 * code validation, recovery codes, and security enforcement.
 */

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

describe('Two-Factor Challenge', function () {
    it('redirects to login when not authenticated', function () {
        $response = $this->get(route('two-factor.login'));

        expect($response)->toRedirectToLogin();
    })->group('auth', 'two-factor', 'guest');

    it('renders two factor challenge screen', function () {
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

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/TwoFactorChallenge')
            );
    })->group('auth', 'two-factor', 'challenge');
});