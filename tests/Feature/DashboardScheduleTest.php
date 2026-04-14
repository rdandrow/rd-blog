<?php

describe('dashboard scheduler wiring', function () {
    test('schedule includes dashboard metrics cache warm command every five minutes', function () {
        config(['dashboard.warm.enabled' => true]);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('php artisan dashboard:warm-metrics-cache')
            ->assertSuccessful();
    });
});
