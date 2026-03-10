<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

class TestableAppServiceProvider extends AppServiceProvider
{
    public function publicShouldLogQueryBindings(): bool
    {
        return $this->shouldLogQueryBindings();
    }

    public function publicSanitizeBindings(array $bindings): array
    {
        return $this->sanitizeBindings($bindings);
    }
}

describe('AppServiceProvider binding safety', function () {

    it('defaults to not logging query bindings', function () {
        putenv('LOG_QUERY_BINDINGS');

        $provider = new TestableAppServiceProvider(app());

        expect($provider->publicShouldLogQueryBindings())->toBeFalse();
    });

    it('reads LOG_QUERY_BINDINGS=true when explicitly set', function () {
        putenv('LOG_QUERY_BINDINGS=true');

        try {
            $provider = new TestableAppServiceProvider(app());
            expect($provider->publicShouldLogQueryBindings())->toBeTrue();
        } finally {
            putenv('LOG_QUERY_BINDINGS');
        }
    });

    it('sanitizes bindings by redacting strings and preserving scalar types', function () {
        $provider = new TestableAppServiceProvider(app());

        $bindings = [
            null,
            true,
            42,
            12.5,
            'secret-token',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ['nested' => 'value'],
        ];

        $sanitized = $provider->publicSanitizeBindings($bindings);

        expect($sanitized[0])->toBeNull()
            ->and($sanitized[1])->toBeTrue()
            ->and($sanitized[2])->toBe(42)
            ->and($sanitized[3])->toBe(12.5)
            ->and($sanitized[4])->toBe('[REDACTED]')
            ->and($sanitized[5])->toBe('2026-01-01T00:00:00+00:00')
            ->and($sanitized[6])->toBe('[REDACTED]');
    });

});
