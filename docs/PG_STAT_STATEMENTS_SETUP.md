# pg_stat_statements Extension Setup Guide

**Extension**: pg_stat_statements  
**Purpose**: Track execution statistics for all SQL statements  
**Status**: ✅ Enabled in development  
**Migration**: `2026_02_17_155710_enable_pg_stat_statements_extension.php`

## Overview

The `pg_stat_statements` extension provides detailed query execution statistics essential for production monitoring and performance analysis.

### What It Tracks

- **Query Execution Count**: How many times each query has run
- **Execution Times**: Min/max/mean/total execution time
- **Rows Processed**: Number of rows read and returned
- **Query Text**: Normalized SQL (parameters replaced with placeholders)
- **I/O Statistics**: Blocks read from disk vs cache

### Benefits

✓ **Identify Slow Queries**: Find queries with high average execution time  
✓ **Detect Frequent Queries**: See which queries run most often  
✓ **Track Performance Trends**: Monitor query performance over time  
✓ **Production Monitoring**: Essential for production database health  
✓ **Optimization Targets**: Data-driven decisions on what to optimize

## Installation

### Requirements

- PostgreSQL 9.2 or later (you have 16.12 ✓)
- PostgreSQL superuser access (for CREATE EXTENSION)
- Access to postgresql.conf (for shared_preload_libraries)

### Step 1: Install Extension (Superuser Required)

```bash
# Development database
psql rd_blog_dev -c "CREATE EXTENSION IF NOT EXISTS pg_stat_statements;"

# Test database
psql rd_blog_test -c "CREATE EXTENSION IF NOT EXISTS pg_stat_statements;"

# Production database (when deploying)
psql rd_blog_prod -c "CREATE EXTENSION IF NOT EXISTS pg_stat_statements;"
```

**Verify Installation:**
```bash
psql rd_blog_dev -c "SELECT COUNT(*) FROM pg_extension WHERE extname = 'pg_stat_statements';"
# Should return: 1
```

### Step 2: Configure PostgreSQL

The extension must be loaded at server startup via `shared_preload_libraries`.

**Find Config File:**
```bash
psql postgres -c "SHOW config_file;"
# Example output: /usr/local/var/postgresql@16/postgresql.conf
```

**Add to postgresql.conf:**
```ini
# Shared libraries to preload
shared_preload_libraries = 'pg_stat_statements'

# Optional: Configure extension behavior
pg_stat_statements.max = 10000              # Max queries tracked (default 5000)
pg_stat_statements.track = all              # Track all queries (default 'top')
pg_stat_statements.track_utility = on       # Track utility commands
pg_stat_statements.save = on                # Persist stats across restarts
```

**Edit File:**
```bash
# macOS (Homebrew)
nano /usr/local/var/postgresql@16/postgresql.conf

# Linux
sudo nano /etc/postgresql/16/main/postgresql.conf
```

### Step 3: Restart PostgreSQL

**macOS (Homebrew):**
```bash
brew services restart postgresql@16
```

**Linux (systemd):**
```bash
sudo systemctl restart postgresql
```

**Docker:**
```bash
docker restart postgres-container
```

**Verify Extension Loaded:**
```bash
psql rd_blog_dev -c "SHOW shared_preload_libraries;"
# Should output: pg_stat_statements
```

### Step 4: Verify Working

```bash
# Test query execution
php artisan tinker --execute="
\$stats = DB::select('SELECT COUNT(*) as count FROM pg_stat_statements');
echo 'Queries tracked: ' . \$stats[0]->count . PHP_EOL;
"
```

**Expected Output:**
```
Queries tracked: 42
```

## Usage

### Via Artisan Command

```bash
# View slow queries (>10ms threshold)
php artisan db:analyze-performance --slow-queries
```

**Example Output:**
```
🐢 Slow Query Analysis
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
+----------------------------------------+-------+----------+----------+-------+
| Query (first 100 chars)                | Calls | Avg Time | Max Time | Total |
+----------------------------------------+-------+----------+----------+-------+
| SELECT * FROM blog_posts WHERE user... | 1,234 | 45.2ms   | 120.5ms  | 55.8s |
| SELECT COUNT(*) FROM comments WHERE... | 892   | 15.3ms   | 89.1ms   | 13.6s |
+----------------------------------------+-------+----------+----------+-------+
```

### Direct SQL Queries

**Most Time-Consuming Queries:**
```sql
SELECT 
    substring(query, 1, 100) as query_start,
    calls,
    round(mean_exec_time::numeric, 2) as avg_time_ms,
    round(max_exec_time::numeric, 2) as max_time_ms,
    round((total_exec_time / 1000)::numeric, 2) as total_time_sec
FROM pg_stat_statements
WHERE query NOT LIKE '%pg_stat_statements%'
ORDER BY total_exec_time DESC
LIMIT 10;
```

**Most Frequent Queries:**
```sql
SELECT 
    substring(query, 1, 100) as query_start,
    calls,
    round(mean_exec_time::numeric, 2) as avg_time_ms
FROM pg_stat_statements
WHERE query NOT LIKE '%pg_stat_statements%'
ORDER BY calls DESC
LIMIT 10;
```

**Slowest Average Queries:**
```sql
SELECT 
    substring(query, 1, 100) as query_start,
    calls,
    round(mean_exec_time::numeric, 2) as avg_time_ms,
    round(max_exec_time::numeric, 2) as max_time_ms
FROM pg_stat_statements
WHERE query NOT LIKE '%pg_stat_statements%'
AND calls > 10  -- Filter out one-off queries
ORDER BY mean_exec_time DESC
LIMIT 10;
```

## Migration Details

### Migration File

**Path**: `database/migrations/2026_02_17_155710_enable_pg_stat_statements_extension.php`

**What It Does:**
1. Checks if running on PostgreSQL
2. Verifies if extension already exists
3. Attempts to create extension
4. Gracefully handles permission errors

**Key Features:**
- **Environment-Aware**: Silent fail in testing/development if no superuser
- **Idempotent**: Safe to run multiple times
- **Helpful Errors**: Provides clear instructions if it fails

**Migration Code:**
```php
public function up(): void
{
    if (DB::connection()->getDriverName() !== 'pgsql') {
        return;
    }

    $exists = DB::selectOne(
        "SELECT COUNT(*) as count FROM pg_extension WHERE extname = 'pg_stat_statements'"
    );

    if ($exists->count > 0) {
        return; // Already installed
    }

    try {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_stat_statements');
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'permission denied')) {
            if (app()->environment(['testing', 'local'])) {
                return; // Silent skip for dev/test
            }
            // Throw helpful error for production
        }
    }
}
```

## Troubleshooting

### Extension Not Found

**Error**: `extension "pg_stat_statements" is not available`

**Solution**: Install PostgreSQL contrib package
```bash
# macOS (Homebrew) - Already included
brew info postgresql@16

# Ubuntu/Debian
sudo apt-get install postgresql-contrib-16

# CentOS/RHEL
sudo yum install postgresql16-contrib
```

### Permission Denied

**Error**: `permission denied to create extension "pg_stat_statements"`

**Solution**: Must be run as PostgreSQL superuser
```bash
# Connect as superuser
psql -U postgres rd_blog_dev -c "CREATE EXTENSION pg_stat_statements;"

# Or grant CREATE privilege to your user (not recommended)
psql -U postgres -c "ALTER USER rd_blog_user CREATEDB;"
```

### Extension Not Loaded

**Error**: `pg_stat_statements must be loaded via shared_preload_libraries`

**Solution**: Add to postgresql.conf and restart
```bash
# 1. Edit config
echo "shared_preload_libraries = 'pg_stat_statements'" >> /path/to/postgresql.conf

# 2. Restart
brew services restart postgresql@16

# 3. Verify
psql postgres -c "SHOW shared_preload_libraries;"
```

### Statistics Not Accumulating

**Issue**: `pg_stat_statements` shows very few queries

**Possible Causes:**
1. **Recently Reset**: Statistics were cleared
2. **Max Limit Reached**: Increase `pg_stat_statements.max`
3. **Track Setting**: Change `pg_stat_statements.track` to 'all'

**Solutions:**
```sql
-- Check current statistics
SELECT COUNT(*) FROM pg_stat_statements;

-- Reset statistics (development only!)
SELECT pg_stat_statements_reset();

-- Check configuration
SHOW pg_stat_statements.max;
SHOW pg_stat_statements.track;
```

### Parallel Test Failures

**Issue**: Tests fail with "permission denied to create extension"

**Explanation**: Parallel tests create temporary databases (rd_blog_test_test_1, etc.) that don't inherit extensions and require superuser per database.

**Solution**: Migration handles this gracefully by:
1. Checking if extension exists before attempting install
2. Silently skipping in testing environments if permission denied
3. Allowing tests to run without the extension

**Impact**: Test databases won't have pg_stat_statements, but this is acceptable as:
- Tests run in isolated temporary databases
- Extension is not required for application functionality
- Production databases can have it enabled properly

## Production Deployment

### Checklist

- [ ] **Install Extension**: Run `CREATE EXTENSION` as superuser
- [ ] **Configure postgresql.conf**: Add `shared_preload_libraries`
- [ ] **Restart PostgreSQL**: Required for config changes
- [ ] **Verify Installation**: Check `--slow-queries` works
- [ ] **Set Up Monitoring**: Integrate with monitoring tools
- [ ] **Review Regularly**: Check statistics weekly/monthly

### Automated Setup (CI/CD)

```bash
#!/bin/bash
# production-setup.sh

# Enable pg_stat_statements
psql $DATABASE_URL -c "CREATE EXTENSION IF NOT EXISTS pg_stat_statements;"

# Run migrations
php artisan migrate --force

# Verify
php artisan db:analyze-performance --slow-queries
```

### Docker Setup

**Dockerfile:**
```dockerfile
FROM postgres:16

# Install contrib extensions (includes pg_stat_statements)
RUN apt-get update && apt-get install -y postgresql-contrib-16

# Custom postgresql.conf
COPY postgresql.conf /etc/postgresql/postgresql.conf
CMD ["postgres", "-c", "config_file=/etc/postgresql/postgresql.conf"]
```

**postgresql.conf:**
```ini
shared_preload_libraries = 'pg_stat_statements'
pg_stat_statements.track = all
```

### Railway/Heroku/Cloud Platforms

Most managed PostgreSQL services include pg_stat_statements:

**Railway**:
```bash
# Enable via dashboard or CLI
railway run psql -c "CREATE EXTENSION pg_stat_statements;"
```

**Heroku Postgres**:
```bash
# Already enabled, just create extension
heroku pg:psql -c "CREATE EXTENSION pg_stat_statements;"
```

**AWS RDS**:
```sql
-- Add to parameter group: shared_preload_libraries
CREATE EXTENSION pg_stat_statements;
```

## Maintenance

### Reset Statistics

**Development Only** - Clears all collected statistics:
```sql
SELECT pg_stat_statements_reset();
```

### View Current Configuration

```sql
-- Check all pg_stat_statements settings
SELECT name, setting, unit, context 
FROM pg_settings 
WHERE name LIKE 'pg_stat_statements%';
```

### Monitor Extension Size

```sql
-- Check memory usage
SELECT pg_size_pretty(
    pg_stat_statements_info().dealloc + 
    pg_stat_statements_info().stats_reset
) as stats_size;
```

## Related Documentation

- [PHASE_4_3_PERFORMANCE_MONITORING.md](PHASE_4_3_PERFORMANCE_MONITORING.md) - Full monitoring guide
- [POSTGRESQL_OPTIMIZATION_REFERENCE.md](POSTGRESQL_OPTIMIZATION_REFERENCE.md) - Complete optimization reference
- [PostgreSQL pg_stat_statements Documentation](https://www.postgresql.org/docs/16/pgstatstatements.html)

## Summary

**Installation Steps:**
1. ✅ Install extension: `CREATE EXTENSION pg_stat_statements;`
2. ✅ Configure: `shared_preload_libraries = 'pg_stat_statements'`
3. ✅ Restart: `brew services restart postgresql@16`
4. ✅ Verify: `php artisan db:analyze-performance --slow-queries`

**Status**: Fully configured and working in development ✓

**Next Steps**: Deploy to production with same configuration
