<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
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

        // Check if 2FA setup is required
        if ($request->session()->has('setup_2fa_after_registration')) {
            return redirect()->route('register.setup-two-factor');
        }

        // Ensure user is authenticated
        if (!$user) {
            return redirect('/login');
        }

        // Redirect based on user role
        if ($user->isMasterAdmin() || $user->isRegularAdmin()) {
            return redirect('/admin/dashboard');
        }

        // Members redirect to home page
        return redirect('/');
    }
}
