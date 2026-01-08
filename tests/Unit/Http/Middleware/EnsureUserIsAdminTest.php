<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

uses(Tests\TestCase::class);

describe('EnsureUserIsAdmin Middleware', function () {
    beforeEach(function () {
        $this->middleware = new EnsureUserIsAdmin();
        $this->request = Request::create('/admin/dashboard', 'GET');
        $this->next = fn($req) => new Response('success');
    });

    it('allows admin users to pass through', function () {
        $admin = User::factory()->make(['role' => 'admin']);
        $this->request->setUserResolver(fn() => $admin);
        
        $response = $this->middleware->handle($this->request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->group('middleware', 'authorization', 'admin');

    it('allows master admin users to pass through', function () {
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $this->request->setUserResolver(fn() => $masterAdmin);
        
        $response = $this->middleware->handle($this->request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->group('middleware', 'authorization', 'admin');

    it('blocks member users with 403', function () {
        $member = User::factory()->make(['role' => 'member']);
        $this->request->setUserResolver(fn() => $member);
        
        $this->middleware->handle($this->request, $this->next);
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Access denied. Admin privileges required.')
      ->group('middleware', 'authorization', 'admin');

    it('blocks unauthenticated requests with 403', function () {
        $this->request->setUserResolver(fn() => null);
        
        $this->middleware->handle($this->request, $this->next);
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Access denied. Admin privileges required.')
      ->group('middleware', 'authorization', 'admin');
});
