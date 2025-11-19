<?php

namespace App\Listeners;

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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