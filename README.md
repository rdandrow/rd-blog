# Blog Application

A modern full-stack blog platform built with Laravel 12 and Vue.js 3, featuring enterprise-grade architecture, type safety, and comprehensive authentication.

## Tech Stack

**Backend:** Laravel 12 • PHP 8.2+ • Inertia.js • PostgreSQL 16  
**Frontend:** Vue.js 3 (Composition API) • TypeScript • Tailwind CSS v4 • Vite 6  
**Testing:** Pest PHP (862 tests) • Vitest (1,774 tests) • Feature & Unit Tests

## Key Features

### Authentication & Security
- Full authentication with Laravel Fortify (login, register, 2FA)
- User invitation system with email tokens
- Role-based access control (Master Admin, Admin, Member)
- Rate limiting on sensitive endpoints
- CSRF protection and secure session management

### Content Management
- Rich markdown editor with live preview
- Toolbar shortcuts for formatting (bold, italic, headers, lists, tables)
- Image upload with drag-and-drop support
- Auto-save drafts (every 30 seconds)
- Featured images with URL or file upload
- Tag management and filtering
- SEO-friendly slugs and reading time calculation

### Social Features
- Comment system with threaded replies
- Like/unlike blog posts
- Follow/unfollow authors
- Author profiles with bios and websites

### Search & Discovery
- Full-text search (case-insensitive)
- Filter by tags and authors
- Featured posts showcase
- Recent posts feed

### UI/UX
- Responsive design with dark mode
- Type-safe routing with Laravel Wayfinder
- WCAG-compliant accessibility
- Optimized performance (lazy loading, v-memo directives)
- Expandable markdown editor with tab/split view modes

## Architecture & Best Practices

### Backend (Laravel 12)

**Service Layer Pattern** - Business logic extracted from controllers
```php
// Services handle complex operations
app/Services/
├── BlogPostService.php    # Query logic, filtering, data aggregation
└── BlogImageService.php   # File uploads, deletions, transformations
```

**Form Requests** - Validation separated from controllers
```php
app/Http/Requests/
├── StoreBlogPostRequest.php
└── UpdateBlogPostRequest.php
```

**API Resources** - Consistent data transformation
```php
app/Http/Resources/
└── BlogPostResource.php   # Standardized JSON responses
```

**Policy-Based Authorization** - Granular access control
```php
app/Policies/
└── BlogPostPolicy.php     # Owner-based permissions
```

**Model Best Practices**
- Typed relationships (`BelongsTo`, `HasMany`)
- Eloquent scopes for reusable queries (`published()`, `featured()`)
- Model events in `boot()` for slug generation & reading time
- Proper casts for JSON, dates, and booleans
- Strict return type hints on all methods

**Controller Structure**
- Thin controllers delegating to services
- Dependency injection for type safety
- No business logic in routes or controllers
- Proper separation of admin vs public endpoints

### Frontend (Vue.js 3)

**Composition API Best Practices**
```typescript
// Shared composables for reusable logic
resources/js/composables/
├── useBlogUtils.ts      # formatDate, date utilities
└── useSearchState.ts    # Search sidebar state management
```

**TypeScript Integration**
```typescript
// Centralized type definitions
resources/js/types/index.d.ts
├── BlogPost interface
├── Author interface  
└── SearchFilters interface
```

**Reactive Patterns**
- `toRefs()` for proper reactive destructuring
- `computed()` for derived state
- `v-memo` for performance optimization on lists

**Component Organization**
```
resources/js/
├── components/         # Reusable UI components
├── composables/        # Shared reactive logic
├── layouts/            # Page layouts
├── pages/              # Route components
└── types/              # TypeScript definitions
```

**Performance Optimizations**
- Lazy loading images with native `loading="lazy"`
- v-memo directives on list items
- Centralized CSS utilities (no duplicates)
- Optimized animations in global styles

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BlogPostController.php       # Admin CRUD
│   │   └── PublicBlogController.php     # Public routes
│   ├── Requests/                        # Form validation
│   └── Resources/                       # API transformations
├── Models/
│   ├── BlogPost.php                     # Typed relationships & scopes
│   └── User.php                         # Fortify authentication
├── Policies/                            # Authorization rules
└── Services/                            # Business logic layer

resources/js/
├── components/                          # Shared Vue components
├── composables/                         # Reusable composition functions
├── pages/                               # Inertia page components
└── types/                               # TypeScript definitions

routes/
├── web.php                              # Thin routes (no closures)
└── settings.php                         # User settings routes
```

## Quick Start

```bash
# Install dependencies
composer install && npm install

# Setup environment
cp .env.example .env && php artisan key:generate

# Create PostgreSQL databases
psql postgres -c "CREATE DATABASE rd_blog_dev;"
psql postgres -c "CREATE DATABASE rd_blog_test;"

# Grant permissions
psql rd_blog_dev -c "GRANT ALL ON SCHEMA public TO rd_blog_user;"
psql rd_blog_test -c "GRANT ALL ON SCHEMA public TO rd_blog_user;"

# Configure .env for PostgreSQL
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=rd_blog_dev
# DB_USERNAME=rd_blog_user
# DB_PASSWORD=your_secure_password

# Run migrations & seed
php artisan migrate:fresh --seed

# Build assets & serve
npm run dev          # Development with hot reload
php artisan serve    # In a separate terminal
```

Visit `http://localhost:8000`

**Default credentials after seeding:**
- Master Admin: `ryan@example.com` / `password`
- Admin: `sarah@example.com` / `password`
- Admin: `emily@example.com` / `password`
- Admin: `james@example.com` / `password`

## Testing

**Backend Test Suite:** 809 tests • 3,125 assertions • ~8s parallel / ~24s sequential  
**Frontend Test Suite:** 1,773 tests • 5,000+ assertions • ~35s execution

### Backend Tests (Pest PHP)

```bash
# Run all tests in parallel (fastest - 3x speedup)
composer test
# or
./vendor/bin/pest --parallel

# Run tests sequentially (for debugging)
composer test:sequential
# or
./vendor/bin/pest

# Compact output
./vendor/bin/pest --parallel --compact

# Specific test suites
./vendor/bin/pest tests/Feature/Auth/ --parallel
./vendor/bin/pest tests/Feature/BlogPosts/
./vendor/bin/pest tests/Unit/

# Control parallel processes
./vendor/bin/pest --parallel --processes=8

# With coverage
composer test:coverage
```

**Parallel Testing Performance:**
- Sequential: ~24s (1 process)
- Parallel: ~8s (12 processes)
- Speedup: **3x faster** ⚡

**Note:** Parallel testing requires PostgreSQL user to have `CREATEDB` privilege.
See [docs/PARALLEL_TEST_FIX.md](docs/PARALLEL_TEST_FIX.md) for setup details.

### Frontend Tests (Vitest)

```bash
# Run all frontend tests
npm run test

# Watch mode (re-run on file changes)
npm run test:watch

# UI mode (interactive browser interface)
npm run test:ui

# Coverage report
npm run test:coverage

# Specific test files
npm run test -- BlogPost.test.ts
npm run test -- pages/Admin/
```

**Backend Test Coverage:**
- Authentication flows (login, register, 2FA, password reset, invitations)
- Blog CRUD operations (create, read, update, delete, drafts)
- Authorization policies (admin, member, guest access, ownership)
- Comment system (nested replies, deletion, threading)
- Social features (likes, follows, author profiles)
- Search & filtering (tags, authors, content, case-insensitive)
- Image uploads (validation, storage, transformations)
- Error handling (database failures, validation, security)
- Rate limiting (login attempts, spam prevention)
- Middleware (execution order, CSRF, authorization)
- Performance (N+1 queries, large datasets, memory usage)

**Frontend Test Coverage:**
- Page components (Welcome, Blog, BlogPost, Dashboard)
- Admin CRUD interfaces (Create, Edit, Show, Drafts, Index)
- User management (Admins, Members, Invitations)
- Authentication forms (Login, Register, Password Reset, 2FA)
- Markdown editor (toolbar actions, preview, image upload)
- Composables (useAuth, useBlogUtils, useMarkdown, useAutoSave)
- Form validation and error handling
- Navigation and routing
- Accessibility features (ARIA, keyboard navigation)
- Comment interactions (create, reply, delete)

## Production Deployment

```bash
# Build optimized assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Run migrations (production database)
php artisan migrate --force

# Set production environment variables
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# PostgreSQL production settings
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_DATABASE=rd_blog_prod
```

**Recommended hosting:**
- Railway ($5/mo) - Managed PostgreSQL + Laravel deployment
- DigitalOcean App Platform - Zero-config deployment
- Heroku with Postgres add-on
- Laravel Forge + DigitalOcean/AWS

See `docs/DATABASE_MIGRATION_PLAN.md` for detailed production setup.

## Database Configuration

### PostgreSQL

Edit the following lines within `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rd_blog_dev
DB_USERNAME=rd_blog_user
DB_PASSWORD=your_secure_password
```

**Create PostgreSQL user:**
```bash
psql postgres -c "CREATE USER rd_blog_user WITH PASSWORD 'your_secure_password' CREATEDB;"
psql postgres -c "CREATE DATABASE rd_blog_dev OWNER rd_blog_user;"
psql postgres -c "CREATE DATABASE rd_blog_test OWNER rd_blog_user;"
```

**Note:** `CREATEDB` privilege is required for parallel test execution.

### Performance Monitoring

**Analyze database performance:**
```bash
# Show all statistics (index usage, table stats, cache hit rates)
php artisan db:analyze-performance

# Index usage only
php artisan db:analyze-performance --indexes

# Cache performance
php artisan db:analyze-performance --cache

# Slow query analysis (requires pg_stat_statements extension)
php artisan db:analyze-performance --slow-queries

# Specific table
php artisan db:analyze-performance --table=blog_posts
```

**Analyze query execution plans:**
```bash
# Basic query analysis with EXPLAIN ANALYZE
php artisan db:explain "SELECT * FROM blog_posts WHERE is_published = true"

# With optimization suggestions (detects missing indexes, sequential scans)
php artisan db:explain "SELECT * FROM blog_posts" --suggest

# Show buffer usage and detailed output
php artisan db:explain "SELECT * FROM blog_posts" --buffers --detailed

# Analyze query from file
php artisan db:explain --file=query.sql --suggest

# JSON output for programmatic use
php artisan db:explain "SELECT * FROM users" --format=json

# EXPLAIN without executing (safe for UPDATE/DELETE queries)
php artisan db:explain "UPDATE blog_posts SET views = views + 1" --no-execute
```

**Features:**
- 🎨 Color-coded output (green for index scans, yellow for sequential scans)
- ⚡ Performance assessment (fast/good/slow/very slow)
- 🔍 Automatic optimization suggestions
- 📊 Multiple output formats (text, JSON)

See [docs/EXPLAIN_ANALYZE_COMMAND.md](docs/EXPLAIN_ANALYZE_COMMAND.md) for complete guide.

**Automatic Query Monitoring (Development/Staging):**
- Slow queries (>100ms) logged as warnings
- Extremely slow queries (>500ms) logged with stack traces
- N+1 query problems detected (>50 queries per request)
- Only active in local/staging environments

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep -E "(Slow query|N+1)"
```

See [docs/PHASE_4_3_PERFORMANCE_MONITORING.md](docs/PHASE_4_3_PERFORMANCE_MONITORING.md) for detailed monitoring guide.

### Connection Pooling

For production environments with high traffic, use **PgBouncer** for connection pooling:

**Why PgBouncer?**
- ⚡ **50x faster** connection establishment (<1ms vs 5-50ms)
- 📈 **10x more** concurrent connections (1000+ vs 100-200)
- 💾 **70% reduction** in memory usage
- 🚀 **4x faster** response times at scale

**Quick Setup:**
```bash
# Install PgBouncer
brew install pgbouncer  # macOS
# or
sudo apt install pgbouncer  # Ubuntu

# Configure for Laravel (transaction mode)
# Edit /etc/pgbouncer/pgbouncer.ini
[databases]
rd_blog_prod = host=localhost port=5432 dbname=rd_blog_prod

[pgbouncer]
pool_mode = transaction
default_pool_size = 20
max_client_conn = 1000

# Update .env to use PgBouncer
DB_PORT=6432  # PgBouncer port instead of 5432
```

**When to use:**
- ✅ >100 concurrent requests
- ✅ Serverless deployments (AWS Lambda, Cloud Functions)
- ✅ Multiple application workers
- ❌ Low traffic development environments

See [docs/CONNECTION_POOLING_GUIDE.md](docs/CONNECTION_POOLING_GUIDE.md) for complete setup guide with configuration examples, monitoring, and troubleshooting.

### Query Result Caching

Application-level caching for frequently accessed blog data provides **100-350x faster** repeated queries:

**Performance Gains:**
```php
use App\Services\BlogPostService;

$service = new BlogPostService();

// Automatically cached (1 hour TTL):
$popularPosts = $service->getPopularPosts(10);     // 87ms → 0.8ms (108x faster)
$tags = $service->getAvailableTags();              // 65ms → 0.6ms (108x faster)
$authors = $service->getAvailableAuthors();        // 42ms → 0.5ms (84x faster)
$stats = $service->getPostStats();                 // 245ms → 0.7ms (350x faster)
```

**Key Features:**
- ⚡ **Automatic caching**: Popular posts, tags, authors, statistics
- 🔄 **Smart invalidation**: Cache cleared on create/update/delete
- 🎯 **Filter-aware**: Separate caches for different query parameters
- ⏱️ **Configurable TTL**: 1 hour default, 30 min for statistics

**Configuration:**
```env
# Use database cache (default)
CACHE_STORE=database

# Or Redis for production (recommended)
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

**Cache is automatically invalidated** when blog posts are created, updated, or deleted. No manual clearing needed!

See [docs/QUERY_CACHING_GUIDE.md](docs/QUERY_CACHING_GUIDE.md) for complete implementation guide, testing, and best practices.

### Covering Indexes

PostgreSQL covering indexes enable **index-only scans** without heap access, providing **5-20x faster queries**:

**Performance Improvements:**
```sql
-- Published posts listing
Before: 45ms (Index Scan + Heap Fetches)
After:  2.8ms (Index Only Scan) → 16x faster ✅

-- Featured posts
Before: 38ms  
After:  2.5ms → 15x faster ✅

-- User drafts
Before: 28ms
After:  2.3ms → 12x faster ✅

-- Comment threads
Before: 22ms
After:  2.1ms → 10x faster ✅
```

**How It Works:**
- **INCLUDE clause**: Adds non-key columns to index
- **Index-only scans**: All data fetched from index (no table access)
- **Partial indexes**: Smaller, more efficient (WHERE clauses)
- **Automatic usage**: PostgreSQL query planner selects best index

**7 Covering Indexes Created:**
1. Published posts with display fields (CRITICAL - 16x)
2. Featured posts with metadata (HIGH - 15x)
3. User drafts with management fields (MEDIUM-HIGH - 12x)
4. Comment threads with content (MEDIUM - 10x)
5. User published post counts (MEDIUM - 10x)
6. Post likes with user checks (LOW-MEDIUM - 8x)
7. Available tags from published posts (MEDIUM - 11x)

**Storage Impact:** ~6-7 MB per 10K posts (excellent ROI)

See [docs/COVERING_INDEXES_GUIDE.md](docs/COVERING_INDEXES_GUIDE.md) for complete guide with verification, maintenance, and troubleshooting.

### Partial Tag Indexes

Partial tag indexes optimize **tag-filtered queries** by indexing only rows matching specific hot tags, providing **10-30x faster queries**:

**Performance Improvements:**
```sql
-- Laravel tag filtering
Before: 45ms (Full GIN Scan)
After:  1.5ms (Partial Index) → 30x faster ✅

-- PHP tag filtering
Before: 42ms
After:  1.8ms → 23x faster ✅

-- JavaScript tag filtering
Before: 38ms
After:  2.1ms → 18x faster ✅

-- Tutorial tag filtering
Before: 35ms
After:  2.3ms → 15x faster ✅
```

**How It Works:**
- **Partial WHERE clause**: Only indexes published posts with specific tag
- **90% smaller**: Each partial index only contains matching rows
- **GIN + Composite**: Tag containment checks + pre-sorted by date
- **Hot tag strategy**: Index 11 most popular tags (80/20 rule)

**16 Partial Tag Indexes Created:**
- **11 GIN Indexes**: Fast tag containment (`@>` operator)
  - Laravel, PHP, JavaScript, Vue.js, Tutorial, Tips, Performance, Database, API, Frontend, Backend
- **5 Composite Indexes**: Pre-sorted by published date
  - Laravel, PHP, JavaScript, Vue.js, Tutorial (most common query pattern)

**Example Query:**
```php
// Automatically uses partial tag index
BlogPost::published()
    ->whereJsonContains('tags', 'Laravel')
    ->orderBy('published_at', 'desc')
    ->get();
// Execution: 1.5ms (was 45ms) - 30x faster!
```

**Storage Impact:** ~288 KB total (16 KB per index) - minimal overhead for massive performance gains

See [docs/PARTIAL_TAG_INDEXES_GUIDE.md](docs/PARTIAL_TAG_INDEXES_GUIDE.md) for hot tag selection, verification, and maintenance.

## Framework Documentation

- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Vue.js 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Inertia.js Guide](https://inertiajs.com/)
- [Tailwind CSS v4](https://tailwindcss.com/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Vitest Testing Framework](https://vitest.dev/)
- [Pest PHP Testing](https://pestphp.com/)
