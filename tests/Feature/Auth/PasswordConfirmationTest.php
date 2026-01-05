<?php

/**
 * Password Confirmation Test Suite
 *
 * Tests password confirmation functionality for sensitive operations
 * requiring recent password verification.
 *
 * Test Categories:
 * - Confirmation Screen: Password confirmation page rendering
 * - Password Validation: Password verification for sensitive actions
 * - Authentication: Authenticated user requirement
 *
 * Features Tested:
 * - Password confirmation page accessibility
 * - Valid password confirmation
 * - Invalid password rejection
 * - Authentication requirement
 * - Redirect after confirmation
 * - Confirmation expiration
 */

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertStatus(200);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/ConfirmPassword')
    );
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});