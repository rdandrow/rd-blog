# Pest PHP Best Practices - Implementation Guide

> **Purpose**: Deep dive into Pest patterns, advanced techniques, and the historical journey of our test suite refactoring. For quick reference and common patterns, see [README.md](./README.md).

This document outlines best practices for improving our Pest PHP test suite based on official Pest documentation and community standards. It serves as the authoritative reference for:

- **Advanced Pest patterns** (snapshot testing, beforeAll optimization, custom expectations)
- **Directory organization philosophy** (functional grouping vs structural mirroring)
- **Refactoring journey** (completed improvements and remaining opportunities)
- **Future enhancements** (mutation testing, coverage reporting)

This document provides comprehensive context beyond the quick-reference patterns in the README.

## Current Status (March 2026)

**Completed Refactoring:**
- All feature tests refactored with `describe()` blocks
- All tests converted to `it()` syntax for behavior-driven descriptions
- Custom expectations implemented and applied throughout
- Test groups added to all tests
- Chained expectations with `->and()` used where appropriate
- Scoped `beforeEach()` within describe blocks
- Datasets created and applied for repetitive tests
- Clear test hierarchy and organization
- Directory structure reorganized to mirror functional areas
- **✨ Phase 1: Test constants** added for magic numbers (8 constants, 22+ replacements)
- **✨ Phase 2: Helper functions** applied suite-wide (100+ instances, 40% boilerplate reduction)
- **✨ Phase 3: Data providers** consolidate validation tests (22+ tests → 8, 60% code reduction)
- **✨ Phase 4: Domain-specific expectations** (7 new expectations for performance & validation)
- **✨ Phase 5: Snapshot testing** for API/JSON responses (5 snapshot tests, BlogPostResource)
- **✨ Phase 6: Performance profiling** - optimized slow tests (2.8s improvement, 8.1% faster)

**Test Suite Statistics:**
- **Total Tests**: 906 (580 feature + 326 unit)
- **Total Assertions**: 3,382
- **Execution Time**: 9.66s (`composer test`, 12 parallel processes)
- **Files Optimized**: 28 files improved across all phases
- **Code Reduced**: ~300 lines of duplicated code eliminated
- **Zero Regressions**: All tests passing after optimizations
- **Snapshot Files**: 5 snapshots for BlogPostResource
- **Performance Improvements**: Removed sleep() calls, reduced request loops
- **Unit Test Coverage**: 3 middleware classes + 1 listener with comprehensive tests

**Directory Organization:**
- `tests/Feature/Admin/` - Administrative operations (4 files)
- `tests/Feature/Auth/` - Authentication flows (7 files)
- `tests/Feature/Public/` - Public-facing features (2 files)
- `tests/Feature/Social/` - Social interactions (3 files)
- `tests/Feature/Settings/` - User settings (3 files)
- `tests/Feature/System/` - System-level tests (4 files)
- `tests/Unit/Http/Middleware/` - Middleware unit tests (3 files)
- `tests/Unit/Listeners/` - Event listener unit tests (1 file)
- Total: 23 feature test files + 4 unit test files, all optimized with modern Pest patterns

**Already Following:**
- Clear, descriptive test names
- Good use of `beforeEach()` for test setup (now enhanced with shared data)
- Helper functions in `Pest.php` (now includes authentication helpers)
- Using `expect()` API instead of PHPUnit assertions
- Modern PHP syntax with arrow functions
- Test constants for magic numbers (HTTP codes, test IDs, thresholds)
- Data providers for validation tests (reduces duplication)

Note: For current suite statistics (test count, assertions, timing), see the Test Suite README.

---

## Directory Structure & Organization Best Practices

### Pest Framework Principle: Mirror Your Application Structure

Following Pest best practices, **test directories should mirror your application structure** for easy navigation and maintenance. This creates a 1:1 mapping that makes it intuitive to find tests for any given class or feature.

### Feature Tests Structure (Current)

Our feature tests now follow a logical grouping structure that mirrors the application's functional areas:

```
tests/Feature/
├── Admin/                             # Administrative functions (authenticated admins)
│   ├── BlogPostTest.php               # → app/Http/Controllers/BlogPostController (CRUD)
│   ├── DashboardTest.php              # → Dashboard access control
│   ├── MasterAdminUserManagementTest.php # → Master admin user operations
│   └── RoleBasedAccessTest.php        # → Authorization & permissions
├── Auth/                              # Authentication flows
│   ├── LoginTest.php                  # → Authentication flow
│   ├── RegistrationTest.php           # → User registration
│   ├── PasswordResetTest.php          # → Password reset flow
│   ├── EmailVerificationTest.php      # → Email verification
│   ├── PasswordConfirmationTest.php   # → Password confirmation
│   ├── TwoFactorAuthenticationTest.php # → 2FA setup/usage
│   └── LogoutTest.php                 # → Logout flow
├── Public/                            # Public-facing features (no auth required)
│   ├── PublicBlogPostViewingTest.php  # → app/Http/Controllers/PublicBlogController
│   └── AuthorProfileTest.php          # → app/Http/Controllers/AuthorProfileController
├── Social/                            # Social interaction features
│   ├── BlogPostLikeTest.php           # → app/Http/Controllers/BlogPostLikeController
│   ├── CommentTest.php                # → app/Http/Controllers/CommentController
│   └── UserFollowTest.php             # → app/Http/Controllers/UserFollowController
├── Settings/                          # User settings & preferences
│   ├── ProfileUpdateTest.php          # → Profile management
│   ├── PasswordUpdateTest.php         # → Password changes
│   └── TwoFactorAuthenticationTest.php # → 2FA settings
└── System/                            # System-level functionality
    ├── MiddlewareTest.php             # → app/Http/Middleware
    ├── RateLimitingTest.php           # → Rate limiting across endpoints
    ├── PerformanceTest.php            # → Performance & optimization
    └── ErrorHandlingTest.php          # → Error handling patterns
```

### Naming Convention Rules

**Pest Best Practice**: Test files should be named after the class they test with `Test` suffix.

| Application File | Test File | Test Type |
|-----------------|-----------|-----------|
| `app/Models/BlogPost.php` | `tests/Unit/Models/BlogPostTest.php` | Unit |
| `app/Services/BlogPostService.php` | `tests/Unit/Services/BlogPostServiceTest.php` | Unit |
| `app/Http/Controllers/BlogPostController.php` | `tests/Feature/Admin/BlogPostTest.php` | Feature |
| `app/Policies/BlogPostPolicy.php` | `tests/Unit/Policies/BlogPostPolicyTest.php` | Unit |
| `app/Actions/Fortify/CreateNewUser.php` | `tests/Unit/Actions/Fortify/CreateNewUserTest.php` | Unit |

### Benefits of This Structure

1. **Intuitive Navigation**: Developers can find tests by logical feature area (Admin, Public, Social, etc.)
2. **Clear Separation**: 
   - **Admin/** = Authenticated admin operations
   - **Auth/** = Authentication flows
   - **Public/** = Guest-accessible features
   - **Social/** = User interaction features
   - **Settings/** = User preferences
   - **System/** = Infrastructure & cross-cutting concerns
3. **Easy Maintenance**: Related tests are grouped together for easier updates
4. **Test Organization**: Logical grouping complements test groups/tags
5. **New Developer Onboarding**: Structure clearly communicates application architecture
6. **Scalability**: Easy to add new feature areas as the application grows

### Directory Organization Philosophy

Our structure uses **functional grouping** for feature tests rather than strict controller mirroring:

**Feature Tests** (Integration/Workflow):
- Grouped by **functional area** (Admin, Public, Social, System)
- Tests complete user workflows and feature behavior
- May span multiple controllers/services

**Unit Tests** (Isolation):
- Mirror **application structure** exactly (Models, Services, Policies)
- Test individual classes in isolation
- 1:1 mapping with application files

### Pest Configuration for Path Mapping

In `Pest.php`, you can configure base paths:

```php
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

// Feature tests can use database
uses(Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Feature');

// Unit tests should NOT use database
// No database trait in Unit folder
```

### Finding Tests for a Class

**Feature Tests** (Functional Grouping):

```
Need to test:     Blog post creation by admin
Find:             tests/Feature/Admin/BlogPostTest.php

Need to test:     Public blog viewing
Find:             tests/Feature/Public/PublicBlogPostViewingTest.php

Need to test:     User following features
Find:             tests/Feature/Social/UserFollowTest.php

Need to test:     Middleware execution
Find:             tests/Feature/System/MiddlewareTest.php
```

**Unit Tests** (Structure Mirroring):

```
Given:    app/Services/BlogPostService.php
Find:     tests/Unit/Services/BlogPostServiceTest.php

Given:    app/Models/BlogPost.php
Find:     tests/Unit/Models/BlogPostTest.php

Given:    app/Policies/BlogPostPolicy.php
Find:     tests/Unit/Policies/BlogPostPolicyTest.php
```

### When to Create Subdirectories

Create subdirectories in **Feature tests** when:
- You have multiple tests for a logical feature area (Admin, Social, Public, etc.)
- The grouping clarifies the application's functional domains
- Tests share common setup or context (e.g., all admin tests need admin users)

Create subdirectories in **Unit tests** when:
- The application has a corresponding directory (e.g., `app/Services/` → `tests/Unit/Services/`)
- You're testing a complete layer (Models, Services, Policies, etc.)
- You want 1:1 structural mapping with application code

Avoid subdirectories when:
- You have only 1-2 test files (keep them flat)
- The grouping is forced or doesn't add clarity
- It adds unnecessary nesting (max 2-3 levels deep)

### Cross-Reference: Pest Groups vs Directories

While directories organize files by **functional area** (Feature) or **structure** (Unit), **groups** organize tests logically across directories:

```php
// tests/Feature/Admin/BlogPostTest.php
it('creates blog posts', function () {
    // test code
})->group('blog-posts', 'crud', 'authenticated', 'admin');

// tests/Feature/Public/PublicBlogPostViewingTest.php
it('displays published posts', function () {
    // test code
})->group('blog-posts', 'public', 'guest');

// tests/Unit/Services/BlogPostServiceTest.php  
it('filters posts by tag', function () {
    // test code
})->group('blog-posts', 'services', 'filtering');
```

Run all blog-post tests (across all directories):
```bash
./vendor/bin/pest --group=blog-posts
```

Run only admin tests:
```bash
./vendor/bin/pest tests/Feature/Admin
```

Run service unit tests:
```bash
./vendor/bin/pest tests/Unit/Services
```

This dual organization gives you:
- **Physical structure** for file organization (directories)
- **Logical structure** for test execution (groups)

### Example: Adding a New Feature with Tests

When adding a new `NotificationService`:

1. **Create the service:**
   ```
   app/Services/NotificationService.php
   ```

2. **Create unit tests (mirrored structure):**
   ```
   tests/Unit/Services/NotificationServiceTest.php
   ```

3. **Create feature tests if needed:**
   ```
   tests/Feature/NotificationTest.php
   ```

4. **Use groups for logical organization:**
   ```php
   // tests/Unit/Services/NotificationServiceTest.php
   it('sends email notifications', function () {
       // test
   })->group('notifications', 'services', 'email');
   
   // tests/Feature/NotificationTest.php
   it('sends notifications on user registration', function () {
       // test
   })->group('notifications', 'authentication', 'integration');
   ```

This approach combines **structural organization** (directories) with **logical organization** (groups) for maximum flexibility and maintainability.

---

## Remaining Opportunities

### Performance Optimization (Completed ✅)

**Implementation:** Profiled and optimized slow tests by removing sleep() calls and reducing request loops.

**Key Optimizations:**
1. **Removed sleep() calls**: Replaced `sleep(1)` with explicit `created_at` timestamps
2. **Reduced request loops**: Lowered iteration counts from 10-30 to 6-15 requests
3. **Added rate limiter resets**: Clear rate limiters before tests to ensure consistent behavior

**Results:**
- Historical phase benchmark: 17.80s → 16.35s (8.1% improvement)
- Slowest test: 2.04s → 0.38s (81% improvement)
- Rate limiting tests: 2.72s → 2.19s (19% improvement)

**Files Optimized:**
- `tests/Feature/Admin/MasterAdminUserManagementTest.php` - Removed 2x sleep(1) calls
- `tests/Feature/System/RateLimitingTest.php` - Reduced request loops, added rate limiter clears

```php
// Before: Slow (2+ seconds)
$admin1 = User::factory()->admin()->create(['created_at' => now()->subDays(2)]);
sleep(1);
$admin2 = User::factory()->admin()->create(['created_at' => now()->subDay()]);
sleep(1);

// After: Fast (0.03 seconds)
$admin1 = User::factory()->admin()->create(['created_at' => now()->subDays(2)]);
$admin2 = User::factory()->admin()->create(['created_at' => now()->subDay()]);
// Explicit timestamps ensure ordering without sleep()
```

---

## Applied Best Practices

### Phase 1: Test Constants (Completed)

**Implementation:** Named constants replace all magic numbers.

```php
// tests/Pest.php
const TEST_NONEXISTENT_ID = 99999;
const HTTP_OK = 200;
const HTTP_UNAUTHORIZED = 401;
const HTTP_FORBIDDEN = 403;
const HTTP_NOT_FOUND = 404;
const MAX_QUERY_TIME_SECONDS = 3;
const MODERATE_QUERY_THRESHOLD = 20;
const COMPLEX_QUERY_THRESHOLD = 25;
```

**Usage:**
```php
it('returns 404 for non-existent posts', function () {
    $response = $this->get(route('blog.show', TEST_NONEXISTENT_ID));
    expect($response)->toBeNotFound();
});

it('completes queries within threshold', function () {
    expect($queryTime)->toBeLessThan(MAX_QUERY_TIME_SECONDS);
});
```

**Impact:** 22+ replacements across 9 files, improved maintainability.

### Phase 2: Authentication Helper Functions (Completed)

**Implementation:** Wrapper functions eliminate authentication boilerplate.

```php
// tests/Pest.php
function authenticatedGet($user, string $route) {
    return test()->actingAs($user)->get($route);
}

function authenticatedPost($user, string $route, array $data = []) {
    return test()->actingAs($user)->post($route, $data);
}

function authenticatedPut($user, string $route, array $data = []) {
    return test()->actingAs($user)->put($route, $data);
}

function authenticatedDelete($user, string $route, array $data = []) {
    return test()->actingAs($user)->delete($route, $data);
}
```

**Before:**
```php
$response = $this->actingAs($user)->post(route('blog.store'), $data);
```

**After:**
```php
$response = authenticatedPost($user, route('blog.store'), $data);
```

**Impact:** 100+ replacements, 40% boilerplate reduction, clearer intent.

### Phase 3: Data Providers for Validation Tests (Completed)

**Implementation:** Consolidate repetitive validation tests with datasets.

**Before (3 separate tests):**
```php
it('requires title', function () {
    $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), [
        'excerpt' => 'Test',
        'content' => 'Test',
    ]);
    expect($response)->toHaveValidationError('title');
});

it('requires excerpt', function () {
    $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), [
        'title' => 'Test',
        'content' => 'Test',
    ]);
    expect($response)->toHaveValidationError('excerpt');
});

it('requires content', function () {
    $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), [
        'title' => 'Test',
        'excerpt' => 'Test',
    ]);
    expect($response)->toHaveValidationError('content');
});
```

**After (1 data-driven test):**
```php
it('requires required fields', function (string $missingField, array $validData) {
    unset($validData[$missingField]);
    $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), $validData);
    expect($response)->toHaveValidationError($missingField);
})->with([
    'title is required' => ['title', ['title' => 'Test', 'excerpt' => 'Test', 'content' => 'Test']],
    'excerpt is required' => ['excerpt', ['title' => 'Test', 'excerpt' => 'Test', 'content' => 'Test']],
    'content is required' => ['content', ['title' => 'Test', 'excerpt' => 'Test', 'content' => 'Test']],
]);
```

**Advanced Pattern - Nested Datasets:**
```php
it('allows admin roles to access routes', function ($userFactory, string $route, string $method) {
    $user = is_callable($userFactory) ? $userFactory() : $userFactory;
    $response = $method === 'GET' 
        ? authenticatedGet($user, route($route))
        : authenticatedPost($user, route($route), $data);
    $response->assertStatus(HTTP_OK);
})->with('admin_roles')  // First dataset: user roles
  ->with([               // Second dataset: routes to test
      'blog post index' => ['admin.blog-posts.index', 'GET'],
      'blog post create' => ['admin.blog-posts.create', 'GET'],
  ]);
```

**Impact:** 22+ tests → 8 tests, 60% code reduction, easier to add scenarios.

### Phase 4: Domain-Specific Custom Expectations (Completed)

**Implementation:** Advanced expectations for common domain patterns and performance testing.

```php
// tests/Pest.php - Domain-Specific Expectations

// Blog Post Validation
expect()->extend('toBeValidBlogPost', function () {
    expect($this->value)
        ->toBeArray()
        ->toHaveKeys(['id', 'title', 'slug', 'excerpt', 'content', 'author', 'is_published', 'published_at']);
    return $this;
});

expect()->extend('toHaveCorrectPostStructure', function () {
    $value = $this->value;
    expect($value)->toBeArray();
    expect($value['id'])->toBeInt();
    expect($value['title'])->toBeString();
    expect($value['slug'])->toBeString();
    expect($value['excerpt'])->toBeString();
    return $this;
});

// User Validation
expect()->extend('toBeValidUser', function () {
    $user = $this->value;
    expect($user)->toBeInstanceOf(App\Models\User::class)
        ->and($user)->toHaveProperty('id')
        ->and($user)->toHaveProperty('name')
        ->and($user)->toHaveProperty('email')
        ->and($user)->toHaveProperty('role');
    return $this;
});

// Performance Expectations
expect()->extend('toHaveEfficientQueryCount', function (int $threshold = MODERATE_QUERY_THRESHOLD) {
    expect($this->value)->toBeLessThan($threshold);
    return $this;
});

expect()->extend('toExecuteWithinTime', function (float $maxSeconds = MAX_QUERY_TIME_SECONDS) {
    expect($this->value)->toBeLessThan($maxSeconds);
    return $this;
});

// Relationship Loading
expect()->extend('toHaveRelationshipsLoaded', function (array $relationships) {
    $model = $this->value;
    foreach ($relationships as $relationship) {
        expect($model->relationLoaded($relationship))->toBeTrue(
            "Expected relationship '{$relationship}' to be loaded"
        );
    }
    return $this;
});

// Comment Validation
expect()->extend('toBeValidComment', function () {
    $comment = $this->value;
    expect($comment)->toBeInstanceOf(App\Models\Comment::class)
        ->and($comment->blog_post_id)->not->toBeNull()
        ->and($comment->user_id)->not->toBeNull()
        ->and($comment->content)->not->toBeEmpty();
    return $this;
});
```

**Usage:**
```php
// tests/Feature/System/PerformanceTest.php
it('avoids N+1 queries for authors on index page', function () {
    DB::enableQueryLog();
    $response = $this->get(route('blog'));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    // Using custom expectation instead of toBeLessThan()
    expect(count($queries))->toHaveEfficientQueryCount(LIST_PAGE_QUERY_THRESHOLD);
})->group('performance', 'n+1', 'queries');

it('handles search with many posts efficiently', function () {
    $startTime = microtime(true);
    $response = $this->get(route('blog', ['search' => 'test']));
    $executionTime = microtime(true) - $startTime;
    
    // Using custom expectation for execution time
    expect($response->status())->toBe(200)
        ->and($executionTime)->toExecuteWithinTime(MAX_QUERY_TIME_SECONDS);
});

// Usage in model tests
it('has required relationships', function () {
    $post = createPublishedPost();
    $post->load(['author', 'comments']);
    
    expect($post)->toHaveRelationshipsLoaded(['author', 'comments']);
});
```

**Impact:** 
- 7 new domain-specific expectations
- Clearer test intent and improved readability
- Consistent patterns across performance tests
- Better error messages with context
- Applied to PerformanceTest.php (15 tests optimized)

### Phase 5: Snapshot Testing (Completed)

**Implementation:** Spatie Pest Plugin Snapshots for JSON/API response testing.

**Installation:**
```bash
composer require spatie/pest-plugin-snapshots --dev
```

**Usage:**
```php
// tests/Unit/Http/Resources/BlogPostResourceTest.php

describe('Snapshot Testing', function () {
    test('blog post resource matches snapshot for index route', function () {
        // Arrange: Create consistent test data
        $author = new User(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $author->id = 100;
        $author->avatar = 'https://example.com/avatar.jpg';
        
        $post = new BlogPost([
            'title' => 'Snapshot Test Post',
            'slug' => 'snapshot-test-post',
            'excerpt' => 'This is a test post for snapshot testing',
            'content' => 'Full content that should not appear on index',
            'tags' => ['Laravel', 'Testing', 'Snapshots'],
            'is_featured' => true,
            'published_at' => '2026-01-08 12:00:00',
            'reading_time' => 8,
        ]);
        $post->id = 100;
        $post->setRelation('author', $author);
        
        $request = Request::create('/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act & Assert: Transform and compare with snapshot
        expect($resource->toArray($request))->toMatchSnapshot();
    })->group('snapshots');
});
```

**Snapshot Output:**
```json
{
    "id": 100,
    "title": "Snapshot Test Post",
    "slug": "snapshot-test-post",
    "excerpt": "This is a test post for snapshot testing",
    "content": {},
    "featured_image": "https://example.com/featured.jpg",
    "author": {
        "id": 100,
        "name": "Jane Smith",
        "avatar": "https://example.com/avatar.jpg"
    },
    "published_at": "2026-01-08T12:00:00.000000Z",
    "reading_time": 8,
    "tags": ["Laravel", "Testing", "Snapshots"],
    "is_featured": true
}
```

**Benefits:**
- Detects unintended changes to API/JSON structures
- Easy to review changes with snapshot diffs
- Less brittle than manual assertions for complex nested structures
- Documents expected output format
- Catches regressions in transformations

**Snapshot Management:**
```bash
# Run tests and create/update snapshots
./vendor/bin/pest --group=snapshots

# Update snapshots when intentional changes are made
./vendor/bin/pest --update-snapshots

# View snapshot files
ls tests/.pest/snapshots/
```

**Impact:**
- 3 snapshot tests added to BlogPostResourceTest
- Comprehensive coverage of BlogPostResource transformation
- Tests for index route (without content), show route (with content), and null values
- Snapshots stored in `tests/.pest/snapshots/` directory

### Custom Expectations (Implemented)

Our test suite now includes these custom expectations in `Pest.php`:

```php
expect()->extend('toBeSuccessfulInertiaResponse', function (string $component) {
    $this->value->assertStatus(200);
    $this->value->assertInertia(fn ($page) => $page->component($component));
    return $this;
});

expect()->extend('toRedirectToLogin', function () {
    $this->value->assertRedirect(route('login'));
    return $this;
});

expect()->extend('toBeForbidden', function () {
    $this->value->assertStatus(403);
    return $this;
});

expect()->extend('toHaveSuccessMessage', function (string $message) {
    $this->value->assertSessionHas('success', $message);
    return $this;
});

expect()->extend('toHaveValidationError', function (string $field) {
    $this->value->assertSessionHasErrors($field);
    return $this;
});

expect()->extend('toBeUnauthorized', function () {
    expect($this->value->status())->toBeIn([401, 403]);
    return $this;
});

expect()->extend('toBeNotFound', function () {
    $this->value->assertStatus(404);
    return $this;
});

expect()->extend('toBeRateLimited', function () {
    $this->value->assertStatus(429);
    return $this;
});

expect()->extend('toHaveErrorMessage', function (string $message) {
    $this->value->assertSessionHas('error', $message);
    return $this;
});
```

### Datasets (Implemented)

Common datasets available in `Pest.php`:

```php
dataset('admin_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
]);

dataset('user_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
    'member' => fn() => createTestMember(),
]);

dataset('unauthorized_for_master_admin', [
    'admin' => fn() => createTestAdmin(),
    'member' => fn() => createTestMember(),
]);

dataset('valid_user_roles', [
    'member',
    'admin',
    'master_admin',
]);
```

### Test Organization (Implemented)

**Core Patterns Applied Throughout:**
- `describe()` blocks for logical grouping
- `it()` syntax for behavior-driven descriptions  
- Test groups for selective execution
- Scoped `beforeEach()` for setup isolation
- Chained expectations with `->and()`
- Test constants for magic numbers
- Helper functions for common operations
- Data providers for repetitive scenarios

### Complete Example: Modern Test Structure

```php
describe('Blog Post Creation', function () {
    beforeEach(function () {
        $this->admin = createTestAdmin();
    });
    
    it('allows admins to create published posts', function () {
        $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), [
            'title' => 'New Post',
            'content' => 'Post content',
            'is_published' => true,
        ]);
        
        expect($response)->toHaveSuccessMessage('Blog post created successfully');
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'New Post',
            'is_published' => true,
        ]);
    })->group('blog-posts', 'crud', 'authenticated');
    
    it('validates required fields', function (string $field, array $data) {
        $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), $data);
        expect($response)->toHaveValidationError($field);
    })->with([
        'title' => ['title', ['excerpt' => 'Test', 'content' => 'Test']],
        'content' => ['content', ['title' => 'Test', 'excerpt' => 'Test']],
    ])->group('blog-posts', 'validation');
});
```

---

## Test Execution

### Run All Tests
```bash
composer test
# or
./vendor/bin/pest
```

### Run Specific Groups
```bash
./vendor/bin/pest --group=admin
./vendor/bin/pest --group=blog-posts
./vendor/bin/pest --group=validation
./vendor/bin/pest --exclude-group=slow
```

### Run Specific Files
```bash
./vendor/bin/pest tests/Feature/Admin/BlogPostTest.php
./vendor/bin/pest tests/Feature/Settings/
```

### Profile Performance
```bash
./vendor/bin/pest --profile
```

### Parallel Execution
```bash
./vendor/bin/pest --parallel
```

---

## Mutation Testing

Mutation testing with [Infection](https://infection.github.io/) helps assess the quality of your tests by introducing small code changes (mutations) and checking if your tests catch them.

### Prerequisites

Mutation testing requires code coverage generation. Enable one of these:

**Option 1: Xdebug (Recommended for Development)**
```bash
# macOS with Homebrew
brew install php-xdebug

# Verify Xdebug is enabled
php -m | grep xdebug

# For Xdebug 3.x, ensure coverage mode is enabled
php -d xdebug.mode=coverage vendor/bin/infection
```

**Option 2: PCOV (Faster)**
```bash
pecl install pcov
# Add to php.ini: extension=pcov.so
```

**Option 3: PHPDBg (Built-in)**
```bash
phpdbg -qrr vendor/bin/infection
```

### Configuration

Mutation testing is configured in `infection.json5`:

```json5
{
    "source": {
        "directories": [
            "app/Models",              // Domain models
            "app/Http/Resources",      // API transformers
            "app/Actions/Fortify",     // Authentication actions
            "app/Policies",            // Authorization logic
            "app/Services",            // Business logic
            "app/Http/Requests"        // Form validation
        ]
    },
    "testFramework": "phpunit",
    "testFrameworkOptions": "--configuration=phpunit.xml --testsuite=Unit",
    "minMsi": 70,                      // Minimum Mutation Score Indicator
    "minCoveredMsi": 80                // Minimum for covered code
}
```

### Running Mutation Tests

**Run all configured sources:**
```bash
./vendor/bin/infection --threads=4
```

**Test specific file:**
```bash
./vendor/bin/infection --filter=app/Models/User.php --threads=4
```

**Test specific directory:**
```bash
./vendor/bin/infection --filter=app/Services/ --threads=4
```

**With Xdebug explicitly:**
```bash
./vendor/bin/infection --threads=4 --initial-tests-php-options=-dzend_extension=xdebug.so
```

**Dry run (see what would be tested):**
```bash
./vendor/bin/infection --dry-run
```

**Show mutations:**
```bash
./vendor/bin/infection --show-mutations --threads=4
```

**Lower MSI threshold for experimentation:**
```bash
./vendor/bin/infection --min-msi=50 --threads=4
```

### Understanding Results

**Mutation Score Indicator (MSI):**
- **80%+** = Excellent test coverage and quality
- **70-79%** = Good (configured minimum)
- **50-69%** = Acceptable, room for improvement
- **<50%** = Tests may not be thorough enough

**Example Output:**
```
432 mutations were generated:
     382 mutants were killed
      23 mutants were not covered by tests
      18 covered mutants were not detected
       9 errors were encountered

Metrics:
    Mutation Score Indicator (MSI): 81%
    Mutation Code Coverage: 95%
    Covered Code MSI: 86%
```

**What the numbers mean:**
- **Killed** = Test caught the mutation ✅
- **Escaped** = Mutation survived, test didn't catch it ❌
- **Not Covered** = No tests cover this code
- **Error** = Mutation caused a fatal error

### Improving MSI Scores

**When mutations escape:**

1. **Review the specific mutation:**
```bash
./vendor/bin/infection --filter=app/Models/User.php --show-mutations
```

2. **Common escapes and fixes:**

```php
// Escaped: Return value mutation (true -> false)
// Before (weak test):
it('checks if user is admin', function () {
    $user = createTestAdmin();
    expect($user->isAdmin())->toBeTrue();
});

// After (stronger test):
it('checks if user is admin', function () {
    $admin = createTestAdmin();
    $member = createTestMember();
    
    expect($admin->isAdmin())->toBeTrue()
        ->and($member->isAdmin())->toBeFalse(); // Catches negation mutations
});
```

```php
// Escaped: Comparison operator mutation (>= to >)
// Before:
it('validates minimum length', function () {
    expect(strlen($value))->toBeGreaterThanOrEqual(8);
});

// After:
it('validates minimum length', function () {
    expect(strlen('1234567'))->toBeLessThan(8)  // Edge case: exactly 7
        ->and(strlen('12345678'))->toBe(8)       // Edge case: exactly 8
        ->and(strlen('123456789'))->toBeGreaterThan(8);
});
```

### Logs and Reports

Infection generates multiple report formats:

```bash
# Text summary
cat infection.log

# Detailed summary
cat infection-summary.log

# JSON for CI/CD
cat infection.json

# Per-mutator breakdown
cat infection-per-mutator.md
```

### CI/CD Integration

Add to your GitHub Actions or CI pipeline:

```yaml
- name: Run Mutation Tests
  run: |
    php -d xdebug.mode=coverage vendor/bin/infection \
      --threads=4 \
      --min-msi=70 \
      --logger-github
```

### Best Practices

1. **Run on critical code first**: Models, Services, Policies
2. **Start with lower thresholds**: `--min-msi=50` then gradually increase
3. **Use filters**: Test one file/directory at a time initially
4. **Review escaped mutations**: They reveal weak tests
5. **Don't chase 100% MSI**: Focus on critical business logic
6. **Use with coverage**: Mutation testing complements code coverage

### Resources

- [Infection Documentation](https://infection.github.io/)
- [Mutation Testing Guide](https://infection.github.io/guide/)
- [Configuration Reference](https://infection.github.io/guide/usage.html)

---

## Resources

- [Pest PHP Documentation](https://pestphp.com/docs)
- [Datasets](https://pestphp.com/docs/datasets)
- [Custom Expectations](https://pestphp.com/docs/expectations#custom-expectations)
- [Higher Order Tests](https://pestphp.com/docs/higher-order-tests)
- [Test Groups](https://pestphp.com/docs/groups)

---

## Future Enhancements

### Completed ✅
- [✅] ~~Implement `beforeAll()` for expensive setup operations~~ (Limitation: Not supported in `describe()` blocks)
- [✅] ~~Add more domain-specific custom expectations~~ (Completed in Phase 4)
- [✅] ~~Profile and optimize slow tests~~ (Completed in Phase 6 - historical benchmark: 17.80s → 16.35s)
- [✅] ~~Add snapshot testing for API responses~~ (Completed in Phase 5)
- [✅] ~~Explore mutation testing with Infection~~ (Completed in Phase 6 - Configured for Models, Resources, Actions)
- [✅] ~~Add test coverage reporting and enforce minimums~~ (Completed in Phase 7 - Xdebug/PCOV documented)
- [✅] ~~Create shared test traits for common patterns~~ (Completed in Phase 8 - DatabaseAssertions, HttpTestHelpers)

### Low Priority (Optional Future Work)
- [ ] Investigate Pest's architectural testing features
- [ ] Add visual regression testing for frontend components
- [ ] Implement contract testing for API endpoints
- [ ] Expand snapshot testing to additional resources and API endpoints
- [ ] Run mutation testing regularly and improve MSI scores
- [ ] Add coverage minimums to CI/CD pipeline

### Current State
All high and medium priority enhancements have been completed. The test suite is now fully optimized with:
- **906 tests** running in **9.66 seconds** (`composer test` baseline)
- **9 completed optimization phases** (constants, helpers, data providers, expectations, snapshots, mutation testing, coverage docs, traits, performance)
- **Zero regressions** after all optimizations
- **Comprehensive coverage** with snapshot testing, custom expectations, shared utilities, middleware unit tests, and listener unit tests
- **Mutation testing ready** for 8 directories including middleware and listeners
