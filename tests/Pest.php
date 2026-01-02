<?php

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
    return App\Models\User::factory()->admin()->create($attributes);
}

/**
 * Create a test master admin user.
 */
function createTestMasterAdmin(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->masterAdmin()->create($attributes);
}

/**
 * Create a test member user.
 */
function createTestMember(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->create($attributes);
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

dataset('user_roles', [
    'admin' => [fn() => createTestAdmin()],
    'master_admin' => [fn() => createTestMasterAdmin()],
    'member' => [fn() => createTestMember()],
]);

dataset('admin_roles', [
    'admin' => [fn() => createTestAdmin()],
    'master_admin' => [fn() => createTestMasterAdmin()],
]);

dataset('non_admin_roles', [
    'member' => [fn() => createTestMember()],
]);
