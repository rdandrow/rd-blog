<?php

/**
 * Two-Factor Authentication Settings Test Suite
 *
 * Tests two-factor authentication management including enabling, disabling,
 * QR code generation, and recovery codes.
 */

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

describe('Two-Factor Settings Page', function () {
    it('renders the two-factor settings page', function () {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/TwoFactor')
                ->where('twoFactorEnabled', false)
            );
    })->group('settings', '2fa', 'authenticated');

    it('requires password confirmation when password confirmation is enabled', function () {
        $user = User::factory()->withTwoFactor()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)->get(route('two-factor.show'));

        $response->assertRedirect(route('password.confirm'));
    })->group('settings', '2fa', 'authentication');

    it('does not require password confirmation when disabled', function () {
        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/TwoFactor')
            );
    })->group('settings', '2fa', 'authenticated');

    it('returns forbidden when two-factor authentication is disabled', function () {
        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'));

        expect($response)->toBeForbidden();
    })->group('settings', '2fa', 'authorization');
});