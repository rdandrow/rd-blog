<?php

use App\Models\User;

describe('dashboard:warm-metrics-cache command', function () {
    test('reports when dashboard cache is disabled', function () {
        config(['dashboard.cache.enabled' => false]);

        $this->artisan('dashboard:warm-metrics-cache')
            ->expectsOutput('Dashboard metrics cache is disabled.')
            ->assertSuccessful();
    });

    test('warms cache for admin and master admin users only', function () {
        config(['dashboard.cache.enabled' => true]);

        User::factory()->admin()->create();
        User::factory()->masterAdmin()->create();
        User::factory()->create(['role' => 'member']);

        $this->artisan('dashboard:warm-metrics-cache')
            ->expectsOutput('Warmed dashboard metrics cache for 2 admin user(s) across 3 scope payload(s).')
            ->assertSuccessful();
    });

    test('supports warming cache for a specific admin user id', function () {
        config(['dashboard.cache.enabled' => true]);

        $target = User::factory()->admin()->create();
        User::factory()->masterAdmin()->create();

        $this->artisan('dashboard:warm-metrics-cache', ['--user-id' => [$target->id]])
            ->expectsOutput('Warmed dashboard metrics cache for 1 admin user(s) across 1 scope payload(s).')
            ->assertSuccessful();
    });

    test('reports when no admin users are available to warm', function () {
        config(['dashboard.cache.enabled' => true]);

        User::factory()->create(['role' => 'member']);

        $this->artisan('dashboard:warm-metrics-cache')
            ->expectsOutput('No admin users found to warm dashboard metrics cache.')
            ->assertSuccessful();
    });

    test('supports warming cache for a specific master admin user id', function () {
        config(['dashboard.cache.enabled' => true]);

        $targetMasterAdmin = User::factory()->masterAdmin()->create();
        User::factory()->admin()->create();

        $this->artisan('dashboard:warm-metrics-cache', ['--user-id' => [$targetMasterAdmin->id]])
            ->expectsOutput('Warmed dashboard metrics cache for 1 admin user(s) across 2 scope payload(s).')
            ->assertSuccessful();
    });
});
