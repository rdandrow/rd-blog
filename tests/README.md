# Test Suite Documentation

This document provides a comprehensive overview of the test suite for the RD Blog application.

## Test Statistics

- **Total Tests**: 423
- **Total Assertions**: 2,266
- **Execution Time**: ~4 seconds (parallel execution)
- **Parallel Processes**: 12
- **Test Framework**: Pest PHP 4.1 with Laravel plugin
- **Last Updated**: January 2026

## Test Architecture

All tests follow Pest PHP best practices:
- **describe() blocks** for logical test organization
- **it() syntax** for behavior-driven descriptions
- **Custom expectations** for domain-specific assertions
- **Test groups** for selective execution
- **Chained expectations** with `->and()`
- **Datasets** for reducing test duplication
- **Scoped beforeEach()** for setup isolation

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

#### User & Role Management

##### `MasterAdminUserManagementTest.php` ⭐
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

##### `RoleBasedAccessTest.php`
- **Purpose**: Role-based access control across all routes
- **Structure**: 8 describe blocks covering different access patterns
- **Tests**: 56 tests covering three roles (master_admin, admin, member)
- **Groups**: `access-control`, `roles`, `authorization`
- **Key Features**: Role detection, dashboard routing, authorization rules, datasets for repetitive tests
- **Authorization Rules**:
  - Admins: Edit own posts only
  - Master admins: Edit any post, access user management
  - Members: Read-only with commenting/liking

#### Blog Post Management

##### `BlogPostTest.php`
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

##### `PublicBlogPostViewingTest.php`
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

##### `BlogPostLikeTest.php`
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

#### Comments

##### `CommentTest.php`
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

#### Social Features

##### `UserFollowTest.php`
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

##### `AuthorProfileTest.php`
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

#### System & Infrastructure

##### `MiddlewareTest.php`
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

##### `RateLimitingTest.php`
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

##### `PerformanceTest.php`
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

##### `ErrorHandlingTest.php`
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

##### `DashboardTest.php`
- **Purpose**: Dashboard access control
- **Structure**: 1 describe block (Dashboard Access)
- **Tests**: 2 tests
- **Groups**: `dashboard`, `authenticated`, `guest`
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

Unit tests verify isolated pieces of code without external dependencies.

##### `ExampleTest.php`
- **Purpose**: Basic unit test example
- **Tests**: 1 example test
- **Note**: Demonstrates Pest PHP syntax for unit testing

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

1. **Keep tests focused**: Each test should verify one behavior
2. **Use factories**: Don't manually create test data
3. **Organize with describe()**: Group related tests logically
4. **Use scoped beforeEach()**: Setup for describe blocks only
5. **Chain expectations**: Use `->and()` for related assertions
6. **Disable unrelated middleware**: Focus tests on specific functionality
7. **Test edge cases**: Include boundary conditions and error states
8. **Verify database state**: Check that data is correctly persisted
9. **Test authorization**: Always verify access control
10. **Use parallel execution**: Tests should be independent and parallelizable

For comprehensive best practices, patterns, and examples, see `tests/PEST_BEST_PRACTICES.md`.

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
**Test Framework**: Pest PHP 4.1  
**Test Count**: 423 tests (2,266 assertions)  
**Average Execution Time**: ~4 seconds with parallel execution
