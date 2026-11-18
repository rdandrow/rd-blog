<?php

use App\Models\BlogPost;
use Illuminate\Support\Facades\DB;

function insertViewAt(int $postId, $viewedAt): void
{
    DB::table('blog_post_views')->insert([
        'blog_post_id' => $postId,
        'session_id' => 'session-'.uniqid(),
        'viewed_at' => $viewedAt,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

describe('dashboard:prune-views command', function () {
    test('deletes views older than the configured retention window and keeps newer ones', function () {
        config(['dashboard.views.retention_days' => 30]);
        $post = BlogPost::factory()->published()->create();

        insertViewAt($post->id, now()->subDays(45));
        insertViewAt($post->id, now()->subDays(31));
        insertViewAt($post->id, now()->subDays(29));
        insertViewAt($post->id, now()->subHour());

        $this->artisan('dashboard:prune-views')
            ->expectsOutputToContain('Pruned 2 blog post view(s) older than 30 day(s).')
            ->assertSuccessful();

        expect(DB::table('blog_post_views')->count())->toBe(2);
    });

    test('the --days option overrides the configured retention window', function () {
        config(['dashboard.views.retention_days' => 365]);
        $post = BlogPost::factory()->published()->create();

        insertViewAt($post->id, now()->subDays(10));
        insertViewAt($post->id, now()->subDay());

        $this->artisan('dashboard:prune-views', ['--days' => 5])->assertSuccessful();

        expect(DB::table('blog_post_views')->count())->toBe(1);
    });

    test('does nothing when no retention window is configured', function () {
        config(['dashboard.views.retention_days' => null]);
        $post = BlogPost::factory()->published()->create();

        insertViewAt($post->id, now()->subYears(3));

        $this->artisan('dashboard:prune-views')
            ->expectsOutputToContain('nothing was pruned')
            ->assertSuccessful();

        expect(DB::table('blog_post_views')->count())->toBe(1);
    });

    test('rejects a non-numeric --days value without deleting anything', function () {
        $post = BlogPost::factory()->published()->create();

        insertViewAt($post->id, now()->subYears(3));

        $this->artisan('dashboard:prune-views', ['--days' => 'abc'])
            ->expectsOutputToContain('nothing was pruned')
            ->assertSuccessful();

        expect(DB::table('blog_post_views')->count())->toBe(1);
    });

    test('is only scheduled when a retention window is configured', function () {
        config(['dashboard.views.retention_days' => 90]);

        // Running any schedule command makes the console kernel define the schedule.
        $this->artisan('schedule:list')->assertSuccessful();

        $scheduled = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'dashboard:prune-views'));

        expect($scheduled)->not->toBeNull()
            ->and($scheduled->filtersPass(app()))->toBeTrue();

        config(['dashboard.views.retention_days' => null]);

        expect($scheduled->filtersPass(app()))->toBeFalse();
    });
});
