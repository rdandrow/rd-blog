<?php

/**
 * User Invitation Test Suite
 *
 * Tests the invitation workflow including token validation, expiration handling,
 * password setup, and security measures.
 */

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

describe('Invitation Display', function () {
    it('renders invitation acceptance page with valid token', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        $response = $this->get(route('invitation.show', $token));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->has('token')
            ->has('email')
            ->has('name')
            ->where('email', $user->email)
            ->where('name', $user->name)
        );
    })->group('invitation', 'guest');

    it('redirects to login with error for invalid token', function () {
        $response = $this->get(route('invitation.show', 'invalid-token'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired invitation link.');
    })->group('invitation', 'guest', 'validation');

    it('redirects to login with error for expired invitation', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now()->subHours(49), // Expired
            'invitation_accepted_at' => null,
        ]);

        $response = $this->get(route('invitation.show', $token));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This invitation has expired. Please contact an administrator.');
    })->group('invitation', 'guest', 'validation');

    it('redirects to login with error for already accepted invitation', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => now(), // Already accepted
        ]);

        $response = $this->get(route('invitation.show', $token));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired invitation link.');
    })->group('invitation', 'guest', 'validation');
});

describe('Invitation Acceptance', function () {
    it('allows setting password with valid invitation', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
        ]);

        $response = $this->post(route('invitation.accept', $token), [
            'password' => 'secure-password-12345',
            'password_confirmation' => 'secure-password-12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Your password has been set successfully. Please log in.');
        $response->assertSessionHas('email', $user->email);

        $user->refresh();
        expect(Hash::check('secure-password-12345', $user->password))->toBeTrue();
        expect($user->invitation_accepted_at)->not->toBeNull();
        expect($user->invitation_token)->toBeNull();
    })->group('invitation', 'guest');

    it('requires password confirmation to match', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        $response = $this->post(route('invitation.accept', $token), [
            'password' => 'secure-password-12345',
            'password_confirmation' => 'different-password-67890',
        ]);

        expect($response)->toHaveValidationError('password');
    })->group('invitation', 'guest', 'validation');

    it('requires password to be at least 14 characters', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        $response = $this->post(route('invitation.accept', $token), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        expect($response)->toHaveValidationError('password');
    })->group('invitation', 'guest', 'validation');

    it('denies password setup with invalid token', function () {
        $response = $this->post(route('invitation.accept', 'invalid-token'), [
            'password' => 'secure-password-12345',
            'password_confirmation' => 'secure-password-12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired invitation link.');
    })->group('invitation', 'guest', 'validation');

    it('denies password setup with expired invitation', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now()->subHours(49), // Expired
            'invitation_accepted_at' => null,
        ]);

        $response = $this->post(route('invitation.accept', $token), [
            'password' => 'secure-password-12345',
            'password_confirmation' => 'secure-password-12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'This invitation has expired. Please contact an administrator.');

        // Verify password was not changed
        $user->refresh();
        expect(Hash::check('secure-password-12345', $user->password))->toBeFalse();
    })->group('invitation', 'guest', 'validation');

    it('denies password setup for already accepted invitation', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => now()->subMinutes(5), // Already accepted
        ]);

        $response = $this->post(route('invitation.accept', $token), [
            'password' => 'secure-password-12345',
            'password_confirmation' => 'secure-password-12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired invitation link.');
    })->group('invitation', 'guest', 'validation');
});

describe('Invitation Rate Limiting', function () {
    it('rate limits invitation page requests', function () {
        $user = User::factory()->create([
            'invitation_token' => $token = Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        // Make 11 requests (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->get(route('invitation.show', $token));
            if ($i < 10) {
                $response->assertStatus(200);
            }
        }

        // 11th request should be rate limited
        $response->assertStatus(429);
    })->group('invitation', 'rate-limiting', 'security');

    it('rate limits invitation acceptance requests', function () {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('invitation.accept', 'some-token'), [
                'password' => 'secure-password-12345',
                'password_confirmation' => 'secure-password-12345',
            ]);
        }

        // 6th request should be rate limited
        $response->assertStatus(429);
    })->group('invitation', 'rate-limiting', 'security');
});

describe('Invitation Resending', function () {
    it('allows master admin to resend invitation', function () {
        Notification::fake();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
        
        $masterAdmin = User::factory()->masterAdmin()->create();
        $user = User::factory()->create([
            'invitation_token' => Str::random(64),
            'invitation_sent_at' => now()->subHours(49), // Expired
            'invitation_accepted_at' => null,
        ]);

        $oldToken = $user->invitation_token;

        $response = $this->actingAs($masterAdmin)
            ->post(route('admin.users.resendInvitation', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation email has been resent successfully.');

        $user->refresh();
        expect($user->invitation_token)->not->toBe($oldToken);
        expect($user->invitation_sent_at->isToday())->toBeTrue();

        Notification::assertSentTo($user, UserInvitation::class);
    })->group('invitation', 'resending', 'master-admin');

    it('prevents resending invitation to user who already accepted', function () {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
        
        $masterAdmin = User::factory()->masterAdmin()->create();
        $user = User::factory()->create([
            'invitation_token' => null,
            'invitation_sent_at' => now()->subDays(2),
            'invitation_accepted_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($masterAdmin)
            ->post(route('admin.users.resendInvitation', $user));

        $response->assertRedirect();
        $response->assertSessionHas('errors');
    })->group('invitation', 'resending', 'validation');
});

describe('Invitation Security', function () {
    it('does not expose invitation token in user listing', function () {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
        
        $masterAdmin = User::factory()->masterAdmin()->create();
        $user = User::factory()->admin()->create([
            'invitation_token' => Str::random(64),
            'invitation_sent_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        $response = $this->actingAs($masterAdmin)
            ->get(route('admin.users.admins'));

        $response->assertStatus(200);
        
        // Verify the response doesn't contain invitation_token in any user data
        $adminsData = $response->viewData('page')['props']['admins']['data'];
        foreach ($adminsData as $admin) {
            expect($admin)->not->toHaveKey('invitation_token');
        }
    })->group('invitation', 'security');
});
