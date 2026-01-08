<?php

/**
 * Profile Update Test Suite
 *
 * Tests user profile management including viewing and updating
 * profile information with validation.
 */

use App\Models\User;

describe('Profile Display', function () {
    it('displays the profile edit page', function () {
        $user = User::factory()->create();

        $response = authenticatedGet($user, route('profile.edit'));

        $response->assertOk();
    })->group('settings', 'profile', 'authenticated');
});

describe('Profile Update', function () {
    it('allows users to update their profile information', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        expect($user->name)->toBe('Test User')
            ->and($user->email)->toBe('test@example.com')
            ->and($user->email_verified_at)->toBeNull();
    })->group('settings', 'profile', 'authenticated');

    it('preserves email verification when email is unchanged', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        expect($user->refresh()->email_verified_at)->not->toBeNull();
    })->group('settings', 'profile', 'validation');
});

describe('Account Deletion', function () {
    it('allows users to delete their account with correct password', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        expect($user->fresh())->toBeNull();
    })->group('settings', 'profile', 'deletion');

    it('requires correct password to delete account', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        expect($response)->toHaveValidationError('password');
        $response->assertRedirect(route('profile.edit'));

        expect($user->fresh())->not->toBeNull();
    })->group('settings', 'profile', 'validation', 'deletion');
});