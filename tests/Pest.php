<?php

/*
|--------------------------------------------------------------------------
| Test Constants
|--------------------------------------------------------------------------
|
| Common constants used across test files for consistency and maintainability.
|
*/

// Test IDs
const TEST_NONEXISTENT_ID = 99999;

// HTTP Status Codes
const HTTP_OK = 200;
const HTTP_UNAUTHORIZED = 401;
const HTTP_FORBIDDEN = 403;
const HTTP_NOT_FOUND = 404;

// Performance Thresholds
const MAX_QUERY_TIME_SECONDS = 3;
const MODERATE_QUERY_THRESHOLD = 20;
const COMPLEX_QUERY_THRESHOLD = 25;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)
    ->use(Tests\Traits\DatabaseAssertions::class)
    ->use(Tests\Traits\HttpTestHelpers::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Custom Expectations for HTTP Responses
|--------------------------------------------------------------------------
*/

/**
 * Assert response is successful (200) with specific Inertia component.
 */
expect()->extend('toBeSuccessfulInertiaResponse', function (string $component) {
    $this->value->assertStatus(200);
    $this->value->assertInertia(fn ($page) => $page->component($component));
    return $this;
});

/**
 * Assert response is unauthorized (401 or 403).
 */
expect()->extend('toBeUnauthorized', function () {
    expect($this->value->status())->toBeIn([401, 403]);
    return $this;
});

/**
 * Assert response is forbidden (403).
 */
expect()->extend('toBeForbidden', function () {
    $this->value->assertStatus(403);
    return $this;
});

/**
 * Assert response redirects to login route.
 */
expect()->extend('toRedirectToLogin', function () {
    $this->value->assertRedirect(route('login'));
    return $this;
});

/**
 * Assert response is not found (404).
 */
expect()->extend('toBeNotFound', function () {
    $this->value->assertNotFound();
    return $this;
});

/**
 * Assert response is rate limited (429).
 */
expect()->extend('toBeRateLimited', function () {
    $this->value->assertStatus(429);
    return $this;
});

/**
 * Assert response has validation errors for specific field.
 */
expect()->extend('toHaveValidationError', function (string $field) {
    $this->value->assertSessionHasErrors($field);
    return $this;
});

/**
 * Assert response has success message.
 */
expect()->extend('toHaveSuccessMessage', function (string $message) {
    $this->value->assertSessionHas('success', $message);
    return $this;
});

/**
 * Assert response has error message.
 */
expect()->extend('toHaveErrorMessage', function (string $message) {
    $this->value->assertSessionHas('error', $message);
    return $this;
});

/*
|--------------------------------------------------------------------------
| Domain-Specific Custom Expectations
|--------------------------------------------------------------------------
*/

/**
 * Assert array contains a valid blog post structure.
 */
expect()->extend('toBeValidBlogPost', function () {
    expect($this->value)
        ->toBeArray()
        ->toHaveKeys(['id', 'title', 'slug', 'excerpt', 'content', 'author', 'is_published', 'published_at']);
    return $this;
});

/**
 * Assert array matches expected blog post structure with type validation.
 */
expect()->extend('toHaveCorrectPostStructure', function () {
    $value = $this->value;
    
    expect($value)->toBeArray();
    expect($value['id'])->toBeInt();
    expect($value['title'])->toBeString();
    expect($value['slug'])->toBeString();
    expect($value['excerpt'])->toBeString();
    
    return $this;
});

/**
 * Assert user object has required role methods and properties.
 */
expect()->extend('toBeValidUser', function () {
    $user = $this->value;
    
    expect($user)->toBeInstanceOf(App\Models\User::class);
    expect($user->id)->not->toBeNull();
    expect($user->name)->not->toBeNull();
    expect($user->email)->not->toBeNull();
    expect($user->role)->not->toBeNull();
    
    return $this;
});

/**
 * Assert query count is below performance threshold.
 */
expect()->extend('toHaveEfficientQueryCount', function (int $threshold = MODERATE_QUERY_THRESHOLD) {
    $count = $this->value;
    
    expect($count)->toBeLessThan($threshold);
    
    return $this;
});

/**
 * Assert execution time is below performance threshold (in seconds).
 */
expect()->extend('toExecuteWithinTime', function (float $maxSeconds = MAX_QUERY_TIME_SECONDS) {
    $executionTime = $this->value;
    
    expect($executionTime)->toBeLessThan($maxSeconds);
    
    return $this;
});

/**
 * Assert blog post has required relationships loaded.
 */
expect()->extend('toHaveRelationshipsLoaded', function (array $relationships) {
    $model = $this->value;
    
    foreach ($relationships as $relationship) {
        expect($model->relationLoaded($relationship))->toBeTrue(
            "Expected relationship '{$relationship}' to be loaded"
        );
    }
    
    return $this;
});

/**
 * Assert comment belongs to a blog post and user.
 */
expect()->extend('toBeValidComment', function () {
    $comment = $this->value;
    
    expect($comment)->toBeInstanceOf(App\Models\Comment::class)
        ->and($comment->blog_post_id)->not->toBeNull()
        ->and($comment->user_id)->not->toBeNull()
        ->and($comment->content)->not->toBeEmpty();
    
    return $this;
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Helper to perform authenticated GET request.
 * Reduces boilerplate: $this->actingAs($user)->get($route)
 */
function authenticatedGet($user, string $route)
{
    return test()->actingAs($user)->get($route);
}

/**
 * Helper to perform authenticated POST request.
 */
function authenticatedPost($user, string $route, array $data = [])
{
    return test()->actingAs($user)->post($route, $data);
}

/**
 * Helper to perform authenticated PUT request.
 */
function authenticatedPut($user, string $route, array $data = [])
{
    return test()->actingAs($user)->put($route, $data);
}

/**
 * Helper to perform authenticated DELETE request.
 */
function authenticatedDelete($user, string $route)
{
    return test()->actingAs($user)->delete($route);
}

/**
 * Assert response has error message.
 */
expect()->extend('toHaveErrorMessage', function (string $message) {
    $this->value->assertSessionHas('error', $message);
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a test admin user.
 */
function createTestAdmin(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->admin()->withTwoFactor()->create($attributes);
}

/**
 * Create a test master admin user.
 */
function createTestMasterAdmin(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->masterAdmin()->withTwoFactor()->create($attributes);
}

/**
 * Create a test member user.
 */
function createTestMember(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->withTwoFactor()->create($attributes);
}

/**
 * Create a published blog post with an admin author.
 */
function createPublishedPost(array $attributes = []): App\Models\BlogPost
{
    if (!isset($attributes['user_id'])) {
        $attributes['user_id'] = createTestAdmin()->id;
    }
    
    return App\Models\BlogPost::factory()->published()->create($attributes);
}

/**
 * Create a draft blog post with an admin author.
 */
function createDraftPost(array $attributes = []): App\Models\BlogPost
{
    if (!isset($attributes['user_id'])) {
        $attributes['user_id'] = createTestAdmin()->id;
    }
    
    return App\Models\BlogPost::factory()->draft()->create($attributes);
}

/**
 * Create a comment on a blog post.
 */
function createComment(App\Models\BlogPost $post, ?App\Models\User $user = null, array $attributes = []): App\Models\Comment
{
    $attributes['blog_post_id'] = $post->id;
    $attributes['user_id'] = $user?->id ?? createTestMember()->id;
    
    return App\Models\Comment::factory()->create($attributes);
}

/*
|--------------------------------------------------------------------------
| Datasets
|--------------------------------------------------------------------------
|
| Datasets allow you to run the same test with different data inputs,
| reducing code duplication and improving test coverage.
|
*/

/**
 * All user role types.
 */
dataset('user_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
    'member' => fn() => createTestMember(),
]);

/**
 * Admin-level roles (admin and master_admin).
 */
dataset('admin_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
]);

/**
 * Non-admin roles (only members).
 */
dataset('non_admin_roles', [
    'member' => fn() => createTestMember(),
]);

/**
 * Unauthorized roles for master admin actions (admin and member).
 */
dataset('unauthorized_for_master_admin', [
    'regular admin' => fn() => createTestAdmin(),
    'member' => fn() => createTestMember(),
]);

/**
 * Invalid email addresses for validation testing.
 */
dataset('invalid_emails', [
    'missing @' => 'notanemail',
    'missing domain' => 'test@',
    'missing username' => '@example.com',
    'spaces' => 'test @example.com',
    'multiple @' => 'test@@example.com',
]);

/**
 * Valid roles for user creation.
 */
dataset('valid_user_roles', [
    'member',
    'admin',
    'master_admin',
]);

/**
 * Invalid password lengths (below 8 characters).
 */
dataset('invalid_passwords', [
    'empty' => '',
    'too short' => 'short',
    'single char' => 'a',
    '7 chars' => 'abcdefg',
]);
