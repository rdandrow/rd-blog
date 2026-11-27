<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;

/**
 * The mandatory 2FA setup flow for new users:
 * GET  /register/setup-two-factor     (register.setup-two-factor)
 * POST /two-factor-challenge/confirm
 *
 * The setup page must never change the secret of a user who already confirmed 2FA.
 * Doing so locks them out: Fortify still treats 2FA as enabled, but their
 * authenticator app no longer matches. It's a GET, so a cross-site request can
 * trigger it.
 */
function userWithConfirmedTwoFactor(array $attributes = []): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => now(),
        ...$attributes,
    ]);
}

function userWithPendingTwoFactor(): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        'two_factor_confirmed_at' => null,
    ]);
}

describe('2FA setup page', function () {
    it('redirects guests to login', function () {
        $this->get(route('register.setup-two-factor'))->assertRedirect(route('login'));
    });

    it('does not change the secret of a member who already confirmed 2FA', function () {
        $user = userWithConfirmedTwoFactor(['role' => 'member']);
        $before = $user->only('two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at');

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $response->assertRedirect(route('home'));
        expect($user->fresh()->only(array_keys($before)))->toEqual($before);
    });

    it('sends an admin who already confirmed 2FA to the dashboard without changing the secret', function () {
        $user = userWithConfirmedTwoFactor(['role' => 'admin']);
        $secret = $user->two_factor_secret;

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $response->assertRedirect(route('dashboard'));
        expect($user->fresh()->two_factor_secret)->toBe($secret);
    });

    it('generates a secret and recovery codes for a user without 2FA', function () {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $user->refresh();
        expect($user->two_factor_secret)->not->toBeNull()
            ->and($user->two_factor_confirmed_at)->toBeNull();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/SetupTwoFactor')
            ->where('secretKey', decrypt($user->two_factor_secret))
            ->has('recoveryCodes', 8)
            ->has('qrCode')
        );
    });

    it('sends a master admin who already confirmed 2FA to the dashboard', function () {
        $user = userWithConfirmedTwoFactor(['role' => 'master_admin']);

        $this->actingAs($user)->get(route('register.setup-two-factor'))->assertRedirect(route('dashboard'));
    });

    it('clears a stale confirmation when it has to create a new secret', function () {
        $user = User::factory()->withoutTwoFactor()->create(['two_factor_confirmed_at' => now()]);

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/SetupTwoFactor'));
        $user->refresh();
        expect($user->two_factor_secret)->not->toBeNull()
            ->and($user->two_factor_confirmed_at)->toBeNull()
            ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
    });

    it('replaces a pending secret that has no recovery codes instead of erroring', function () {
        $user = userWithPendingTwoFactor();
        $user->forceFill(['two_factor_recovery_codes' => null])->save();

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page->has('recoveryCodes', 8));
    });

    it('replaces a pending secret that cannot be decrypted instead of erroring', function () {
        $user = User::factory()->create([
            'two_factor_secret' => 'plain-text-seed-value',
            'two_factor_recovery_codes' => 'plain-text-seed-value',
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $response->assertOk();
        expect(decrypt($user->fresh()->two_factor_secret))->toBeString();
    });

    it('shows the same pending secret on every visit until it is confirmed', function () {
        $user = userWithPendingTwoFactor();
        $storedSecret = $user->two_factor_secret;

        $first = $this->actingAs($user)->get(route('register.setup-two-factor'));
        $second = $this->actingAs($user)->get(route('register.setup-two-factor'));

        $first->assertInertia(fn (Assert $page) => $page->where('secretKey', decrypt($storedSecret)));
        $second->assertInertia(fn (Assert $page) => $page->where('secretKey', decrypt($storedSecret)));
        expect($user->fresh()->two_factor_secret)->toBe($storedSecret);
    });
});

describe('2FA setup confirmation', function () {
    it('confirms 2FA with a valid code', function () {
        $user = userWithPendingTwoFactor();
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => $code]);

        $response->assertRedirect('/');
        expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
    });

    it('rejects an invalid code and leaves 2FA unconfirmed', function () {
        $user = userWithPendingTwoFactor();

        $response = $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => '000000']);

        $response->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
        expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
    });

    it('returns to the page the user originally asked for after confirming', function () {
        $user = userWithPendingTwoFactor();
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => url('/settings/profile')])
            ->post('/two-factor-challenge/confirm', ['code' => $code]);

        $response->assertRedirect('/settings/profile');
    });

    it('does not re-confirm a user who already confirmed 2FA', function () {
        $user = userWithConfirmedTwoFactor(['role' => 'member', 'two_factor_confirmed_at' => now()->subWeek()]);
        $confirmedAt = $user->two_factor_confirmed_at;
        $validCode = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $response = $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => $validCode]);

        $response->assertRedirect(route('home'));
        expect($user->fresh()->two_factor_confirmed_at->equalTo($confirmedAt))->toBeTrue();
    });

    it('rate limits confirmation attempts per user', function () {
        $user = userWithPendingTwoFactor();
        $other = userWithPendingTwoFactor();

        foreach (range(1, 6) as $attempt) {
            $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => '000000']);
        }

        $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => '000000'])->assertTooManyRequests();
        $this->actingAs($other)->post('/two-factor-challenge/confirm', ['code' => '000000'])->assertRedirect();
    });

    it('sends a user with no pending secret back to setup instead of erroring', function () {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->actingAs($user)->post('/two-factor-challenge/confirm', ['code' => '123456']);

        $response->assertRedirect(route('register.setup-two-factor'));
        expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
    });
});
