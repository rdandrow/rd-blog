# Phase 4.3: Query Performance Monitoring

**Status**: ✅ Complete  
**Date**: February 16, 2026  
**Related**: [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md) Phase 4.3

## Overview

Implemented comprehensive PostgreSQL query performance monitoring and analysis tools to help identify and diagnose performance bottlenecks in development and staging environments.

## Implementation

### 1. Automatic Query Monitoring (AppServiceProvider)

**File**: `app/Providers/AppServiceProvider.php`

Added `enableQueryMonitoring()` method that automatically logs slow queries and detects N+1 query problems.

#### Features

**Multi-Tier Slow Query Detection:**
- **Warning Level** (>100ms): Logs query details for investigation
- **Error Level** (>500ms): Logs query with full stack trace for debugging
- **Query Log**: Enables DB query logging in debug mode

**N+1 Query Detection:**
- Warns when request executes >50 queries
- Helps identify missing eager loading

**Environment-Aware:**
- Only runs in `local` and `staging` environments
- Zero overhead in production

#### Monitoring Code

```php
protected function enableQueryMonitoring(): void
{
    if (!app()->environment(['local', 'staging'])) {
        return;
    }

    DB::listen(function ($query) {
        // Slow query warning (>100ms)
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
            ]);
        }

        // Extremely slow query error (>500ms)
        if ($query->time > 500) {
            Log::error('Extremely slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            ]);
        }

        // N+1 query detection
        if (config('app.debug') && DB::getQueryLog()) {
            $queryCount = count(DB::getQueryLog());
            if ($queryCount > 50) {
                Log::warning('Potential N+1 query problem detected', [
                    'query_count' => $queryCount,
                    'last_query' => $query->sql,
                ]);
            }
        }
    });

    // Enable query log in debug mode
    if (config('app.debug')) {
        DB::enableQueryLog();
    }
}
```

#### Log Examples

**Slow Query Warning:**
```
[2026-02-17 02:46:38] local.WARNING: Slow query detected 
{
    "sql":"SELECT pg_sleep(0.2)",
    "bindings":[],
    "time":"220.63ms",
    "connection":"pgsql"
}
```

**Extremely Slow Query Error:**
```
[2026-02-17 02:46:38] local.ERROR: Extremely slow query detected
{
    "sql":"SELECT * FROM blog_posts WHERE ...",
    "bindings":[...],
    "time":"650.2ms",
    "connection":"pgsql",
    "trace":[...]
}
```

**N+1 Detection Warning:**
```
[2026-02-17 02:46:38] local.WARNING: Potential N+1 query problem detected
{
    "query_count":75,
    "last_query":"SELECT * FROM users WHERE id = ?"
}
```

### 2. Manual Performance Analysis Command

**File**: `app/Console/Commands/AnalyzeQueryPerformance.php`

Created `db:analyze-performance` artisan command for on-demand PostgreSQL performance analysis.

#### Command Signature

```bash
php artisan db:analyze-performance [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--indexes` | Show index usage statistics only |
| `--slow-queries` | Show slow query patterns (requires pg_stat_statements) |
| `--cache` | Show cache hit rates only |
| `--table=TABLE` | Analyze specific table(s) (can be used multiple times) |
| *(no options)* | Show all statistics |

#### Example Usage

**Show All Statistics:**
```bash
php artisan db:analyze-performance
```

**Output:**
```
🔍 PostgreSQL Performance Analysis

📊 Index Usage Statistics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Table: blog_posts
+-------------------------------------+-------+-------------+-------+----------+
| Index                               | Scans | Tuples Read | Size  | Status   |
+-------------------------------------+-------+-------------+-------+----------+
| blog_posts_pkey                     | 638   | 638         | 16 kB | ✓ Used   |
| blog_posts_slug_unique              | 15    | 15          | 16 kB | ✓ Used   |
| blog_posts_tags_gin_index           | 0     | 0           | 16 kB | ⚠ Unused |
| blog_posts_search_index             | 0     | 0           | 72 kB | ⚠ Unused |
| blog_posts_published_date_index     | 0     | 0           | 16 kB | ⚠ Unused |
| blog_posts_featured_published_index | 0     | 0           | 16 kB | ⚠ Unused |
| blog_posts_drafts_by_author_index   | 0     | 0           | 16 kB | ⚠ Unused |
+-------------------------------------+-------+-------------+-------+----------+

📈 Table Statistics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
+-----------------+------+-------------+--------+--------+
| Table           | Rows | Dead Tuples | Size   | Vacuum |
+-----------------+------+-------------+--------+--------+
| blog_posts      | 25   | 0 (0%)      | 264 kB | Never  |
| comments        | 399  | 0 (0%)      | 160 kB | Never  |
| blog_post_likes | 239  | 0 (0%)      | 104 kB | Never  |
| users           | 17   | 5 (29.4%)   | 80 kB  | Never  |
| user_follows    | 90   | 0 (0%)      | 72 kB  | Never  |
+-----------------+------+-------------+--------+--------+
⚠ Table users has high dead tuple percentage (29.4%). Consider running VACUUM.

💾 Cache Hit Rates
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Database Cache Hit Rate: ✓ 100%
Excellent cache performance!
Index Cache Hit Rate: ✓ 99.52%
```

**Analyze Specific Table:**
```bash
php artisan db:analyze-performance --indexes --table=blog_posts
```

**Check Cache Performance:**
```bash
php artisan db:analyze-performance --cache
```

**Check Slow Queries:**
```bash
php artisan db:analyze-performance --slow-queries
```

### 3. Query Execution Plan Analysis Command

**File**: `app/Console/Commands/ExplainQuery.php`

Created `db:explain` artisan command for analyzing individual query execution plans using PostgreSQL's EXPLAIN ANALYZE.

#### Command Signature

```bash
php artisan db:explain {query?} [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `query` | SQL query to analyze (optional if using `--file`) |
| `--file=PATH` | Read query from file instead of argument |
| `--format=FORMAT` | Output format: `text` (default) or `json` |
| `--buffers` | Show buffer usage statistics |
| `--detailed` | Show detailed verbose output |
| `--costs` | Show cost estimates (enabled by default) |
| `--no-execute` | Run EXPLAIN without ANALYZE (no execution) |
| `--suggest` | Show optimization suggestions |

#### Features

1. **Color-Coded Output**:
   - 🟢 Green: Index scans (efficient)
   - 🟡 Yellow: Sequential scans (may need optimization)
   - 🔵 Blue: Cost estimates
   - 🟣 Magenta: Timing information

2. **Performance Assessment**:
   - ✓ Excellent: < 10ms
   - ⚠ Good: 10-100ms
   - ⚠ Slow: 100-500ms
   - ✗ Very Slow: > 500ms

3. **Optimization Suggestions**:
   - Detects sequential scans
   - Identifies missing indexes
   - Flags high query costs
   - Warns about external sorts
   - Highlights inefficient nested loops

#### Example Usage

**Basic Query Analysis:**
```bash
php artisan db:explain "SELECT * FROM blog_posts WHERE is_published = true LIMIT 5"
```

**Output:**
```
🔍 Analyzing Query Plan...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Query Execution Plan
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Limit  (cost=0.00..2.60 rows=5 width=1690) (actual time=0.015..0.019 rows=5 loops=1)
  ->  Seq Scan on blog_posts  (cost=0.00..10.40 rows=20 width=1690) (actual time=0.013..0.014 rows=5 loops=1)
        Filter: is_published
Planning Time: 3.710 ms
Execution Time: 0.048 ms

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Timing Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Planning Time: 3.71 ms
Execution Time: 0.048 ms
Total Time: 3.758 ms

✓ Excellent performance - Query executes very fast
```

**With Optimization Suggestions:**
```bash
php artisan db:explain "SELECT * FROM blog_posts WHERE content LIKE '%search%'" --suggest
```

**Output (with suggestions):**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Optimization Suggestions
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔍 Sequential scan detected on table 'blog_posts'
   Consider adding an index on the filtered columns
📊 Table scan with filter condition detected
   An index on the filter column(s) could improve performance
```

**Read Query from File:**
```bash
php artisan db:explain --file=complex_query.sql --buffers --detailed --suggest
```

**JSON Output (for programmatic use):**
```bash
php artisan db:explain "SELECT * FROM users" --format=json
```

**EXPLAIN Without Execution (for UPDATEs/DELETEs):**
```bash
php artisan db:explain "UPDATE blog_posts SET views = views + 1" --no-execute
```

**Complex Query with Multiple Options:**
```bash
php artisan db:explain "
  SELECT bp.*, u.name, COUNT(c.id) as comment_count
  FROM blog_posts bp
  LEFT JOIN users u ON bp.user_id = u.id
  LEFT JOIN comments c ON bp.id = c.blog_post_id
  WHERE bp.is_published = true
  GROUP BY bp.id, u.name
  ORDER BY bp.created_at DESC
  LIMIT 10
" --buffers --detailed --suggest
```

#### Use Cases

1. **Development**: Test query performance before production
2. **Optimization**: Identify slow queries and missing indexes
3. **Debugging**: Understand why a query is slow
4. **Learning**: See how PostgreSQL executes queries
5. **Documentation**: Include execution plans in technical docs

## Statistics Explained

### Index Usage Statistics

Shows how often each index is used by queries.

- **Scans**: Number of times the index was used
- **Tuples Read**: Number of rows read from the index
- **Size**: Disk space used by the index
- **Status**: 
  - ✓ Used: Index has been scanned at least once
  - ⚠ Unused: Index has never been used (consider dropping if persistent)

**Key Insights:**
- Unused indexes waste disk space and slow down writes
- High scan counts indicate valuable indexes
- Zero scans after significant usage may indicate redundant indexes

### Table Statistics

Shows table health and maintenance needs.

- **Rows**: Number of live tuples (actual rows)
- **Dead Tuples**: Obsolete row versions from UPDATEs/DELETEs
- **Size**: Total disk space (table + indexes + TOAST)
- **Vacuum**: Last vacuum operation (Auto/Manual/Never)

**Key Insights:**
- Dead tuple percentage >20% indicates need for VACUUM
- Large dead tuple counts slow down queries
- "Never" vacuum status on active tables needs investigation

### Cache Hit Rates

Shows how effectively PostgreSQL uses memory.

- **Database Cache Hit Rate**: Percentage of heap block reads from memory vs disk
- **Index Cache Hit Rate**: Percentage of index block reads from memory vs disk

**Performance Targets:**
- ✓ Excellent: >99% (ideal state)
- ⚠ Warning: 90-99% (acceptable, monitor)
- ✗ Critical: <90% (increase shared_buffers or add RAM)

**Key Insights:**
- High cache rates (>99%) = excellent performance
- Low rates suggest memory pressure or cold cache
- Persistent low rates may need PostgreSQL configuration tuning

### Slow Query Patterns

Requires `pg_stat_statements` extension to be enabled.

Shows queries with highest average execution times to help identify optimization targets.

**Enable pg_stat_statements:**

1. **Install the extension** (requires PostgreSQL superuser):
   ```sql
   CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
   ```

2. **Configure PostgreSQL** to load the extension:
   ```bash
   # Edit postgresql.conf
   shared_preload_libraries = 'pg_stat_statements'
   ```

3. **Restart PostgreSQL**:
   ```bash
   # macOS (Homebrew)
   brew services restart postgresql@16
   
   # Linux (systemd)
   sudo systemctl restart postgresql
   ```

4. **Verify installation**:
   ```bash
   php artisan db:analyze-performance --slow-queries
   ```

**Migration**: The `2026_02_17_155710_enable_pg_stat_statements_extension.php` migration attempts to install the extension automatically, but requires superuser privileges. In development, follow the manual steps above.

**Note**: Test databases created during parallel test execution will not have this extension (requires superuser per database), but the migration handles this gracefully.

## Performance Thresholds

### Slow Query Levels

| Threshold | Severity | Action | Log Level |
|-----------|----------|--------|-----------|
| >100ms | Warning | Investigate query optimization | WARNING |
| >500ms | Error | Immediate optimization needed | ERROR |

### N+1 Query Detection

| Threshold | Severity | Action |
|-----------|----------|--------|
| >50 queries | Warning | Check for missing eager loading |

### Dead Tuple Warning

| Threshold | Severity | Action |
|-----------|----------|--------|
| >20% | Warning | Run VACUUM on affected table |

### Cache Hit Rate

| Rate | Status | Action |
|------|--------|--------|
| >99% | Excellent | No action needed |
| 90-99% | Good | Monitor for trends |
| <90% | Critical | Increase shared_buffers or investigate |

## Troubleshooting

### Monitoring Not Working

**Symptom**: No slow query logs appearing in `storage/logs/laravel.log`

**Possible Causes:**
1. **Wrong Environment**: Monitoring only runs in local/staging
   ```bash
   php artisan tinker --execute="echo app()->environment();"
   ```

2. **APP_DEBUG Not Set**: Query log requires debug mode
   ```bash
   # .env
   APP_DEBUG=true
   ```

3. **Log Level Too High**: Check logging configuration
   ```php
   // config/logging.php
   'level' => 'debug', // or 'warning'
   ```

### Unused Indexes Showing Up

**Note**: Indexes may show as unused because:
- Database statistics were recently reset
- Tests don't exercise all query patterns
- Index is for future optimization
- Query planner prefers different index

**Check Index Usage History:**
```bash
php artisan db:analyze-performance --indexes
```

**Reset Statistics (if needed):**
```sql
SELECT pg_stat_reset();
```

### pg_stat_statements Not Working

**Symptom**: `--slow-queries` option shows "extension not enabled" message

**Solutions:**

1. **Check if extension is installed**:
   ```bash
   psql your_database -c "SELECT COUNT(*) FROM pg_extension WHERE extname = 'pg_stat_statements';"
   ```

2. **Check if shared_preload_libraries is configured**:
   ```bash
   psql your_database -c "SHOW shared_preload_libraries;"
   # Should show: pg_stat_statements
   ```

3. **Install extension manually** (as superuser):
   ```sql
   CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
   ```

4. **Add to postgresql.conf** if missing:
   ```bash
   # Find config file
   psql postgres -c "SHOW config_file;"
   
   # Add line (requires PostgreSQL restart)
   shared_preload_libraries = 'pg_stat_statements'
   ```

5. **Restart PostgreSQL** after configuration change:
   ```bash
   brew services restart postgresql@16  # macOS
   sudo systemctl restart postgresql    # Linux
   ```

### High Dead Tuple Percentage

**Solution**: Run VACUUM on the affected table

```sql
-- Manual vacuum
VACUUM VERBOSE users;

-- Aggressive vacuum (reclaims more space)
VACUUM FULL users;

-- Update statistics
ANALYZE users;
```

**Prevention**: Ensure autovacuum is enabled
```sql
-- Check autovacuum settings
SHOW autovacuum;
SELECT * FROM pg_settings WHERE name LIKE 'autovacuum%';
```

### Low Cache Hit Rates

**Solutions:**

1. **Increase shared_buffers** (PostgreSQL configuration)
   ```
   # postgresql.conf
   shared_buffers = 256MB  # or 25% of RAM
   ```

2. **Warm up the cache** after restart
   ```bash
   # Run common queries after PostgreSQL restart
   php artisan db:seed --class=WarmCacheSeeder
   ```

3. **Check if queries are cacheable**
   - Some queries (like RANDOM()) bypass cache
   - Large sequential scans may not benefit from cache

## Performance Optimization Workflow

### 1. Identify Slow Queries

```bash
# Check logs for slow queries
tail -f storage/logs/laravel.log | grep "Slow query"

# Or use the command
php artisan db:analyze-performance --slow-queries
```

### 2. Analyze Execution Plan

```bash
php artisan tinker
```

```php
DB::select('EXPLAIN ANALYZE SELECT ...');
```

### 3. Check Index Usage

```bash
php artisan db:analyze-performance --indexes --table=blog_posts
```

### 4. Verify Cache Performance

```bash
php artisan db:analyze-performance --cache
```

### 5. Implement Fix

- Add missing indexes
- Add eager loading to reduce N+1 queries
- Optimize query structure
- Add query result caching

### 6. Verify Improvement

```bash
# Check logs again
tail -f storage/logs/laravel.log

# Re-run performance analysis
php artisan db:analyze-performance
```

## Testing

### Verify Monitoring Works

**Test Slow Query Detection:**
```bash
php artisan tinker --execute="DB::select('SELECT pg_sleep(0.2)');"
tail -1 storage/logs/laravel.log
# Should show: local.WARNING: Slow query detected
```

**Test Extremely Slow Query:**
```bash
php artisan tinker --execute="DB::select('SELECT pg_sleep(0.6)');"
tail -1 storage/logs/laravel.log
# Should show: local.ERROR: Extremely slow query detected (with stack trace)
```

**Test N+1 Detection:**
```php
// In tinker
DB::enableQueryLog();
\App\Models\BlogPost::all()->each(fn($post) => $post->user);
count(DB::getQueryLog()); // Should trigger warning if >50 queries
```

### Test Performance Command

```bash
# Test all features
php artisan db:analyze-performance

# Test specific options
php artisan db:analyze-performance --indexes
php artisan db:analyze-performance --cache
php artisan db:analyze-performance --table=blog_posts

# Test on non-PostgreSQL (should fail gracefully)
# Temporarily switch DB_CONNECTION=sqlite
php artisan db:analyze-performance
# Should show: "This command only works with PostgreSQL databases."
```

## Integration with Existing Tools

### Laravel Telescope (Optional)

For more advanced monitoring in development:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Telescope provides:
- Query timeline visualization
- Request/response inspection
- Job/queue monitoring
- Exception tracking

### Laravel Debugbar (Optional)

For per-request query analysis:

```bash
composer require barryvdh/laravel-debugbar --dev
```

## Best Practices

### Development

1. **Run Performance Analysis Regularly**
   ```bash
   php artisan db:analyze-performance
   ```

2. **Monitor Logs During Feature Development**
   ```bash
   tail -f storage/logs/laravel.log | grep -E "(Slow query|N+1)"
   ```

3. **Test with Production-Like Data Volume**
   - Small datasets hide performance issues
   - Use seeders to create realistic test data

### Staging

1. **Enable Monitoring**
   ```bash
   # .env.staging
   APP_ENV=staging
   APP_DEBUG=true
   ```

2. **Run Load Tests with Monitoring Active**
   ```bash
   # Monitor logs during load testing
   tail -f storage/logs/laravel.log
   ```

3. **Analyze Results After Testing**
   ```bash
   php artisan db:analyze-performance
   ```

### Production

1. **Disable Automatic Monitoring**
   ```bash
   # .env.production
   APP_ENV=production  # Monitoring automatically disabled
   APP_DEBUG=false
   ```

2. **Use External Monitoring Tools**
   - New Relic APM
   - Datadog
   - Scout APM
   - AppSignal

3. **Enable pg_stat_statements**
   ```sql
   -- In production PostgreSQL
   CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
   ```

4. **Schedule Performance Reports**
   ```bash
   # Run weekly analysis and email results
   php artisan db:analyze-performance > /tmp/db-report.txt
   mail -s "Weekly DB Performance Report" admin@example.com < /tmp/db-report.txt
   ```

## Related Documentation

- [POSTGRESQL_INDEXES.md](POSTGRESQL_INDEXES.md) - Index design and rationale
- [POSTGRESQL_INDEX_IMPLEMENTATION.md](POSTGRESQL_INDEX_IMPLEMENTATION.md) - Index migration details
- [PHASE_4_2_QUERY_OPTIMIZATION.md](PHASE_4_2_QUERY_OPTIMIZATION.md) - Full-text search optimization
- [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md) - Overall migration plan

## Performance Improvements

### Before Monitoring

- No visibility into slow queries
- N+1 problems discovered in production
- Index usage unknown
- Cache performance unmeasured

### After Monitoring

- **Automatic Detection**: Slow queries logged immediately with context
- **Proactive Optimization**: Issues caught in development/staging
- **Data-Driven Decisions**: Index usage statistics guide optimization
- **Health Monitoring**: Cache hit rates and table bloat tracking

## Summary

Phase 4.3 provides comprehensive PostgreSQL performance monitoring through:

1. **Automatic Monitoring** (AppServiceProvider)
   - 3-tier slow query detection (100ms, 500ms)
   - N+1 query problem detection
   - Environment-aware (local/staging only)

2. **Manual Analysis Command** (db:analyze-performance)
   - Index usage statistics
   - Table health metrics
   - Cache hit rate analysis
   - Slow query patterns (with pg_stat_statements)

3. **Developer-Friendly**
   - Clear, actionable output
   - Helpful warnings and recommendations
   - Multiple filtering options
   - Works seamlessly with existing tests

**Status**: ✅ Fully implemented and tested
- All features working correctly
- Monitoring confirmed in logs
- Command tested with all options
- Documentation complete
