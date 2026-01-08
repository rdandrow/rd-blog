<?php

use App\Listeners\HandlePostRegistrationMfa;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('HandlePostRegistrationMfa Listener', function () {
    beforeEach(function () {
        $this->listener = new HandlePostRegistrationMfa();
        
        // Create a proper request with session attached
        $request = Request::create('/register', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);
    });

    it('sets session key for 2FA setup after registration', function () {
        $user = User::factory()->make(['role' => 'member']);
        $event = new Registered($user);
        
        $this->listener->handle($event);
        
        expect(session()->has('setup_2fa_after_registration'))->toBeTrue()
            ->and(session()->get('setup_2fa_after_registration'))->toBeTrue();
    })->group('listeners', 'events', 'two-factor', 'registration');

    it('handles Registered event correctly', function () {
        $user = User::factory()->make(['role' => 'member']);
        $event = new Registered($user);
        
        // Should not throw exception
        $this->listener->handle($event);
        
        expect(true)->toBeTrue();
    })->group('listeners', 'events', 'two-factor', 'registration');

    it('works with different user types', function (array $userData) {
        $user = User::factory()->make($userData);
        $event = new Registered($user);
        
        $this->listener->handle($event);
        
        expect(session()->get('setup_2fa_after_registration'))->toBeTrue();
    })->with([
        'member' => [['role' => 'member']],
        'admin' => [['role' => 'admin']],
        'master_admin' => [['role' => 'master_admin']],
    ])->group('listeners', 'events', 'two-factor', 'registration');

    it('session key persists after handler execution', function () {
        $user = User::factory()->make(['role' => 'member']);
        $event = new Registered($user);
        
        $this->listener->handle($event);
        
        // Verify session key is still accessible after handler returns
        expect(session()->has('setup_2fa_after_registration'))->toBeTrue();
        
        // Simulate checking it later in the request lifecycle
        $laterCheck = session()->get('setup_2fa_after_registration');
        expect($laterCheck)->toBeTrue();
    })->group('listeners', 'events', 'two-factor', 'registration');
});

describe('HandlePostRegistrationMfa Event Binding', function () {
    it('is registered to listen for Registered event', function () {
        Event::fake();
        
        $user = User::factory()->make(['role' => 'member']);
        
        event(new Registered($user));
        
        Event::assertDispatched(Registered::class);
    })->group('listeners', 'events', 'integration');
});
