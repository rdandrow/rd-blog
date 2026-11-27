<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class HandlePostRegistrationMfa
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $request = request();

        // Always require MFA setup for new users
        $request->session()->put('setup_2fa_after_registration', true);
    }
}
