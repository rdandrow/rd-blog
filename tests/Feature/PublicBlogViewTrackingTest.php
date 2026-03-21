<?php

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

describe('Public Blog View Tracking', function () {
    test('records one view for a post visit', function () {
        $post = BlogPost::factory()->published()->create();

        $response = $this
            ->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]));

        $response->assertOk();

        expect(DB::table('blog_post_views')->count())->toBe(1)
            ->and(DB::table('blog_post_views')->where('blog_post_id', $post->id)->count())->toBe(1);
    });

    test('deduplicates repeated views within 30 minutes for same session and post', function () {
        $post = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->where('blog_post_id', $post->id)->count())->toBe(1);
    });

    test('counts a new view after dedupe window passes', function () {
        $post = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        DB::table('blog_post_views')->update([
            'viewed_at' => now()->subMinutes(31),
        ]);

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->where('blog_post_id', $post->id)->count())->toBe(2);
    });

    test('stores authenticated user id with tracked view', function () {
        $post = BlogPost::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->where('user_id', $user->id)->count())->toBe(1);
    });
});
