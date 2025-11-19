<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class RegisterController extends Controller
{
    /**
     * Display the registration form.
     */
    public function create()
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request with mandatory MFA setup.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Always redirect to MFA setup for new users (mandatory)
        return $this->setupTwoFactorAuthentication($user);
    }

    /**
     * Setup two-factor authentication for the user.
     */
    public function setupTwoFactorAuthentication(User $user)
    {
        $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);
        
        // Generate recovery codes using Laravel's standard approach
        $recoveryCodes = Collection::times(8, function () {
            return Str::random(10) . '-' . Str::random(10);
        })->toArray();
        
        // Enable 2FA for the user
        $user->forceFill([
            'two_factor_secret' => encrypt($twoFactorProvider->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        return Inertia::render('auth/SetupTwoFactor', [
            'qrCode' => $this->generateQrCodeSvg($user, $twoFactorProvider),
            'recoveryCodes' => json_decode(decrypt($user->two_factor_recovery_codes)),
            'secretKey' => decrypt($user->two_factor_secret),
        ]);
    }

    /**
     * Confirm two-factor authentication setup.
     */
    public function confirmTwoFactorAuthentication(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);

        if (! $twoFactorProvider->verify(decrypt($user->two_factor_secret), $request->code)) {
            return back()->withErrors([
                'code' => 'The provided two factor authentication code was invalid.',
            ]);
        }

        // Mark 2FA as confirmed
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return redirect('/dashboard')->with('status', 'Two-factor authentication has been enabled successfully!');
    }

    /**
     * Generate QR Code SVG for two-factor authentication.
     */
    private function generateQrCodeSvg(User $user, TwoFactorAuthenticationProvider $provider)
    {
        $qrCodeUrl = $provider->qrCodeUrl(
            config('app.name'),
            $user->email,
            decrypt($user->two_factor_secret)
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }
}