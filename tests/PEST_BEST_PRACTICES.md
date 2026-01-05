# Pest PHP Best Practices - Implementation Guide

This document outlines best practices for improving our Pest PHP test suite based on official Pest documentation and community standards.

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

**Already Following:**
- Clear, descriptive test names
- Good use of `beforeEach()` for test setup
- Helper functions in `Pest.php`
- Using `expect()` API instead of PHPUnit assertions
- Modern PHP syntax with arrow functions

**Total:** 423 tests passing (2,266 assertions)

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

### 5. Test Coverage Improvements

**Opportunity:**
Add `todo()` tests for planned features or edge cases not yet covered.

**Example:**
```php
it('handles concurrent comment submissions')->todo();
it('rate limits excessive API calls')->todo('pending rate limiter implementation');
```

---

### 6. Snapshot Testing

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
- Verify full test suite passes (423 tests, 2,266 assertions)

## Future Enhancements

- [ ] Add snapshot testing for API responses
- [ ] Implement `beforeAll()` for expensive setup operations
- [ ] Add more domain-specific custom expectations
- [ ] Profile and optimize slow tests
- [ ] Add `todo()` tests for planned features
- [ ] Explore mutation testing with Infection
- [ ] Add test coverage reporting
