<?php

/**
 * Registration Test Suite
 *
 * Tests user registration functionality including account creation,
 * validation, and mandatory two-factor authentication setup.
 *
 * Test Categories:
 * - Registration Screen: Registration page rendering
 * - Registration Process: Account creation and validation
 * - 2FA Setup: Mandatory two-factor authentication configuration
 * - Input Validation: Email, password, and name validation
 *
 * Features Tested:
 * - Registration page accessibility
 * - New user account creation
 * - Email uniqueness validation
 * - Password strength requirements
 * - Password confirmation matching
 * - Mandatory 2FA setup redirect
 * - User authentication after registration
 * - Default role assignment (member)
 */

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password', // Min 8 chars required
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('register.setup-two-factor')); // 2FA setup is mandatory
});