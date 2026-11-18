<?php

use App\Http\Middleware\TrackLastActiveAt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

function runTrackLastActiveAt(): void
{
    (new TrackLastActiveAt)->handle(Request::create('/'), fn () => new Response);
}

describe('TrackLastActiveAt Middleware', function () {
    test('sets last_active_at when the user has never been active', function () {
        $user = User::factory()->create(['last_active_at' => null]);
        Auth::setUser($user);

        runTrackLastActiveAt();

        expect($user->fresh()->last_active_at)->not->toBeNull();
    });

    test('does not write again within the throttle window', function () {
        $recent = now()->subMinute()->startOfSecond();
        $user = User::factory()->create(['last_active_at' => $recent]);
        Auth::setUser($user);

        runTrackLastActiveAt();

        expect($user->fresh()->last_active_at->equalTo($recent))->toBeTrue();
    });

    test('does not touch updated_at when refreshing last_active_at', function () {
        $user = User::factory()->create([
            'last_active_at' => now()->subHour(),
            'updated_at' => now()->subDay()->startOfSecond(),
        ]);
        $originalUpdatedAt = $user->updated_at->copy();
        Auth::setUser($user);

        runTrackLastActiveAt();

        expect($user->fresh()->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
    });

    test('restores model timestamps even when saving fails', function () {
        $user = new class extends User
        {
            public function save(array $options = []): bool
            {
                throw new RuntimeException('save failed');
            }
        };
        $user->last_active_at = null;
        Auth::setUser($user);

        expect(fn () => runTrackLastActiveAt())->toThrow(RuntimeException::class, 'save failed')
            ->and($user->timestamps)->toBeTrue();
    });
});
