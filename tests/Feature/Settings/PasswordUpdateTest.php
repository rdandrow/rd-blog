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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('user-password.edit'));

        expect($response->status())->toBe(200);
    })->group('settings', 'password', 'authenticated');
});

describe('Password Change', function () {
    it('allows users to update their password', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('user-password.edit'));

        expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    })->group('settings', 'password', 'authenticated');

    it('requires correct current password to update', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('user-password.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        expect($response)->toHaveValidationError('current_password');
        $response->assertRedirect(route('user-password.edit'));
    })->group('settings', 'password', 'validation');
});