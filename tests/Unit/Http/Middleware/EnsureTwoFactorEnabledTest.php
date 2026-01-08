<?php

use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

uses(Tests\TestCase::class);

describe('EnsureTwoFactorEnabled Middleware', function () {
    beforeEach(function () {
        $this->middleware = new EnsureTwoFactorEnabled();
        $this->next = fn($req) => new Response('success');
    });

    it('allows authenticated users with 2FA enabled to pass through', function () {
        $user = User::factory()->make([
            'role' => 'member',
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
        ]);
        
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);
        Auth::shouldReceive('user')->andReturn($user);
        
        $response = $this->middleware->handle($request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->group('middleware', 'two-factor', 'authentication');

    it('redirects authenticated users without 2FA to setup route', function () {
        $user = User::factory()->make([
            'role' => 'member',
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);
        
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => $user);
        Auth::shouldReceive('user')->andReturn($user);
        
        $response = $this->middleware->handle($request, $this->next);
        
        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toContain('setup-two-factor');
    })->group('middleware', 'two-factor', 'authentication');

    it('allows unauthenticated requests to pass through', function () {
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn() => null);
        Auth::shouldReceive('user')->andReturn(null);
        
        $response = $this->middleware->handle($request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->group('middleware', 'two-factor', 'authentication');

    it('allows excepted routes to bypass 2FA check', function (string $route) {
        $user = User::factory()->make([
            'role' => 'member',
            'two_factor_secret' => null, // No 2FA setup
        ]);
        
        $request = Request::create($route, 'GET');
        $request->setUserResolver(fn() => $user);
        Auth::shouldReceive('user')->andReturn($user);
        
        $response = $this->middleware->handle($request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->with([
        'login' => '/login',
        'logout' => '/logout',
        'register' => '/register',
        'password reset' => '/password/reset',
        'two-factor challenge' => '/two-factor-challenge',
        '2FA setup' => '/user/two-factor-authentication',
        '2FA QR code' => '/user/two-factor-qr-code',
        'settings 2FA' => '/settings/two-factor',
    ])->group('middleware', 'two-factor', 'authentication');

    it('does not redirect when user is on 2FA setup routes', function () {
        $user = User::factory()->make([
            'role' => 'member',
            'two_factor_secret' => null,
        ]);
        
        $request = Request::create('/settings/two-factor', 'GET');
        $request->setUserResolver(fn() => $user);
        Auth::shouldReceive('user')->andReturn($user);
        
        $response = $this->middleware->handle($request, $this->next);
        
        expect($response->getContent())->toBe('success')
            ->and($response->isRedirect())->toBeFalse();
    })->group('middleware', 'two-factor', 'authentication');
});
