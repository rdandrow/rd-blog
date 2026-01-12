<?php

/**
 * Registration Test Suite
 *
 * Tests user registration functionality including account creation,
 * validation, and mandatory two-factor authentication setup.
 */

describe('User Registration', function () {
    it('renders registration screen', function () {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    })->group('auth', 'registration', 'guest');

    it('allows new users to register', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('register.setup-two-factor'));
    })->group('auth', 'registration', 'guest');
});