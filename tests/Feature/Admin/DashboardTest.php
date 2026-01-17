<?php

/**
 * Dashboard Test Suite
 *
 * Tests dashboard access control and functionality for authenticated users.
 */

use App\Models\User;

describe('Dashboard Access', function () {
    it('redirects unauthenticated users to login page', function () {
        $response = $this->get(route('dashboard'));
        
        expect($response)->toRedirectToLogin();
    })->group('dashboard', 'guest');

    it('allows authenticated users to visit dashboard', function () {
        $user = User::factory()->admin()->withTwoFactor()->create();

        $response = authenticatedGet($user, route('dashboard'));

        $response->assertStatus(200);
    })->group('dashboard', 'authenticated');
});