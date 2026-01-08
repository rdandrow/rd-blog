# Test Suite Documentation

This document provides a comprehensive overview of the test suite for the RD Blog application.

## Test Statistics

- **Total Tests**: 700+ (422 feature + 216 unit, growing)
- **Total Assertions**: 2,675+
- **Execution Time**: ~4.50 seconds (parallel execution)
- **Parallel Processes**: 12
- **Test Framework**: Pest PHP 4.1 (built on PHPUnit 11.x)
- **Last Updated**: January 2026

## Test Architecture

This test suite uses **Pest PHP 4.1** for expressive, behavior-driven testing with **PHPUnit 11.x** as the underlying framework.

### Testing Philosophy

**Feature Tests** (Integration/Workflow):
- Test complete user workflows spanning multiple components
- Use real dependencies (database, filesystem with fakes)
- Verify HTTP requests, responses, and side effects
- Grouped by functional area (Admin, Auth, Public, Social, System)
- Slower execution (seconds) but comprehensive coverage

**Unit Tests** (Isolation):
- Test individual classes/methods in complete isolation
- Mock all external dependencies (no database, filesystem, network)
- Very fast execution (milliseconds)
- Mirror application structure exactly (Models, Services, Policies)
- Focus on business logic and algorithms

### Pest Best Practices Applied

All tests follow modern Pest patterns:
- ✅ **describe() blocks** - Logical test organization
- ✅ **it() syntax** - Behavior-driven descriptions
- ✅ **Custom expectations** - Domain-specific assertions
- ✅ **Test groups** - Selective execution
- ✅ **Chained expectations** - Use `->and()` for related assertions
- ✅ **Datasets** - Reduce test duplication
- ✅ **Scoped beforeEach()** - Setup isolation per describe block

## Test Organization

### Feature Tests (`tests/Feature/`)

Feature tests verify complete user-facing functionality including HTTP requests, database interactions, and integration between components.

#### Authentication & Authorization

##### `Auth/AuthenticationTest.php`
- **Purpose**: User login/logout functionality
- **Structure**: 2 describe blocks (Login, Logout)
- **Tests**: 6 tests covering login screen, authentication, logout, rate limiting
- **Groups**: `auth`, `login`, `logout`, `guest`, `authenticated`
- **Key Features**: Valid/invalid credentials, remember me, 2FA integration

##### `Auth/RegistrationTest.php`
- **Purpose**: User registration and account creation
- **Structure**: 1 describe block (Registration)
- **Tests**: 2 tests covering registration form and process
- **Groups**: `auth`, `registration`, `guest`
- **Key Features**: Email uniqueness, password validation, mandatory 2FA setup

##### `Auth/TwoFactorChallengeTest.php`
- **Purpose**: Two-factor authentication challenge process
- **Structure**: 1 describe block (Two-Factor Challenge)
- **Tests**: 2 tests covering challenge screen and code validation
- **Groups**: `auth`, `2fa`
- **Key Features**: TOTP validation, recovery code authentication

##### `Auth/PasswordResetTest.php`
- **Purpose**: Password reset via email
- **Structure**: 2 describe blocks (Password Reset Request, Password Reset)
- **Tests**: 5 tests covering reset link generation, token validation, password update
- **Groups**: `auth`, `password-reset`, `guest`
- **Key Features**: Email notifications, signed URLs, token expiration

##### `Auth/EmailVerificationTest.php`
- **Purpose**: Email verification process
- **Structure**: 1 describe block (Email Verification)
- **Tests**: 6 tests covering verification links and already verified handling
- **Groups**: `auth`, `email-verification`
- **Key Features**: Signed URL validation, verification events

##### `Auth/PasswordConfirmationTest.php`
- **Purpose**: Password confirmation for sensitive operations
- **Structure**: 1 describe block (Password Confirmation)
- **Tests**: 2 tests covering confirmation screen and validation
- **Groups**: `auth`, `password-confirmation`
- **Key Features**: Recent password verification requirement

##### `Auth/VerificationNotificationTest.php`
- **Purpose**: Email verification notification sending
- **Structure**: 1 describe block (Email Verification Notification)
- **Tests**: 2 tests covering notification dispatch and resend
- **Groups**: `auth`, `email-verification`
- **Key Features**: Duplicate prevention, rate limiting

#### User & Role Management (`tests/Feature/Admin/`)

##### `Admin/MasterAdminUserManagementTest.php` ⭐
- **Purpose**: Comprehensive user management (master admin exclusive)
- **Structure**: 6 describe blocks
  - Access Control (8 tests)
  - User Listing (6 tests)
  - User Creation (20 tests)
  - Role Updates (13 tests)
  - User Deletion (9 tests)
  - Edge Cases (4 tests)
- **Tests**: 58 tests, 270 assertions
- **Groups**: `admin`, `master-admin`, `user-management`, `access-control`, `validation`
- **Safety Features**: Cannot delete/demote last master admin, cannot delete self
- **Custom Expectations**: `toBeSuccessfulInertiaResponse()`, `toBeForbidden()`, `toRedirectToLogin()`

##### `Admin/RoleBasedAccessTest.php`
- **Purpose**: Role-based access control across all routes
- **Structure**: 8 describe blocks covering different access patterns
- **Tests**: 56 tests covering three roles (master_admin, admin, member)
- **Groups**: `access-control`, `roles`, `authorization`
- **Key Features**: Role detection, dashboard routing, authorization rules, datasets for repetitive tests
- **Authorization Rules**:
  - Admins: Edit own posts only
  - Master admins: Edit any post, access user management
  - Members: Read-only with commenting/liking

#### Blog Post Management (`tests/Feature/Admin/`)

##### `Admin/BlogPostTest.php`
- **Purpose**: Complete blog post CRUD operations
- **Structure**: 10 describe blocks
  - Blog Post Listing (3 tests)
  - Draft Posts (2 tests)
  - Blog Post Creation (6 tests)
  - Blog Post Creation Validation (3 tests)
  - Blog Post Editing (6 tests)
  - Blog Post Viewing (1 test)
  - Blog Post Deletion (3 tests)
  - Featured Posts (1 test)
  - Blog Post Tags (2 tests)
  - Reading Time Calculation (1 test)
- **Tests**: 28 tests, 143 assertions
- **Groups**: `blog-posts`, `crud`, `validation`, `authenticated`, `admin`
- **Features**: Role-based access, file uploads, reading time calculation, tag management, slug generation
- **Custom Expectations**: `toBeSuccessfulInertiaResponse()`, `toHaveSuccessMessage()`, `toHaveValidationError()`

#### Public Features (`tests/Feature/Public/`)

##### `Public/PublicBlogPostViewingTest.php`
- **Purpose**: Public-facing blog functionality
- **Structure**: 7 describe blocks
  - Landing Page (Home) - 12 tests
  - Blog Index (Listing) - 8 tests
  - Search and Filtering - 6 tests
  - Available Filters - 4 tests
  - Individual Post Viewing - 5 tests
  - Post Metadata Display - 11 tests
  - Edge Cases - 9 tests
- **Tests**: 55 tests, 720 assertions
- **Groups**: `public`, `guest`, `authenticated`, `blog-list`, `blog-post`, `search`, `filtering`
- **Features**: Guest/authenticated access, search, tags, likes, comments, featured posts

#### Social Features (`tests/Feature/Social/`)

##### `Social/BlogPostLikeTest.php`
- **Purpose**: Like/unlike functionality for posts
- **Structure**: 7 describe blocks
  - Liking Blog Posts (8 tests)
  - Unliking Blog Posts (5 tests)
  - Like Database Integrity (2 tests)
  - Like Relationships (2 tests)
  - Cascade Deletion (2 tests)
  - Like Edge Cases (4 tests)
  - Like Queries (2 tests)
- **Tests**: 26 tests, 56 assertions
- **Groups**: `likes`, `blog-posts`, `toggle`, `authenticated`, `guest`
- **Features**: Toggle endpoint, duplicate prevention, like counting, cascade deletion
- **Custom Expectations**: `toHaveSuccessMessage()`, `toRedirectToLogin()`, `toBeNotFound()`

##### `Social/CommentTest.php`
- **Purpose**: Complete comment system functionality
- **Structure**: 5 describe blocks
  - Comment Creation (5 tests)
  - Comment Validation (4 tests)
  - Comment Replies (6 tests)
  - Comment Deletion (6 tests)
  - Comment Relationships (5 tests)
- **Tests**: 29 tests, 58 assertions
- **Groups**: `comments`, `crud`, `validation`, `authenticated`, `replies`
- **Features**: Nested replies, content validation (max 1000 chars), author-only deletion, soft deletes
- **Custom Expectations**: `toHaveSuccessMessage()`, `toRedirectToLogin()`, `toHaveValidationError()`

##### `Social/UserFollowTest.php`
- **Purpose**: Follow/unfollow functionality between users
- **Structure**: 8 describe blocks
  - Following Authors (9 tests)
  - Unfollowing Authors (6 tests)
  - Follow Database Integrity (2 tests)
  - Follow Relationships (5 tests)
  - Cascade Deletion (2 tests)
  - Follow Edge Cases (4 tests)
  - Follow Queries (3 tests)
- **Tests**: 31 tests, 78 assertions
- **Groups**: `follows`, `toggle`, `authenticated`, `guest`, `validation`, `relationships`
- **Features**: Follow admins (authors), self-follow prevention, follower/following counts, cascade deletion
- **Custom Expectations**: `toHaveSuccessMessage()`, `toRedirectToLogin()`, `toBeNotFound()`, `toHaveErrorMessage()`

##### `Public/AuthorProfileTest.php`
- **Purpose**: Author profile pages with posts and statistics
- **Structure**: 6 describe blocks
  - Viewing Author Profiles (2 tests)
  - Profile Data (11 tests)
  - Author Statistics (8 tests)
  - Author Blog Posts (9 tests)
  - Following Status (4 tests)
  - Edge Cases (3 tests)
- **Tests**: 37 tests, 533 assertions
- **Groups**: `author-profile`, `public`, `guest`, `authenticated`, `statistics`, `posts`, `following`
- **Features**: Public access, published posts listing, follower counts, follow status
- **Custom Expectations**: `toBeSuccessfulInertiaResponse()`, `toBeNotFound()`

#### System Tests (`tests/Feature/System/`)

##### `System/MiddlewareTest.php`
- **Purpose**: Middleware execution and functionality
- **Structure**: 5 describe blocks
  - Middleware Execution Order (7 tests)
  - Role-Based Middleware (5 tests)
  - Two-Factor Authentication Middleware (3 tests)
  - Request Transformation Middleware (4 tests)
  - Authorization Middleware (5 tests)
- **Tests**: 24 tests, 30 assertions
- **Groups**: `middleware`, `authentication`, `authorization`, `roles`, `2fa`, `transformation`
- **Middleware Tested**:
  - Authenticate: User authentication
  - EnsureTwoFactorEnabled: 2FA enforcement
  - EnsureUserIsAdmin: Admin-only access
  - EnsureUserIsMasterAdmin: Master admin-only access
  - TrimStrings: Whitespace trimming
  - ConvertEmptyStringsToNull: Empty string conversion
- **Custom Expectations**: `toRedirectToLogin()`, `toBeForbidden()`

##### `System/RateLimitingTest.php`
- **Purpose**: Rate limiting across endpoints
- **Structure**: 9 describe blocks
  - Login Rate Limiting (3 tests)
  - Like Toggling Rate Limiting (1 test)
  - Follow/Unfollow Rate Limiting (1 test)
  - Registration Rate Limiting (1 test)
  - Password Reset Rate Limiting (1 test)
  - Comment Deletion Rate Limiting (1 test)
  - Rate Limit Headers (1 test)
  - Global Request Rate Limiting (1 test)
- **Tests**: 10 tests, 43 assertions
- **Groups**: `rate-limiting`, `login`, `likes`, `follows`, `registration`, `password-reset`
- **Rate Limits**:
  - Login attempts: 5 per minute per email
  - Like toggles: 20 per minute per user
  - Follow actions: 10 per minute per user
- **Custom Expectations**: `toBeRateLimited()`

##### `System/PerformanceTest.php`
- **Purpose**: Application performance characteristics
- **Structure**: 13 describe blocks (nested)
  - N+1 Query Detection
    - Blog Posts (2 tests)
    - Author Profiles (2 tests)
    - Comment Loading (1 test)
  - Large Dataset Handling
    - Pagination (2 tests)
    - Bulk Operations (2 tests)
  - Query Optimization
    - Eager Loading (2 tests)
    - Indexing (2 tests)
  - Memory Usage
    - Stress Testing (1 test)
    - Large Result Sets (1 test)
- **Tests**: 15 tests
- **Groups**: `performance`, `n+1`, `queries`, `optimization`, `memory`
- **Note**: Dataset sizes (20-60 records) balanced for speed vs effectiveness

##### `System/ErrorHandlingTest.php`
- **Purpose**: Error handling and graceful degradation
- **Structure**: 6 describe blocks
  - Database Connection Failures (2 tests)
  - Transaction Rollback (1 test)
  - File System Errors (5 tests)
  - Invalid Input (5 tests)
  - 404 Errors (3 tests)
- **Tests**: 16 tests (1 passing, 15 pre-existing database mock failures)
- **Groups**: `error-handling`, `database`, `filesystem`, `validation`, `404`, `500`
- **Custom Expectations**: `toBeNotFound()`
- **Features**: Graceful failures, error message sanitization

##### `Admin/DashboardTest.php`
- **Purpose**: Dashboard access control
- **Structure**: 1 describe block (Dashboard Access)
- **Tests**: 2 tests
- **Groups**: `dashboard`, `authenticated`, `guest`, `admin`
- **Custom Expectations**: `toRedirectToLogin()`

#### Settings

##### `Settings/ProfileUpdateTest.php`
- **Purpose**: User profile management
- **Structure**: 3 describe blocks
  - Profile Display (1 test)
  - Profile Update (3 tests)
  - Account Deletion (1 test)
- **Tests**: 5 tests
- **Groups**: `settings`, `profile`, `authenticated`, `validation`, `deletion`
- **Features**: Profile display, updates (name/email), account deletion
- **Patterns**: Chained expectations with `->and()`
- **Custom Expectations**: `toHaveValidationError()`

##### `Settings/PasswordUpdateTest.php`
- **Purpose**: Password change functionality
- **Structure**: 2 describe blocks
  - Password Update Page (1 test)
  - Password Change (2 tests)
- **Tests**: 3 tests
- **Groups**: `settings`, `password`, `authenticated`, `validation`
- **Features**: Page access, password validation, successful updates
- **Custom Expectations**: `toHaveValidationError()`

##### `Settings/TwoFactorAuthenticationTest.php`
- **Purpose**: Two-factor authentication settings
- **Structure**: 1 describe block (Two-Factor Settings Page)
- **Tests**: 4 tests
- **Groups**: `settings`, `2fa`, `authenticated`, `authentication`, `authorization`
- **Features**: Page access for different roles, 2FA enablement verification
- **Custom Expectations**: `toBeForbidden()`

### Unit Tests (`tests/Unit/`)

Unit tests verify isolated pieces of code without external dependencies. Our suite includes **278 unit tests** covering:

#### Structure
```
tests/Unit/
├── Models/              # Model business logic (BlogPost, User, Comment, BlogPostLike)
├── Services/            # Service layer (BlogPostService, BlogImageService)
├── Policies/            # Authorization (BlogPostPolicy)
├── Actions/Fortify/     # Authentication actions (CreateNewUser, ResetUserPassword)
├── Http/
│   ├── Requests/        # Form validation (StoreBlogPostRequest, UpdateBlogPostRequest)
│   └── Resources/       # API transformations (BlogPostResource)
```

#### Completed Unit Test Files (278 tests total)

**Models** (79 tests):
- `BlogPostTest.php` - 22 tests (slug generation, reading time calculation)
  - **Groups**: `models`, `blog-post`, `unit`
- `UserTest.php` - 24 tests (role helpers, relationship utilities)
  - **Groups**: `models`, `user`, `unit`
- `CommentTest.php` - 21 tests (threading, relationships, isReply method)
  - **Groups**: `models`, `comment`, `relationships`, `unit`
- `BlogPostLikeTest.php` - 12 tests (pivot relationships, business logic)
  - **Groups**: `models`, `blog-post-like`, `relationships`, `unit`

**Services** (45 tests):
- `BlogPostServiceTest.php` - 30 tests (filtering, queries, data aggregation)
  - **Groups**: `services`, `blog-post-service`, `unit`
- `BlogImageServiceTest.php` - 15 tests (file operations, path transformations)
  - **Groups**: `services`, `blog-image-service`, `unit`

**Policies** (24 tests):
- `BlogPostPolicyTest.php` - 24 tests (authorization rules, access control)
  - **Groups**: `policies`, `authorization`, `blog-post-policy`, `unit`

**Actions/Fortify** (50 tests):
- `CreateNewUserTest.php` - 19 tests (user creation, validation)
  - **Groups**: `actions`, `fortify`, `user-creation`, `unit`
- `ResetUserPasswordTest.php` - 12 tests (password reset, validation)
  - **Groups**: `actions`, `fortify`, `password-reset`, `unit`
- `PasswordValidationRulesTest.php` - 19 tests (password rules, confirmation)
  - **Groups**: `actions`, `fortify`, `password-validation`, `unit`

**HTTP Layer** (80 tests):
- `StoreBlogPostRequestTest.php` - 31 tests (validation rules, authorization)
  - **Groups**: `requests`, `validation`, `authorization`, `unit`
- `UpdateBlogPostRequestTest.php` - 31 tests (update validation, policy integration)
  - **Groups**: `requests`, `validation`, `authorization`, `update-request`, `unit`
- `BlogPostResourceTest.php` - 18 tests (data transformation, conditional content)
  - **Groups**: `resources`, `transformation`, `blog-post-resource`, `unit`

#### Unit Test Principles

1. **Isolation** - No database, filesystem, or network operations
2. **Speed** - Target < 100ms per test (most achieve < 20ms)
3. **Mocking** - All external dependencies mocked/stubbed
4. **Focus** - One behavior per test
5. **Independence** - Tests can run in any order
6. **Memory Management** - Mockery mocks properly cleaned up with `afterEach`
7. **Code Reusability** - Helper methods reduce duplication in validation tests
8. **Edge Case Coverage** - Boundary conditions tested (empty, null, very large values)

#### Running Unit Tests by Group

All unit tests now include PHPDoc annotations documenting their test groups for better organization. While Pest uses the `->group()` method on individual tests/describe blocks, the PHPDoc comments serve as documentation.

**By Directory (Most Practical):**
```bash
./vendor/bin/pest tests/Unit/Models           # All model tests (37 tests)
./vendor/bin/pest tests/Unit/Services         # All service tests (5 tests)
./vendor/bin/pest tests/Unit/Policies         # All policy tests (24 tests)
./vendor/bin/pest tests/Unit/Actions/Fortify  # All Fortify action tests (50 tests)
./vendor/bin/pest tests/Unit/Http/Requests    # All request validation tests (62 tests)
./vendor/bin/pest tests/Unit/Http/Resources   # All resource transformation tests (18 tests)
```

**By Specific File:**
```bash
./vendor/bin/pest tests/Unit/Models/BlogPostTest.php              # BlogPost model tests
./vendor/bin/pest tests/Unit/Models/UserTest.php --group=models   # User model tests (with group filter)
./vendor/bin/pest tests/Unit/Services/BlogPostServiceTest.php     # BlogPostService tests
./vendor/bin/pest tests/Unit/Services/BlogImageServiceTest.php    # BlogImageService tests
```

**All Unit Tests:**
```bash
./vendor/bin/pest tests/Unit                   # All 211 unit tests
./vendor/bin/pest tests/Unit --compact         # Compact output
```

**Test Group Documentation:**

Each unit test file includes PHPDoc annotations indicating its test groups:
- `@group models` - Model business logic tests
- `@group services` - Service layer tests
- `@group policies` - Authorization policy tests
- `@group actions` - Fortify action tests
- `@group requests` - Form request validation tests
- `@group resources` - API resource transformation tests
- `@group unit` - All unit tests

These annotations serve as documentation and help developers quickly identify the purpose and category of each test file.

## Test Helpers

### Custom Expectations

The test suite includes 9 custom expectations (defined in `tests/Pest.php`):

#### Inertia & Response
- `toBeSuccessfulInertiaResponse($component)` - Validates successful Inertia response with correct component
- `toHaveSuccessMessage($message)` - Checks for success flash message
- `toHaveErrorMessage($message)` - Checks for error flash message

#### Authentication & Authorization
- `toRedirectToLogin()` - Validates redirect to login page for guests
- `toBeForbidden()` - Validates 403 Forbidden response
- `toBeUnauthorized()` - Validates 401 Unauthorized response

#### Validation & Errors
- `toHaveValidationError($field)` - Checks for validation error on specific field
- `toBeNotFound()` - Validates 404 Not Found response
- `toBeRateLimited()` - Validates 429 Too Many Requests response

### Datasets

Reusable test datasets for reducing duplication:

#### Role Datasets
```php
'admin_roles' => [['admin'], ['master_admin']]  // Closures creating users
'user_roles' => ['admin', 'master_admin', 'member']  // String role names
'unauthorized_for_master_admin' => [['admin'], ['member']]  // Non-master admins
'valid_user_roles' => ['member', 'admin', 'master_admin']  // All valid roles
```

### Factory Methods

Located in test helper files, these methods create test data:

#### User Factories
```php
createTestMasterAdmin(array $attributes = []): User
createTestAdmin(array $attributes = []): User  
createTestMember(array $attributes = []): User
```

#### Content Factories
```php
createPublishedPost(array $attributes = []): BlogPost
createDraftPost(array $attributes = []): BlogPost
```

### Database Management

- **Strategy**: `LazilyRefreshDatabase` (transaction-based)
- **Advantages**: Fast test execution, automatic rollback, isolated tests
- **Database**: SQLite in-memory for tests

### Middleware Considerations

Some tests disable 2FA middleware to focus on specific functionality:
```php
beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
});
```

## Running Tests

### Run All Tests
```bash
composer test
# or
./vendor/bin/pest
```

### Run Specific Test File
```bash
./vendor/bin/pest tests/Feature/BlogPostTest.php
```

### Run by Group
```bash
./vendor/bin/pest --group=authentication
./vendor/bin/pest --group=crud
./vendor/bin/pest --group=performance
```

### Exclude Groups
```bash
./vendor/bin/pest --exclude-group=performance
```

### Run Specific Test
```bash
./vendor/bin/pest --filter "admin users can create blog posts"
```

### Run with Coverage
```bash
./vendor/bin/pest --coverage
```

### Run in Parallel
```bash
./vendor/bin/pest --parallel
```

### Profile Performance
```bash
./vendor/bin/pest --profile
```

For more testing strategies and patterns, see `tests/PEST_BEST_PRACTICES.md`.

## Best Practices Guide

### Directory Organization

**Feature Tests** - Functional grouping by domain:
```
tests/Feature/
├── Admin/      # Administrative operations (authenticated admins)
├── Auth/       # Authentication flows (login, registration, 2FA)
├── Public/     # Public-facing features (guest-accessible)
├── Social/     # User interactions (likes, comments, follows)
├── Settings/   # User preferences and settings
└── System/     # Infrastructure (middleware, rate limiting, errors)
```

**Unit Tests** - Mirror application structure exactly:
```
tests/Unit/
├── Models/             → app/Models/
├── Services/           → app/Services/
├── Policies/           → app/Policies/
├── Actions/Fortify/    → app/Actions/Fortify/
└── Http/
    ├── Requests/       → app/Http/Requests/
    └── Resources/      → app/Http/Resources/
```

### Test Structure Patterns

#### Feature Test Structure (Pest + Laravel)

```php
use function Pest\Laravel\{actingAs, get, post};

describe('Blog Post Creation', function () {
    beforeEach(function () {
        $this->admin = createTestAdmin();
    });
    
    it('allows admins to create published posts', function () {
        $response = actingAs($this->admin)
            ->post(route('admin.blog-posts.store'), [
                'title' => 'New Post',
                'content' => 'Post content',
                'is_published' => true,
            ]);
        
        expect($response)
            ->toBeSuccessfulInertiaResponse('Posts/Create')
            ->toHaveSuccessMessage('Blog post created successfully');
        
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'New Post',
            'is_published' => true,
        ]);
    })->group('blog-posts', 'crud', 'authenticated');
    
    it('validates required fields', function () {
        $response = actingAs($this->admin)->post(route('admin.blog-posts.store'), []);
        
        expect($response)
            ->toHaveValidationError('title')
            ->and($response)->toHaveValidationError('content');
    })->group('blog-posts', 'validation');
});
```

#### Unit Test Structure (PHPUnit + Mocking)

```php
use PHPUnit\Framework\TestCase;

class BlogPostServiceTest extends TestCase
{
    private BlogPostService $service;
    
    protected function setUp(): void
    {
        // Runs before each test - common initialization
        $this->service = new BlogPostService();
    }
    
    public function test_apply_filters_adds_search_condition(): void
    {
        // Arrange: Set up test data and mocks
        $query = $this->createMock(Builder::class);
        $query->expects($this->once())
            ->method('where')
            ->with($this->callback(function ($closure) {
                return $closure instanceof \Closure;
            }));
        
        $filters = ['search' => 'test term'];
        
        // Act: Execute the behavior being tested
        $result = $this->service->applyFilters($query, $filters);
        
        // Assert: Verify the outcome
        $this->assertSame($query, $result);
    }
    
    public function test_calculate_reading_time_returns_correct_minutes(): void
    {
        // Arrange
        $content = str_repeat('word ', 200); // 200 words
        
        // Act
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert
        $this->assertEquals(1, $minutes); // 200 words / 200 wpm = 1 min
    }
}
```

### Mocking External Dependencies (Unit Tests)

**When to Mock:**
- Database queries (use mocked repositories)
- File operations (mock Storage facade)
- Email/notifications (mock mailer)
- External APIs (mock HTTP clients)
- Time-dependent logic (mock Carbon/now())

**Mock Examples:**

```php
// Mock with return value
public function test_get_user_returns_user_from_repository(): void
{
    $user = new User(['id' => 1, 'name' => 'John']);
    
    $repository = $this->createMock(UserRepository::class);
    $repository->method('find')->with(1)->willReturn($user);
    
    $service = new UserService($repository);
    $result = $service->getUser(1);
    
    $this->assertSame($user, $result);
}

// Mock with exception
public function test_handle_repository_exception(): void
{
    $repository = $this->createMock(UserRepository::class);
    $repository->method('save')->willThrowException(new DatabaseException());
    
    $service = new UserService($repository);
    
    $this->expectException(ServiceException::class);
    $service->createUser(['name' => 'John']);
}

// Mock Laravel Storage facade
public function test_upload_generates_unique_filename(): void
{
    $file = $this->createMock(UploadedFile::class);
    $file->method('getClientOriginalExtension')->willReturn('jpg');
    $file->expects($this->once())
        ->method('storeAs')
        ->with('blog-images', $this->matchesRegularExpression('/^\d+_test-image\.jpg$/'))
        ->willReturn('blog-images/12345_test-image.jpg');
    
    $service = new BlogImageService();
    $path = $service->upload($file);
    
    $this->assertStringStartsWith('/storage/', $path);
}
```

### Data Providers (Reduce Duplication)

**Pest Datasets:**
```php
// In tests/Pest.php
dataset('admin_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
]);

// In test file
it('allows admins to perform action', function ($userFactory) {
    $response = actingAs($userFactory())->post(route('some.action'));
    expect($response)->toBeSuccessful();
})->with('admin_roles');
```

**PHPUnit Data Providers:**
```php
/**
 * @dataProvider discountProvider
 */
public function test_calculate_discount(float $price, float $percent, float $expected): void
{
    $result = $this->calculator->calculateDiscount($price, $percent);
    $this->assertEquals($expected, $result);
}

public static function discountProvider(): array
{
    return [
        '10% of 100' => [100.00, 10, 10.00],
        '25% of 200' => [200.00, 25, 50.00],
        '0% of 100' => [100.00, 0, 0.00],
    ];
}
```

### Test Naming Conventions

**Feature Tests (Pest - Behavior-driven):**
```php
it('allows admins to create blog posts with valid data')
it('prevents guests from accessing admin dashboard')
it('displays published posts on the blog listing page')
it('validates required fields when creating a post')
```

**Unit Tests (PHPUnit - Method-focused):**
```php
public function test_calculate_discount_with_valid_percentage(): void
public function test_throw_exception_when_percentage_is_negative(): void
public function test_format_currency_with_two_decimal_places(): void
```

### Assertions

**Pest Expectations (Feature Tests):**
```php
expect($response)->toBeSuccessful();
expect($response)->toBeSuccessfulInertiaResponse('Posts/Index');
expect($response)->toHaveSuccessMessage('Post created');
expect($response)->toHaveValidationError('title');
expect($response)->toRedirectToLogin();
expect($response)->toBeForbidden();
expect($user->role)->toBe('admin');
expect($posts)->toHaveCount(5);
```

**PHPUnit Assertions (Unit Tests):**
```php
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict comparison
$this->assertTrue($condition);
$this->assertInstanceOf(User::class, $object);
$this->assertCount(3, $array);
$this->assertStringContainsString('substring', $string);
$this->expectException(InvalidArgumentException::class);
```

### Test Groups & Execution

**Add groups for selective execution:**
```php
// Pest
it('creates blog post', function () {
    // test
})->group('crud', 'blog-posts', 'authenticated');

// PHPUnit
/**
 * @group services
 * @group discounts
 */
class DiscountCalculatorTest extends TestCase { }
```

**Run by group:**
```bash
./vendor/bin/pest --group=crud
./vendor/bin/pest --group=blog-posts
./vendor/bin/pest --exclude-group=performance
```

### Performance Optimization

**Feature Tests:**
- Use `LazilyRefreshDatabase` for transaction-based rollback
- Disable unnecessary middleware in specific tests
- Use `Storage::fake()` instead of real filesystem
- Keep test data minimal

**Unit Tests:**
- Target < 100ms per test (most should be < 20ms)
- Never touch database, filesystem, or network
- Mock all external dependencies
- Avoid expensive object creation in loops

### Common Anti-Patterns to Avoid

❌ **Testing multiple behaviors in one test:**
```php
// BAD
it('handles all user operations', function () {
    $user->setName('John');
    expect($user->getName())->toBe('John');
    
    $user->setEmail('john@example.com');
    expect($user->getEmail())->toBe('john@example.com');
});

// GOOD - Split into separate tests
it('sets user name', function () { /* ... */ });
it('sets user email', function () { /* ... */ });
```

❌ **Test interdependence:**
```php
// BAD - Test depends on previous test
private static User $user;
it('creates user', function () { self::$user = User::create(...); });
it('updates user', function () { self::$user->update(...); }); // Depends on previous!

// GOOD - Each test creates its own data
it('creates user', function () { $user = User::create(...); });
it('updates user', function () { $user = User::create(...); $user->update(...); });
```

❌ **Testing implementation details:**
```php
// BAD
it('calls internal cache method', function () {
    $service = Mockery::mock(UserService::class)->makePartial();
    $service->shouldReceive('cacheUser')->once(); // Testing implementation
    $service->getUser(1);
});

// GOOD - Test behavior
it('returns cached user on subsequent calls', function () {
    $service->getUser(1); // First call - hits database
    $service->getUser(1); // Second call - should be faster (cached)
});
```

❌ **Database in unit tests:**
```php
// BAD - Unit test hitting database
public function test_create_user(): void
{
    $service = new UserService();
    $user = $service->createUser(['name' => 'John']);
    $this->assertDatabaseHas('users', ['name' => 'John']); // NO!
}

// GOOD - Mock the repository
public function test_create_user(): void
{
    $repository = $this->createMock(UserRepository::class);
    $repository->expects($this->once())->method('save');
    
    $service = new UserService($repository);
    $service->createUser(['name' => 'John']);
}
```

### Test Organization Checklist

✅ **Feature Tests:**
- [ ] Use `describe()` blocks for logical grouping
- [ ] Use `it()` for behavior-driven test names
- [ ] Add test groups (`->group('crud', 'authenticated')`)
- [ ] Use custom expectations (`toBeSuccessfulInertiaResponse()`)
- [ ] Chain related assertions with `->and()`
- [ ] Use scoped `beforeEach()` for setup
- [ ] Verify database state after operations
- [ ] Test authorization (guest, member, admin, master_admin)

✅ **Unit Tests:**
- [ ] Mock all external dependencies
- [ ] Follow AAA pattern (Arrange-Act-Assert)
- [ ] Test one behavior per test
- [ ] Keep tests fast (< 100ms)
- [ ] Never touch database/filesystem/network
- [ ] Use descriptive method names
- [ ] Test edge cases and error conditions
- [ ] Verify behavior, not implementation

For more detailed examples and patterns, see:
- `tests/PEST_BEST_PRACTICES.md` - Pest-specific patterns and advanced techniques
- `tests/PHPUNIT_BEST_PRACTICES.md` - PHPUnit unit testing comprehensive guide

## Test Coverage Highlights

### User Roles
- Master Admin: Full system access including user management
- Admin: Content management (own posts only)
- Member: Read-only with social features (comments, likes, follows)
- Guest: Public content viewing only

### Security Features
- Mandatory two-factor authentication
- Rate limiting on sensitive endpoints
- Role-based authorization
- CSRF protection
- Password confirmation for sensitive operations
- Email verification

### Data Integrity
- Cannot delete last master admin
- Cannot demote last master admin
- Cannot delete self
- Transaction rollback on errors
- Soft deletes for comments
- Duplicate prevention (likes, follows)

### Validation Coverage
- Email format and uniqueness
- Password strength requirements
- Content length limits (comments: 1000 chars)
- File upload validation (images: 2MB max)
- Required fields enforcement
- Input sanitization (trim, empty to null)

## TODO/Future Enhancements

### Potential Additions
- Email template rendering tests
- File storage integration tests (currently using fake storage)

## Contributing to Tests

### Adding New Tests

1. **Choose the right location**:
   - Feature tests: `tests/Feature/` (HTTP, database, integration)
   - Unit tests: `tests/Unit/` (isolated functions/methods)

2. **Use describe() blocks for organization**:
   ```php
   describe('Blog Post Creation', function () {
       it('allows admins to create posts with valid data', function () {
           // Test implementation
       });
       
       it('prevents guests from creating posts', function () {
           // Test implementation
       });
   });
   ```

3. **Use it() syntax for test descriptions**:
   ```php
   it('allows admins to create posts with valid data', function () {
       // Test implementation
   });
   
   it('prevents guests from creating posts', function () {
       // Test implementation
   });
   ```

4. **Add test groups** for selective execution:
   ```php
   describe('Blog Post Creation', function () {
       it('allows admins to create posts with valid data', function () {
           // Test implementation
       })->group('crud', 'authenticated', 'validation');
   });
   ```

5. **Use custom expectations** for common patterns:
   ```php
   $response->toBeSuccessfulInertiaResponse('Posts/Index');
   $response->toHaveValidationError('title');
   $response->toRedirectToLogin();
   ```

6. **Use test helpers**: Leverage `createTestMasterAdmin()`, `createPublishedPost()`, etc.

### Test Naming Conventions

- Use clear, descriptive names that explain behavior
- Start with the subject or actor: "allows admins", "prevents guests", "displays"
- Use present tense: "allows", "prevents", "redirects"
- Be specific about the scenario: "with valid data", "without authentication", "when post is published"

### Best Practices

**Feature Tests (Pest):**
1. Use `describe()` blocks for logical test organization
2. Use `it()` syntax for behavior-driven test names
3. Add test groups for selective execution (`->group('crud', 'auth')`)
4. Use custom expectations (`toBeSuccessfulInertiaResponse()`)
5. Chain related assertions with `->and()`
6. Use scoped `beforeEach()` for describe-block setup
7. Leverage datasets to reduce duplication (`->with('admin_roles')`)
8. Test authorization across all user roles
9. Verify database state after operations
10. Keep tests independent (can run in parallel)

**Unit Tests (PHPUnit):**
1. Mock all external dependencies (database, filesystem, network)
2. Follow AAA pattern (Arrange-Act-Assert)
3. Test one behavior per test method
4. Keep tests fast (< 100ms, target < 20ms)
5. Never touch database/filesystem/network in unit tests
6. Use descriptive method names (`test_method_name_scenario`)
7. Test edge cases and error conditions
8. Verify behavior, not implementation details
9. Use data providers for multiple test scenarios
10. Mirror application structure in test organization

**General:**
- Each test verifies one behavior
- Tests are independent (no shared state)
- Use factories for test data creation
- Disable unrelated middleware in tests
- Test both happy path and error cases
- Include boundary conditions
- Keep test suites fast (parallel execution)
- Document complex test scenarios

For comprehensive patterns and examples:
- `tests/PEST_BEST_PRACTICES.md` - Pest-specific patterns
- `tests/PHPUNIT_BEST_PRACTICES.md` - PHPUnit unit testing guide

## Test Execution Performance

The test suite is optimized for fast execution:

- **Parallel Execution**: 12 processes by default
- **Transaction-based Rollback**: No database rebuilding between tests
- **In-memory SQLite**: Fast database operations
- **Fake Storage**: No actual file I/O during tests
- **Selective Middleware**: Disabled where not relevant

Target: Keep full suite under 5 seconds on modern hardware.

## Documentation Updates

When adding new features:
1. Add tests for the feature using describe() blocks and it() syntax
2. Update this README with test documentation (structure, groups, custom expectations)
3. Update test statistics in the overview section
4. Document any new test helpers, custom expectations, or datasets
5. Update `tests/PEST_BEST_PRACTICES.md` if introducing new patterns

---

**Last Updated**: January 2026  
**Test Framework**: Pest PHP 4.1 (built on PHPUnit 11.x)  
**Test Count**: 700 tests (422 feature + 278 unit)  
**Assertions**: 2,831  
**Execution Time**: ~4.67 seconds (parallel)  

**Documentation:**
- [PEST_BEST_PRACTICES.md](./PEST_BEST_PRACTICES.md) - Advanced Pest patterns and techniques
- [PHPUNIT_BEST_PRACTICES.md](./PHPUNIT_BEST_PRACTICES.md) - Comprehensive unit testing guide
