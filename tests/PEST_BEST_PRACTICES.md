# Pest PHP Best Practices - Implementation Guide

> **Purpose**: Deep dive into Pest patterns, advanced techniques, and the historical journey of our test suite refactoring. For quick reference and common patterns, see [README.md](./README.md).

This document outlines best practices for improving our Pest PHP test suite based on official Pest documentation and community standards. It serves as the authoritative reference for:

- **Advanced Pest patterns** (snapshot testing, beforeAll optimization, custom expectations)
- **Directory organization philosophy** (functional grouping vs structural mirroring)
- **Refactoring journey** (completed improvements and remaining opportunities)
- **Future enhancements** (mutation testing, coverage reporting)

This document provides comprehensive context beyond the quick-reference patterns in the README.

## Current Status (January 2026)

**Completed Refactoring:**
- All feature tests refactored with `describe()` blocks
- All tests converted to `it()` syntax for behavior-driven descriptions
- Custom expectations implemented and applied throughout
- Test groups added to all tests
- Chained expectations with `->and()` used where appropriate
- Scoped `beforeEach()` within describe blocks
- Datasets created and applied for repetitive tests
- Clear test hierarchy and organization
- **✨ Directory structure reorganized to mirror functional areas**

**Directory Organization:**
- `tests/Feature/Admin/` - Administrative operations (4 files)
- `tests/Feature/Auth/` - Authentication flows (7 files)
- `tests/Feature/Public/` - Public-facing features (2 files)
- `tests/Feature/Social/` - Social interactions (3 files)
- `tests/Feature/Settings/` - User settings (3 files)
- `tests/Feature/System/` - System-level tests (4 files)

**Already Following:**
- Clear, descriptive test names
- Good use of `beforeEach()` for test setup
- Helper functions in `Pest.php`
- Using `expect()` API instead of PHPUnit assertions
- Modern PHP syntax with arrow functions

Note: For current suite statistics (test count, assertions, timing), see the Test Suite README.

## Remaining Improvements

### 1. Enhanced Dataset Usage

**Opportunity:**
While datasets have been applied to repetitive tests, there may be additional opportunities to consolidate similar test patterns.

**Example:**
```php
// Could potentially consolidate role-based access tests further
dataset('admin_roles', [
    'admin' => fn() => createTestAdmin(),
    'master_admin' => fn() => createTestMasterAdmin(),
]);

it('allows admins to perform action', function ($userFactory) {
    $response = $this->actingAs($userFactory())->post(route('some.action'));
    expect($response)->toBeSuccessful();
})->with('admin_roles');
```

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
| `app/Http/Controllers/BlogPostController.php` | `tests/Feature/BlogPostTest.php` | Feature |
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

### 2. Performance Optimization with Hooks

**Opportunity:**
Use `beforeAll()` for expensive setup that doesn't need to run before each test.

**Example:**
```php
describe('Blog Performance', function () {
    beforeAll(function () {
        // Seed large dataset once
        BlogPost::factory()->count(100)->create();
    });
    
    it('handles pagination efficiently', function () {
        // Tests use pre-seeded data
    });
});
```

---

### 3. Additional Custom Expectations

**Opportunity:**
Add more domain-specific expectations for common patterns.

**Potential Additions:**
```php
// In Pest.php
expect()->extend('toHaveFlashMessage', function (string $key, string $message) {
    $this->value->assertSessionHas($key, $message);
    return $this;
});

expect()->extend('toBeValidBlogPost', function () {
    expect($this->value)
        ->toHaveKey('id')
        ->toHaveKey('title')
        ->toHaveKey('content')
        ->toHaveKey('author');
    return $this;
});
```

---

### 4. Parallel Testing Optimization

**Current:** Tests run in parallel with 12 processes.

**Opportunity:**
Profile and optimize slow tests to improve overall suite execution time.

```bash
# Identify slow tests
./vendor/bin/pest --profile

# Run specific groups in CI
./vendor/bin/pest --group=fast
./vendor/bin/pest --group=integration --parallel
```

---

### 5. Snapshot Testing

**Opportunity:**
Use Pest's snapshot testing for complex API responses or UI components.

**Example:**
```php
it('returns correct blog post JSON structure', function () {
    $response = $this->get(route('api.posts.show', $post));
    expect($response->json())->toMatchSnapshot();
});
```

---

## Applied Best Practices

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

All tests now use:
- `describe()` blocks for logical grouping
- `it()` syntax for behavior-driven descriptions
- Test groups for selective execution
- Scoped `beforeEach()` for setup isolation
- Chained expectations with `->and()`

### Example: Refactored Test Structure

```php
describe('Blog Post Creation', function () {
    beforeEach(function () {
        $this->admin = createTestAdmin();
    });
    
    it('allows admins to create published posts', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.blog-posts.store'), [
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
    
    it('validates required fields', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.blog-posts.store'), []);
        
        expect($response)->toHaveValidationError('title')
            ->and($response)->toHaveValidationError('content');
    })->group('blog-posts', 'validation');
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
./vendor/bin/pest tests/Feature/BlogPostTest.php
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

## Resources

- [Pest PHP Documentation](https://pestphp.com/docs)
- [Datasets](https://pestphp.com/docs/datasets)
- [Custom Expectations](https://pestphp.com/docs/expectations#custom-expectations)
- [Higher Order Tests](https://pestphp.com/docs/higher-order-tests)
- [Test Groups](https://pestphp.com/docs/groups)

---

## Completed Checklist

- Add custom expectations to `Pest.php`
- Create common datasets for user roles
- Add test groups to all tests
- Refactor all feature tests with `describe()` blocks
- Refactor all settings tests with `describe()` blocks
- Convert all `test()` to `it()` for behavior-driven testing
- Apply scoped `beforeEach()` within describe blocks
- Use chained expectations with `->and()`
- Apply datasets to repetitive tests
- Verify full test suite passes (see README for current counts)

## Future Enhancements

- [ ] Add snapshot testing for API responses
- [ ] Implement `beforeAll()` for expensive setup operations
- [ ] Add more domain-specific custom expectations
- [ ] Profile and optimize slow tests
- [ ] Explore mutation testing with Infection
- [ ] Add test coverage reporting
