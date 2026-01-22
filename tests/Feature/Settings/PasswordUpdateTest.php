<?php

/**
 * Password Update Test Suite
 *
 * Tests password change functionality including current password validation,
 * new password requirements, and security checks.
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Password Update Page', function () {
    it('displays the password update page', function () {
        $user = User::factory()->withTwoFactor()->create();

        $response = authenticatedGet($user, route('user-password.edit'));

        expect($response->status())->toBe(HTTP_OK);
    })->group('settings', 'password', 'authenticated');
});

describe('Password Change', function () {
    it('allows users to update their password', function () {
        $user = User::factory()->withTwoFactor()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-secure-password-456',
                'password_confirmation' => 'new-secure-password-456',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('user-password.edit'));

        expect(Hash::check('new-secure-password-456', $user->refresh()->password))->toBeTrue();
    })->group('settings', 'password', 'authenticated');

    it('requires correct current password to update', function () {
        $user = User::factory()->withTwoFactor()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password-789',
                'password_confirmation' => 'new-secure-password-789',
            ]);

        expect($response)->toHaveValidationError('current_password');
        $response->assertRedirect(route('user-password.edit'));
    })->group('settings', 'password', 'validation');
});