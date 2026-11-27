<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

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
     * This is a GET, so it must be safe to repeat: it never replaces an existing
     * secret. Users who already confirmed 2FA are sent on, and a pending secret is
     * shown again unchanged, so a refresh doesn't invalidate a half-scanned QR code.
     */
    public function showTwoFactorSetup(Request $request, TwoFactorAuthenticationProvider $provider): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect($this->homeFor($user));
        }

        if (is_null($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => encrypt($provider->generateSecretKey()),
                'two_factor_recovery_codes' => encrypt(json_encode(
                    Collection::times(8, fn () => Str::random(10).'-'.Str::random(10))->all()
                )),
            ])->save();
        }

        $secret = decrypt($user->two_factor_secret);

        return Inertia::render('auth/SetupTwoFactor', [
            'qrCode' => $this->qrCodeSvg($provider->qrCodeUrl(config('app.name'), $user->email, $secret)),
            'recoveryCodes' => json_decode(decrypt($user->two_factor_recovery_codes)),
            'secretKey' => $secret,
        ]);
    }

    /**
     * Confirm the pending 2FA secret with a code from the user's authenticator app.
     */
    public function confirmTwoFactorAuthentication(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            return redirect()->route('register.setup-two-factor');
        }

        if (! $provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            return back()->withErrors([
                'code' => 'The provided two factor authentication code was invalid.',
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return redirect($this->homeFor($user))
            ->with('status', 'Two-factor authentication has been enabled successfully!');
    }

    private function homeFor(User $user): string
    {
        return $user->isAdmin() ? route('dashboard') : route('home');
    }

    private function qrCodeSvg(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(192), new SvgImageBackEnd));

        return $writer->writeString($url);
    }
}
