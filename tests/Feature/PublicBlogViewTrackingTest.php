<?php

use App\Models\BlogPost;
use App\Models\BlogPostView;
use App\Models\User;
use App\Services\BlogPostViewTrackingService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    test('stores a hash of the session id instead of the raw value', function () {
        $post = BlogPost::factory()->published()->create();
        $rawSessionId = Str::random(40);

        $request = Request::create(route('blog.show', ['slug' => $post->slug]));
        $request->setLaravelSession(tap(
            Mockery::mock(Session::class),
            fn ($session) => $session->shouldReceive('getId')->andReturn($rawSessionId),
        ));

        app(BlogPostViewTrackingService::class)->trackView($post, $request);

        $stored = DB::table('blog_post_views')->value('session_id');

        expect($stored)->not->toBe($rawSessionId)
            ->and($stored)->toBe(hash_hmac('sha256', $rawSessionId, config('app.key')));
    });

    test('still records a view when the visitor has no session id, ip, or user agent', function () {
        $post = BlogPost::factory()->published()->create();

        // An unrelated, identifiable visitor viewed the post moments ago.
        DB::table('blog_post_views')->insert([
            'blog_post_id' => $post->id,
            'session_id' => 'other-visitor',
            'ip_hash' => 'other-ip',
            'user_agent_hash' => 'other-ua',
            'viewed_at' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create(route('blog.show', ['slug' => $post->slug]));
        $request->server->remove('REMOTE_ADDR');
        $request->headers->remove('User-Agent');
        $request->setLaravelSession(tap(
            Mockery::mock(Session::class),
            fn ($session) => $session->shouldReceive('getId')->andReturn(''),
        ));

        expect($request->ip())->toBeNull()
            ->and($request->userAgent())->toBeNull();

        app(BlogPostViewTrackingService::class)->trackView($post, $request);

        expect(DB::table('blog_post_views')->where('blog_post_id', $post->id)->count())->toBe(2);
    });

    test('does not record views from crawlers or scripted clients', function (string $userAgent) {
        $post = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', $userAgent)
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->count())->toBe(0);
    })->with([
        'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'facebook unfurler' => 'facebookexternalhit/1.1',
        'curl' => 'curl/8.4.0',
        'headless chrome' => 'Mozilla/5.0 HeadlessChrome/120.0',
    ]);

    test('does not record HEAD requests or browser prefetches', function () {
        $post = BlogPost::factory()->published()->create();
        $url = route('blog.show', ['slug' => $post->slug]);

        $this->withHeader('User-Agent', 'pest-test-agent')->call('HEAD', $url);
        $this->withHeader('User-Agent', 'pest-test-agent')
            ->withHeader('Sec-Purpose', 'prefetch')
            ->get($url)
            ->assertOk();

        expect(DB::table('blog_post_views')->count())->toBe(0);
    });

    test('still records views from regular browsers', function () {
        $post = BlogPost::factory()->published()->create();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/605.1.15')
            ->get(route('blog.show', ['slug' => $post->slug]))
            ->assertOk();

        expect(DB::table('blog_post_views')->count())->toBe(1);
    });

    test('keeps rendering the post when recording the view fails', function () {
        $post = BlogPost::factory()->published()->create();

        BlogPostView::creating(fn () => throw new RuntimeException('insert failed'));

        try {
            $this->withHeader('User-Agent', 'pest-test-agent')
                ->get(route('blog.show', ['slug' => $post->slug]))
                ->assertOk();
        } finally {
            BlogPostView::flushEventListeners();
        }

        expect(DB::table('blog_post_views')->count())->toBe(0);
    });
});
