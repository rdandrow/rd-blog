# Database Migration Plan: SQLite → PostgreSQL

## Executive Summary

This document outlines a comprehensive plan to migrate the blog application from SQLite to PostgreSQL, providing improved scalability, concurrency, and production-readiness.

## Current State Analysis

### Database Configuration
- **Current DB**: SQLite (file-based: `database/database.sqlite`)
- **Default Connection**: `sqlite` (configured in `config/database.php`)
- **Testing**: In-memory SQLite (`:memory:`)
- **Queue/Cache**: Using SQLite connection

### Schema Analysis
**Migrations**: 17 total migrations covering:
- Users table with 2FA, roles, invitations
- Blog posts with JSON tags, fulltext content
- Comments with nested replies (parent_id)
- Blog post likes
- User follows (many-to-many)
- Cache, jobs, sessions tables

**Key Features Used**:
- ✅ JSON columns (`tags` in blog_posts)
- ✅ Foreign key constraints with cascading deletes
- ✅ Timestamps (created_at, updated_at)
- ✅ Indexes on comments table
- ✅ Unique constraints (slugs, emails)
- ✅ Nullable columns
- ✅ Boolean columns
- ✅ Text/LongText columns

**No SQLite-Specific Features Detected**:
- ❌ No raw SQLite queries (`whereRaw`, `DB::raw`)
- ❌ No AUTOINCREMENT keywords
- ❌ No SQLite-specific functions
- ❌ No direct SQL in application code

### Model Analysis
**Cast Types Used**:
```php
'tags' => 'array',         // JSON array
'is_featured' => 'boolean',
'is_published' => 'boolean',
'published_at' => 'datetime',
```
✅ All cast types are cross-compatible with PostgreSQL

## Recommended Database: PostgreSQL

### Why PostgreSQL?

**1. Production-Grade Features**
- ACID compliance with true concurrent transactions
- Row-level locking (vs table-level in SQLite)
- Multiple concurrent writers (critical for blog with comments/likes)
- Better performance at scale (100K+ posts, millions of rows)

**2. Advanced Features for Future Growth**
- Full-text search (built-in, better than JSON-based search)
- JSON/JSONB columns (better than SQLite JSON)
- Array columns (native support for tags instead of JSON)
- Geospatial support (if adding author locations)
- Materialized views (for analytics dashboards)

**3. Ecosystem & Tooling**
- Industry standard for Laravel production apps
- Better ORM support (Laravel optimized for PostgreSQL)
- PgAdmin, pgcli, DBeaver for management
- Backup/restore tools (pg_dump, WAL archiving)
- Connection pooling (PgBouncer)

**4. Hosting Options**
- Heroku Postgres (free tier available)
- AWS RDS PostgreSQL
- DigitalOcean Managed Databases
- Railway.app (free tier)
- Supabase (free tier with realtime features)
- Neon (serverless PostgreSQL)

**5. Cost-Effective**
- Most cloud providers offer free PostgreSQL tiers
- Scales better than MySQL for read-heavy workloads (blog use case)
- Lower TCO than commercial databases

### Why Not MySQL/MariaDB?

While MySQL is also production-ready:
- PostgreSQL has better JSON support (JSONB vs JSON)
- PostgreSQL full-text search is more powerful
- PostgreSQL has better standards compliance
- PostgreSQL performs better with complex joins (comments, likes, follows)
- Laravel ecosystem trends toward PostgreSQL

## Migration Strategy

### Phase 1: Preparation (Week 1)

#### 1.1 Install PostgreSQL
```bash
# macOS
brew install postgresql@16
brew services start postgresql@16

# Ubuntu/Debian
sudo apt install postgresql postgresql-contrib

# Windows
# Download from https://www.postgresql.org/download/windows/
```

#### 1.2 Create Database & User
```bash
# Connect to PostgreSQL
psql postgres

# In psql:
CREATE DATABASE rd_blog_dev;
CREATE DATABASE rd_blog_test;  -- For testing
CREATE USER rd_blog_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE rd_blog_dev TO rd_blog_user;
GRANT ALL PRIVILEGES ON DATABASE rd_blog_test TO rd_blog_user;

# Grant CREATEDB for parallel test execution (Pest --parallel)
ALTER USER rd_blog_user CREATEDB;

# Grant schema privileges (PostgreSQL 15+)
\c rd_blog_dev
GRANT ALL ON SCHEMA public TO rd_blog_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO rd_blog_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO rd_blog_user;

# Repeat for test database
\c rd_blog_test
GRANT ALL ON SCHEMA public TO rd_blog_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO rd_blog_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO rd_blog_user;

# Set default privileges for future tables
\c rd_blog_dev
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO rd_blog_user;

\c rd_blog_test
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO rd_blog_user;
```

#### 1.3 Update Environment Configuration
```bash
# .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_SSLMODE=prefer
DB_DATABASE=rd_blog_dev
DB_USERNAME=rd_blog_user
DB_PASSWORD=your_secure_password

# Keep SQLite for local testing (optional)
# TEST_DB_CONNECTION=sqlite
# TEST_DB_DATABASE=:memory:
```

#### 1.4 Update Configuration Files

**config/database.php** (already configured, no changes needed)
```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('DB_SSLMODE', 'prefer'),
],
```

For production deployments with TLS, set `DB_SSLMODE=require` (or `verify-ca` / `verify-full` when certificate validation is configured).

#### 1.5 Update Testing Configuration

**phpunit.xml** - Use PostgreSQL for realistic testing:
```xml
<env name="DB_CONNECTION" value="pgsql" force="false"/>
<env name="DB_DATABASE" value="rd_blog_test" force="false"/>
```

Use `force="false"` so CI/job-level env vars can override these local fallbacks.

**Why PostgreSQL for Testing?**
- ✅ Tests actual production database behavior
- ✅ Catches PostgreSQL-specific issues (case sensitivity, JSON operators)
- ✅ Validates indexes and query performance
- ✅ Parallel test execution (Pest `--parallel`) creates temp databases

**Note**: Parallel testing requires the database user to have `CREATEDB` privilege.
Pest creates temporary databases like `rd_blog_test_test_1`, `rd_blog_test_test_2`, etc.

### Phase 2: Migration Validation (Week 1-2)

#### 2.1 Test Fresh Migration
```bash
# Clear any existing data
php artisan migrate:fresh

# Run all migrations
php artisan migrate

# Verify schema
php artisan db:show
php artisan db:table users
php artisan db:table blog_posts
```

#### 2.2 Test Seeders
```bash
php artisan db:seed
```

#### 2.3 Run Test Suite
```bash
# Backend tests
./vendor/bin/pest

# Should pass: 906 tests (3,382 assertions)
```

#### 2.4 Manual Testing Checklist
- [ ] User registration/login
- [ ] Blog post creation with tags (JSON)
- [ ] Comment creation with nested replies
- [ ] Like/unlike posts
- [ ] Follow/unfollow users
- [ ] Search and filtering
- [ ] Image uploads
- [ ] Concurrent operations (multiple browser tabs)

### Phase 3: Data Migration (Week 2)

#### 3.1 Export Existing Data (If Production)

**Option A: Laravel-Native Migration** (Recommended)
```php
// database/seeders/MigrateSQLiteDataSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateSQLiteDataSeeder extends Seeder
{
    public function run(): void
    {
        // Temporarily connect to SQLite
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        
        $tables = ['users', 'blog_posts', 'comments', 'blog_post_likes', 'user_follows'];
        
        foreach ($tables as $table) {
            echo "Migrating {$table}...\n";
            
            DB::connection('sqlite')
                ->table($table)
                ->orderBy('id')
                ->chunk(1000, function ($records) use ($table) {
                    foreach ($records as $record) {
                        DB::connection('pgsql')
                            ->table($table)
                            ->insert((array) $record);
                    }
                });
        }
        
        echo "Migration complete!\n";
    }
}
```

Run migration:
```bash
php artisan db:seed --class=MigrateSQLiteDataSeeder
```

**Option B: pgloader** (For large datasets)
```bash
# Install pgloader
brew install pgloader  # macOS
sudo apt install pgloader  # Ubuntu

# Create migration script
cat > migrate.load << EOF
LOAD DATABASE
    FROM sqlite://$(pwd)/database/database.sqlite
    INTO postgresql://rd_blog_user:password@localhost/rd_blog_dev
    
    WITH data only,
         truncate,
         create no tables,
         create no indexes
         
    CAST type text to text drop typemod,
         type datetime to timestamptz drop default drop not null
;
EOF

# Run migration
pgloader migrate.load
```

#### 3.2 Verify Data Integrity
```bash
# Compare record counts
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM users;"
psql rd_blog_dev -c "SELECT COUNT(*) FROM users;"

# Verify JSON data
psql rd_blog_dev -c "SELECT id, title, tags FROM blog_posts LIMIT 5;"
```

### Phase 4: Optimization (Week 3)

#### 4.1 Add PostgreSQL-Specific Indexes

```bash
php artisan make:migration add_postgresql_indexes
```

```php
// database/migrations/2026_02_xx_add_postgresql_indexes.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $language = (string) config('database.full_text_search.language', 'english');

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $language)) {
            throw new RuntimeException("Invalid full-text search language config: {$language}");
        }

        // Full-text search on blog posts
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_search_idx ON blog_posts 
            USING GIN(to_tsvector('{$language}', title || ' ' || excerpt || ' ' || content))
        ");
        
        // Optimize tag searches (GIN index for JSON)
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_tags_idx ON blog_posts USING GIN(tags)");
        
        // Composite indexes for common queries
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_published_at_idx 
            ON blog_posts (is_published, published_at DESC) 
            WHERE is_published = true
        ");
        
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS comments_post_parent_idx 
            ON comments (blog_post_id, parent_id, created_at DESC)
        ");
    }
    
    public function down(): void
    {
        DB::statement("DROP INDEX CONCURRENTLY IF EXISTS blog_posts_search_idx");
        DB::statement("DROP INDEX CONCURRENTLY IF EXISTS blog_posts_tags_idx");
        DB::statement("DROP INDEX CONCURRENTLY IF EXISTS blog_posts_published_at_idx");
        DB::statement("DROP INDEX CONCURRENTLY IF EXISTS comments_post_parent_idx");
    }
};
```

#### 4.2 Optimize Queries for PostgreSQL

**app/Services/BlogPostService.php** - Add full-text search:
```php
public function applyFilters(Builder $query, array $filters): Builder
{
    // Use PostgreSQL full-text search instead of LIKE
    if ($search = $filters['search'] ?? null) {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
                $language = config('database.full_text_search.language', 'english');
            $query->whereRaw("
                to_tsvector(?, title || ' ' || excerpt || ' ' || content) 
                @@ plainto_tsquery(?, ?)
            ", [$language, $language, $search]);
        } else {
            // Fallback for SQLite (testing)
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
    }
    
    // ... rest of filters
}
```

#### 4.3 Enable Query Performance Monitoring

**app/Providers/AppServiceProvider.php**:
```php
use Illuminate\Support\Facades\DB;

public function boot(): void
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log slow queries (>100ms)
                logger()->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => '[REDACTED]',
                    'binding_count' => count($query->bindings),
                    'time' => $query->time,
                ]);
            }
        });
    }
}
```

### Phase 5: Production Deployment

#### 5.1 Choose Hosting Provider

**Recommended: Railway.app** (Easiest for Laravel)
```bash
# Install Railway CLI
npm install -g @railway/cli

# Login and init
railway login
railway init

# Provision PostgreSQL
railway add
# Select PostgreSQL from list

# Deploy
railway up
```

**Alternative: Heroku**
```bash
# Create app
heroku create rd-blog-prod

# Add PostgreSQL
heroku addons:create heroku-postgresql:essential-0

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate --force
```

**Alternative: DigitalOcean**
```bash
# Create managed database via UI
# Connection details: DO_DB_HOST, DO_DB_PASSWORD, etc.

# Update .env on production server
DB_CONNECTION=pgsql
DB_HOST=$DO_DB_HOST
DB_PORT=25060
DB_DATABASE=rd_blog_prod
DB_USERNAME=doadmin
DB_PASSWORD=$DO_DB_PASSWORD
DB_SSLMODE=require
```

#### 5.2 Production Configuration

**.env.production**:
```bash
DB_CONNECTION=pgsql
DB_HOST=your-db-host.railway.app
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=your_generated_password
DB_SSLMODE=require

# Enable connection pooling
DB_POOL_MIN=2
DB_POOL_MAX=20

# Cache queries
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### 5.3 Performance Tuning

**postgresql.conf** (on server):
```ini
# Connections
max_connections = 100

# Memory
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 16MB
maintenance_work_mem = 64MB

# WAL
wal_buffers = 16MB
checkpoint_completion_target = 0.9

# Query planner
default_statistics_target = 100
random_page_cost = 1.1  # For SSD
```

### Phase 6: Monitoring & Maintenance

#### 6.1 Setup Database Backups

**Daily Backups** (cron job):
```bash
#!/bin/bash
# backup-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/postgresql"
DB_NAME="rd_blog_prod"

mkdir -p $BACKUP_DIR

# Backup with compression
pg_dump -h localhost -U rd_blog_user $DB_NAME | gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"

# Keep last 30 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

Add to crontab:
```bash
0 2 * * * /path/to/backup-db.sh
```

#### 6.2 Monitoring Queries

**Install pgBadger** (log analysis):
```bash
brew install pgbadger

# Generate report
pgbadger /path/to/postgresql/logs/*.log -o report.html
```

**Query Performance Dashboard** (Laravel Telescope):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

## Migration Checklist

### Pre-Migration
- [ ] Backup SQLite database
- [ ] Install PostgreSQL locally
- [ ] Update `.env` with PostgreSQL credentials
- [ ] Test migrations on fresh PostgreSQL database
- [ ] Run full test suite
- [ ] Manual testing of all features

### Data Migration (If Applicable)
- [ ] Export data from SQLite
- [ ] Import data to PostgreSQL
- [ ] Verify record counts match
- [ ] Test application with migrated data
- [ ] Validate JSON data integrity

### Post-Migration
- [ ] Add PostgreSQL-specific indexes
- [ ] Optimize query patterns
- [ ] Setup query monitoring
- [ ] Configure backups
- [ ] Update CI/CD pipelines
- [ ] Update documentation

### Production
- [ ] Choose hosting provider
- [ ] Provision PostgreSQL instance
- [ ] Configure SSL/TLS
- [ ] Setup connection pooling
- [ ] Enable monitoring
- [ ] Test failover/recovery
- [ ] Document rollback procedure

## Rollback Plan

If issues arise during migration:

### Immediate Rollback
```bash
# 1. Revert .env
DB_CONNECTION=sqlite

# 2. Clear config cache
php artisan config:clear

# 3. Restart application
php artisan optimize:clear
```

### Data Recovery
```bash
# Restore from SQLite backup
cp database/database.sqlite.backup database/database.sqlite

# Or import from PostgreSQL dump
pg_dump rd_blog_dev > backup.sql
```

## Cost Analysis

### Local Development
- **PostgreSQL**: FREE (self-hosted)
- **Storage**: Minimal (<100MB for typical blog)

### Production Hosting

| Provider | Free Tier | Paid Plans | Best For |
|----------|-----------|------------|----------|
| Railway | 500 hrs/mo free | $5/mo starter | Quick deploys |
| Heroku | No free tier | $5/mo essential | Enterprise |
| DigitalOcean | None | $15/mo managed | Control |
| Supabase | 500MB, 2 projects | $25/mo pro | Realtime features |
| Neon | 0.5GB, 1 project | $19/mo launch | Serverless |

**Recommendation**: Start with Railway.app ($5/mo) or Neon (free tier)

## Timeline

| Phase | Duration | Tasks |
|-------|----------|-------|
| **Preparation** | 1 week | Install PostgreSQL, configure environment |
| **Validation** | 1 week | Test migrations, run tests, manual testing |
| **Data Migration** | 1 week | Export/import data, verify integrity |
| **Optimization** | 1 week | Add indexes, optimize queries |
| **Production** | 1 day | Deploy, configure, monitor |

**Total Estimated Time**: 4-5 weeks (parallel work possible)

## Success Metrics

### Performance
- [ ] Query response time < 100ms (95th percentile)
- [ ] Concurrent writes > 100/second
- [ ] Full-text search < 50ms

### Reliability
- [ ] 99.9% uptime
- [ ] Zero data loss
- [ ] Automated backups working

### Development
- [ ] All tests passing
- [ ] No SQLite-specific code remaining
- [ ] CI/CD working with PostgreSQL

## Next Steps

1. **Start Here**: Install PostgreSQL locally (Phase 1.1)
2. **Update .env**: Switch to PostgreSQL (Phase 1.3)
3. **Test**: Run migrations and tests (Phase 2)
4. **Document**: Any issues or PostgreSQL-specific behaviors

## Resources

- [Laravel Database Documentation](https://laravel.com/docs/11.x/database)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [pgLoader Documentation](https://pgloader.readthedocs.io/)
- [Railway PostgreSQL Guide](https://docs.railway.app/databases/postgresql)

---

**Questions or Issues?** Open an issue in the repository or consult the team lead.
