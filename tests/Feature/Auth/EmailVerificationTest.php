<?php

/**
 * Email Verification Test Suite
 *
 * Tests email verification functionality including verification notice,
 * verification link handling, and verified user state.
 */

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

describe('Email Verification', function () {
    it('renders email verification screen', function () {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
    })->group('auth', 'email-verification', 'authenticated');

    it('allows verifying email with valid link', function () {
        $user = User::factory()->admin()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
        $response->assertRedirect('/admin/dashboard?verified=1');
    })->group('auth', 'email-verification', 'authenticated');

    it('denies verification with invalid hash', function () {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertNotDispatched(Verified::class);
        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    })->group('auth', 'email-verification', 'validation');

    it('denies verification with invalid user id', function () {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 123, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertNotDispatched(Verified::class);
        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    })->group('auth', 'email-verification', 'validation');

    it('redirects verified users to dashboard from verification prompt', function () {
        $user = User::factory()->admin()->create();

        Event::fake();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        Event::assertNotDispatched(Verified::class);
        $response->assertRedirect('/admin/dashboard');
    })->group('auth', 'email-verification', 'authenticated');
});

test('already verified user visiting verification link is redirected without firing event again', function () {
    $user = User::factory()->admin()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect('/admin/dashboard?verified=1');

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});