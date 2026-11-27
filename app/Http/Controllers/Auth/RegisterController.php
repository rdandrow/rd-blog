<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;

/**
 * Mandatory 2FA setup for new users. Registration itself is handled by Fortify;
 * RegisterResponse, LoginResponse, and EnsureTwoFactorEnabled send users here
 * until they confirm 2FA.
 */
class RegisterController extends Controller
{
    /**
     * Show the 2FA setup page.
     *
     * This is a GET, so it must be safe to repeat: it never replaces a usable secret.
     * Users who already confirmed 2FA are sent on, and a pending secret is shown again
     * unchanged, so a refresh doesn't invalidate a half-scanned QR code.
     *
     * It still creates a secret when none is usable. Moving that to a POST is a
     * tracked follow-up (docs/ROADMAP.md).
     */
    public function showTwoFactorSetup(Request $request, EnableTwoFactorAuthentication $enable): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->intended($this->homeFor($user));
        }

        if (! $this->hasUsablePendingSecret($user)) {
            $enable($user, force: true);

            // A stale confirmation must not carry over to a secret the user hasn't scanned.
            $user->forceFill(['two_factor_confirmed_at' => null])->save();
        }

        return Inertia::render('auth/SetupTwoFactor', [
            'qrCode' => $user->twoFactorQrCodeSvg(),
            'recoveryCodes' => $user->recoveryCodes(),
            'secretKey' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
        ]);
    }

    /**
     * Confirm the pending 2FA secret with a code from the user's authenticator app.
     *
     * Validation errors use Fortify's `confirmTwoFactorAuthentication` error bag.
     */
    public function confirmTwoFactorAuthentication(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->intended($this->homeFor($user));
        }

        if (empty($user->two_factor_secret)) {
            return redirect()->route('register.setup-two-factor');
        }

        $confirm($user, $request->input('code'));

        return redirect()->intended($this->homeFor($user))
            ->with('status', 'Two-factor authentication has been enabled successfully!');
    }

    /**
     * A pending secret is usable when both it and the recovery codes decrypt.
     * Rows written outside Fortify (missing codes, plain-text seed data) are not.
     */
    private function hasUsablePendingSecret(User $user): bool
    {
        if (empty($user->two_factor_secret) || empty($user->two_factor_recovery_codes)) {
            return false;
        }

        try {
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
            Fortify::currentEncrypter()->decrypt($user->two_factor_recovery_codes);
        } catch (DecryptException) {
            return false;
        }

        return true;
    }

    private function homeFor(User $user): string
    {
        return $user->isAdmin() ? route('dashboard') : route('home');
    }
}
