<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // Ensure user is authenticated
        if (!$user) {
            return redirect('/login');
        }

        // Redirect based on user role
        if (!$user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('register.setup-two-factor');
        }

        if ($user->isMasterAdmin() || $user->isRegularAdmin()) {
            return redirect()->intended('/admin/dashboard');
        }

        // Members redirect to home page
        return redirect()->intended('/');
    }
}
