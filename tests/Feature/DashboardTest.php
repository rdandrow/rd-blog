<?php

/**
 * Dashboard Test Suite
 *
 * Tests dashboard access control and functionality for authenticated users.
 * Verifies proper authentication enforcement and dashboard content display.
 *
 * Test Categories:
 * - Authentication: Guest redirect behavior
 * - Dashboard Access: Authenticated user access
 *
 * Features Tested:
 * - Guest users redirected to login
 * - Authenticated users can access dashboard
 * - Proper status codes and redirects
 */

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});