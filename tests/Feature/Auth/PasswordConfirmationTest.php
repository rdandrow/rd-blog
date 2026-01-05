<?php

/**
 * Password Confirmation Test Suite
 *
 * Tests password confirmation functionality for sensitive operations
 * requiring recent password verification.
 */

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Password Confirmation', function () {
    it('renders confirm password screen', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/ConfirmPassword')
        );
    })->group('auth', 'password-confirmation', 'authenticated');

    it('requires authentication', function () {
        $response = $this->get(route('password.confirm'));

        expect($response)->toRedirectToLogin();
    })->group('auth', 'password-confirmation', 'guest');
});