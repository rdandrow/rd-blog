<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostViewTrackingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function ensureBlogPostViewsTableExists(): void
{
    if (Schema::hasTable('blog_post_views')) {
        return;
    }

    Schema::create('blog_post_views', function (Blueprint $table) {
        $table->id();
        $table->foreignId('blog_post_id')->constrained('blog_posts')->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        $table->string('session_id', 128)->nullable();
        $table->string('ip_hash', 64)->nullable();
        $table->string('user_agent_hash', 64)->nullable();
        $table->timestamp('viewed_at')->index();
        $table->timestamps();

        $table->index(['blog_post_id', 'viewed_at']);
        $table->index(['session_id', 'blog_post_id', 'viewed_at']);
        $table->index(['ip_hash', 'user_agent_hash', 'blog_post_id', 'viewed_at']);
    });
}

function resetViewTrackingTableCache(): void
{
    $reflection = new ReflectionClass(BlogPostViewTrackingService::class);
    $property = $reflection->getProperty('viewsTableExists');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

describe('Public Blog View Tracking', function () {
    beforeEach(function () {
        ensureBlogPostViewsTableExists();
        resetViewTrackingTableCache();
    });

    test('does not fail post view page when views table is missing', function () {
        Schema::dropIfExists('blog_post_views');
        resetViewTrackingTableCache();

        $post = BlogPost::factory()->published()->create();

        $response = $this
            ->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $post->slug]));

        $response->assertOk();
    });

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

    test('deduplicates repeated views by matching ip and user agent when session changes', function () {
        $post = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', 'pest-fingerprint-agent')
            ->withCookie('laravel_session', 'session-one')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        $this->withHeader('User-Agent', 'pest-fingerprint-agent')
            ->withCookie('laravel_session', 'session-two')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->where('blog_post_id', $post->id)->count())->toBe(1);
    });

    test('dedupe is scoped per post and still counts different posts in same session window', function () {
        $firstPost = BlogPost::factory()->published()->create();
        $secondPost = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $firstPost->slug]))
            ->assertOk();

        $this->withHeader('User-Agent', 'pest-test-agent')
            ->get(route('blog.show', ['slug' => $secondPost->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->where('blog_post_id', $firstPost->id)->count())->toBe(1)
            ->and(DB::table('blog_post_views')->where('blog_post_id', $secondPost->id)->count())->toBe(1)
            ->and(DB::table('blog_post_views')->count())->toBe(2);
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
