<?php

use App\Http\Middleware\EnsureUserIsMasterAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

uses(Tests\TestCase::class);

describe('EnsureUserIsMasterAdmin Middleware', function () {
    beforeEach(function () {
        $this->middleware = new EnsureUserIsMasterAdmin();
        $this->request = Request::create('/admin/users', 'GET');
        $this->next = fn($req) => new Response('success');
    });

    it('allows master admin users to pass through', function () {
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $this->request->setUserResolver(fn() => $masterAdmin);
        
        $response = $this->middleware->handle($this->request, $this->next);
        
        expect($response->getContent())->toBe('success');
    })->group('middleware', 'authorization', 'master-admin');

    it('blocks regular admin users with 403', function () {
        $admin = User::factory()->make(['role' => 'admin']);
        $this->request->setUserResolver(fn() => $admin);
        
        $this->middleware->handle($this->request, $this->next);
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Access denied. Master admin privileges required.')
      ->group('middleware', 'authorization', 'master-admin');

    it('blocks member users with 403', function () {
        $member = User::factory()->make(['role' => 'member']);
        $this->request->setUserResolver(fn() => $member);
        
        $this->middleware->handle($this->request, $this->next);
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Access denied. Master admin privileges required.')
      ->group('middleware', 'authorization', 'master-admin');

    it('blocks unauthenticated requests with 403', function () {
        $this->request->setUserResolver(fn() => null);
        
        $this->middleware->handle($this->request, $this->next);
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Access denied. Master admin privileges required.')
      ->group('middleware', 'authorization', 'master-admin');
});
