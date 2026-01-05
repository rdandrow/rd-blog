# Test Suite Documentation

This document provides a comprehensive overview of the test suite for the RD Blog application.

## Test Statistics

- **Total Tests**: 418
- **Total Assertions**: 2,045
- **Execution Time**: ~4-5 seconds (parallel execution)
- **Parallel Processes**: 12
- **Test Framework**: Pest PHP 4.1 with Laravel plugin

## Test Organization

### Feature Tests (`tests/Feature/`)

Feature tests verify complete user-facing functionality including HTTP requests, database interactions, and integration between components.

#### Authentication & Authorization

##### `Auth/AuthenticationTest.php`
- **Purpose**: User login/logout functionality
- **Tests**: 8 tests covering login screen, authentication process, logout, rate limiting
- **Key Features**: Valid/invalid credentials, remember me, 2FA integration

##### `Auth/RegistrationTest.php`
- **Purpose**: User registration and account creation
- **Tests**: 6 tests covering registration form, validation, mandatory 2FA setup
- **Key Features**: Email uniqueness, password validation, default role assignment

##### `Auth/TwoFactorChallengeTest.php`
- **Purpose**: Two-factor authentication challenge process
- **Tests**: 4 tests covering challenge screen, code validation, recovery codes
- **Key Features**: TOTP validation, recovery code authentication

##### `Auth/PasswordResetTest.php`
- **Purpose**: Password reset via email
- **Tests**: 5 tests covering reset link generation, token validation, password update
- **Key Features**: Email notifications, signed URLs, token expiration

##### `Auth/EmailVerificationTest.php`
- **Purpose**: Email verification process
- **Tests**: 6 tests covering verification links, already verified handling
- **Key Features**: Signed URL validation, verification events

##### `Auth/PasswordConfirmationTest.php`
- **Purpose**: Password confirmation for sensitive operations
- **Tests**: 2 tests covering confirmation screen and validation
- **Key Features**: Recent password verification requirement

##### `Auth/VerificationNotificationTest.php`
- **Purpose**: Email verification notification sending
- **Tests**: 3 tests covering notification dispatch and resend functionality
- **Key Features**: Duplicate prevention, rate limiting

#### User & Role Management

##### `MasterAdminUserManagementTest.php` ⭐
- **Purpose**: Comprehensive user management (master admin exclusive)
- **Tests**: 60 tests, 286 assertions
- **Categories**:
  - Access Control (8 tests): Master admin authorization
  - User Listing (6 tests): Admin/member indexes with ordering
  - User Creation (20 tests): All roles, validation, access control
  - Role Updates (13 tests): Promotions, demotions, safety checks
  - User Deletion (9 tests): Delete operations, last master admin protection
  - Edge Cases (4 tests): Complex scenarios, data integrity
- **Safety Features**: Cannot delete/demote last master admin, cannot delete self

##### `RoleBasedAccessTest.php`
- **Purpose**: Role-based access control across all routes
- **Tests**: 45+ tests covering three roles (master_admin, admin, member)
- **Key Features**: Role detection methods, dashboard routing, authorization rules
- **Authorization Rules**:
  - Admins: Edit own posts only
  - Master admins: Edit any post, access user management
  - Members: Read-only with commenting/liking

#### Blog Post Management

##### `BlogPostTest.php`
- **Purpose**: Complete blog post CRUD operations
- **Tests**: 40+ tests covering creation, editing, publishing, deletion
- **Categories**:
  - List/Index: Post listing and filtering
  - Create: Post creation with validation
  - Edit: Update operations
  - Delete: Deletion and authorization
  - Publishing: Draft/publish workflow
  - Featured Images: Image upload and validation
  - Slug Generation: Automatic URL-friendly slugs
  - Validation: Input validation
- **Features**: Role-based access, file uploads, reading time calculation, tag management

##### `PublicBlogPostViewingTest.php`
- **Purpose**: Public-facing blog functionality
- **Tests**: 80+ tests covering landing page, blog index, individual posts
- **Categories**:
  - Landing Page: Homepage with featured posts
  - Blog Index: Main listing with pagination
  - Individual Posts: Single post pages with comments
  - Search: Blog post search functionality
  - Filtering: Tag filtering and sorting
  - Pagination: Large dataset handling
- **Features**: Guest/authenticated access, search, tags, likes, comments

##### `BlogPostLikeTest.php`
- **Purpose**: Like/unlike functionality for posts
- **Tests**: 30+ tests covering toggle behavior, counts, access control
- **Features**: Toggle endpoint, duplicate prevention, like counting

#### Comments

##### `CommentTest.php`
- **Purpose**: Complete comment system functionality
- **Tests**: 40+ tests covering creation, replies, editing, deletion
- **Categories**:
  - Adding Comments: Basic creation and validation
  - Reply Comments: Nested threading
  - Validation: Input validation and error handling
  - Deletion: Soft deletion and authorization
  - Display: Retrieval and ordering
  - Access Control: Authentication and ownership
- **Features**: Nested replies, content validation (max 1000 chars), author-only deletion

#### Social Features

##### `UserFollowTest.php`
- **Purpose**: Follow/unfollow functionality between users
- **Tests**: 35+ tests covering toggle behavior, counts, relationships
- **Features**: Follow admins (authors), self-follow prevention, follower/following counts

##### `AuthorProfileTest.php`
- **Purpose**: Author profile pages with posts and statistics
- **Tests**: 40+ tests covering profile viewing, posts, stats, social features
- **Features**: Public access, published posts listing, follower counts, follow status

#### System & Infrastructure

##### `MiddlewareTest.php`
- **Purpose**: Middleware execution and functionality
- **Tests**: 20+ tests covering ordering, role-based access, request transformation
- **Middleware Tested**:
  - Authenticate: User authentication
  - EnsureTwoFactorEnabled: 2FA enforcement
  - EnsureUserIsAdmin: Admin-only access
  - EnsureUserIsMasterAdmin: Master admin-only access
  - TrimStrings: Whitespace trimming
  - ConvertEmptyStringsToNull: Empty string conversion
- **Validations**: Execution order, redirects, access denial, data transformation

##### `RateLimitingTest.php`
- **Purpose**: Rate limiting across endpoints
- **Tests**: 5 tests covering login attempts, likes, follows
- **Rate Limits**:
  - Login attempts: 5 per minute per email
  - Like toggles: 20 per minute per user
  - Follow actions: 10 per minute per user
- **Features**: 429 responses, rate limit reset after success

##### `PerformanceTest.php`
- **Purpose**: Application performance characteristics
- **Tests**: 25+ tests covering N+1 queries, large datasets, memory usage
- **Categories**:
  - N+1 Query Detection: Eager loading verification
  - Large Dataset Handling: 20-60 records per test
  - Memory Usage: Memory efficiency patterns
  - Query Optimization: Indexing and optimization
- **Note**: Dataset sizes balanced for speed vs effectiveness

##### `ErrorHandlingTest.php`
- **Purpose**: Error handling and graceful degradation
- **Tests**: 15+ tests covering database failures, file system errors, invalid input
- **Categories**:
  - Database Connection Failures
  - Transaction Rollback: Data integrity
  - File System Errors: Storage failures
  - Invalid Input: Malformed data
  - 404/500 Errors: Proper status codes
- **Features**: Graceful failures, error message sanitization

##### `DashboardTest.php`
- **Purpose**: Dashboard access control
- **Tests**: 2 tests covering guest redirect and authenticated access
- **Features**: Authentication enforcement, proper redirects

#### Settings

##### `Settings/ProfileUpdateTest.php`
- **Purpose**: User profile management
- **Tests**: 8 tests covering profile viewing and updates
- **Features**: Name/email updates, email verification reset on change, validation

##### `Settings/PasswordUpdateTest.php`
- **Purpose**: Password change functionality
- **Tests**: 5 tests covering password updates and validation
- **Features**: Current password verification, confirmation matching, strength requirements

##### `Settings/TwoFactorAuthenticationTest.php`
- **Purpose**: 2FA settings management
- **Tests**: 8 tests covering enable/disable, QR codes, recovery codes
- **Features**: QR code generation, recovery codes, password confirmation

### Unit Tests (`tests/Unit/`)

Unit tests verify isolated pieces of code without external dependencies.

##### `ExampleTest.php`
- **Purpose**: Basic unit test example
- **Tests**: 1 example test
- **Note**: Demonstrates Pest PHP syntax for unit testing

## Test Helpers

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

2. **Add documentation header**:
   ```php
   /**
    * [Feature Name] Test Suite
    *
    * Brief description of what this test suite covers.
    *
    * Test Categories:
    * - Category 1: Description
    * - Category 2: Description
    *
    * Features Tested:
    * - Feature 1
    * - Feature 2
    */
   ```

3. **Use descriptive test names**:
   ```php
   test('admin users can create blog posts with valid data', function () {
       // Test implementation
   });
   ```

4. **Use test helpers**: Leverage `createTestMasterAdmin()`, `createPublishedPost()`, etc.

### Test Naming Conventions

- Use clear, descriptive names that explain what is being tested
- Start with the role or actor: "admin users", "guests", "authenticated users"
- Use present tense: "can create", "cannot delete", "redirects to"
- Be specific about the scenario: "with valid data", "without authentication", "when post is published"

### Best Practices

1. **Keep tests focused**: Each test should verify one thing
2. **Use factories**: Don't manually create test data
3. **Disable unrelated middleware**: Focus tests on specific functionality
4. **Test edge cases**: Include boundary conditions and error states
5. **Verify database state**: Check that data is correctly persisted
6. **Test authorization**: Always verify access control
7. **Use parallel execution**: Tests should be independent and parallelizable

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
1. Add tests for the feature
2. Update this README with test documentation
3. Add PHPDoc headers to test files
4. Update test statistics if significant changes
5. Document any new test helpers or patterns
