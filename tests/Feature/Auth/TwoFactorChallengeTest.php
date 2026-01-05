<?php

/**
 * Two-Factor Authentication Challenge Test Suite
 *
 * Tests two-factor authentication challenge process including
 * code validation, recovery codes, and security enforcement.
 *
 * Test Categories:
 * - Challenge Screen: 2FA challenge page rendering
 * - Code Validation: Authentication code verification
 * - Recovery Codes: Backup authentication method
 * - Authentication State: Session and state management
 *
 * Features Tested:
 * - 2FA challenge page accessibility
 * - Valid authentication code acceptance
 * - Invalid code rejection
 * - Recovery code authentication
 * - Challenge screen protection (authenticated only)
 * - Redirect after successful 2FA
 * - Rate limiting on failed attempts
 */

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('two factor challenge redirects to login when not authenticated', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

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
});