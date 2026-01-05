<?php

/**
 * Password Reset Test Suite
 *
 * Tests password reset functionality including reset link generation,
 * token validation, and password update process.
 */

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

describe('Password Reset Link Request', function () {
    it('renders reset password link screen', function () {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    })->group('auth', 'password-reset', 'guest');

    it('allows requesting password reset link', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    })->group('auth', 'password-reset', 'guest');
});

describe('Password Reset', function () {
    it('renders reset password screen with valid token', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertStatus(200);

            return true;
        });
    })->group('auth', 'password-reset', 'guest');

    it('allows resetting password with valid token', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    })->group('auth', 'password-reset', 'guest');

    it('denies password reset with invalid token', function () {
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        expect($response)->toHaveValidationError('email');
    })->group('auth', 'password-reset', 'validation');
});