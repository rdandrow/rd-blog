# Parallel Test Execution Fix

## Issue

`composer test` was failing when running tests in parallel mode due to PostgreSQL permission errors.

### Error Message
```
SQLSTATE[42501]: Insufficient privilege: 7 ERROR: permission denied to create database
(SQL: create database "rd_blog_test_test_1" encoding "utf8")
```

## Root Cause

When Pest runs tests with the `--parallel` flag, it creates separate temporary test databases for each parallel process:
- `rd_blog_test_test_1`
- `rd_blog_test_test_2`
- `rd_blog_test_test_3`
- etc. (up to 12 processes by default)

The `rd_blog_user` database user lacked the `CREATEDB` privilege required to create these temporary databases.

## Solution

Grant the `CREATEDB` privilege to the database user:

```bash
psql postgres -c "ALTER USER rd_blog_user CREATEDB;"
```

### Verification

Check that the user now has the privilege:
```bash
psql postgres -c "\du rd_blog_user"
```

Expected output:
```
             List of roles
  Role name   | Attributes | Member of 
--------------+------------+-----------
 rd_blog_user | Create DB  | {}
```

## Results

### Before Fix
```bash
./vendor/bin/pest --parallel
# Result: 500+ tests failing with permission errors
```

### After Fix
```bash
./vendor/bin/pest --parallel --compact
# Result: Tests: 906 passed (3,382 assertions)
# Duration: 8.11s
# Parallel: 12 processes
```

### Performance Improvement (Historical Benchmark - February 2026)
- **Sequential execution**: 24.28s (`./vendor/bin/pest`)
- **Parallel execution**: 8.11s (`./vendor/bin/pest --parallel`)
- **Speedup**: **3x faster** ⚡

Current backend full-suite baseline is approximately **9.66s** in parallel (`composer test`).

## Complete Setup for New Environments

When setting up PostgreSQL for the first time, include the `CREATEDB` privilege:

```sql
-- Create user with all necessary privileges
CREATE USER rd_blog_user WITH ENCRYPTED PASSWORD 'your_secure_password' CREATEDB;

-- Or alter existing user
ALTER USER rd_blog_user CREATEDB;

-- Create databases
CREATE DATABASE rd_blog_dev OWNER rd_blog_user;
CREATE DATABASE rd_blog_test OWNER rd_blog_user;

-- Grant schema permissions (PostgreSQL 15+)
\c rd_blog_dev
GRANT ALL ON SCHEMA public TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO rd_blog_user;

\c rd_blog_test
GRANT ALL ON SCHEMA public TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO rd_blog_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO rd_blog_user;
```

## Why Parallel Testing?

### Benefits
1. **3x faster test execution** (8s vs 24s)
2. **Faster CI/CD pipelines** - quicker feedback on PRs
3. **Improved developer experience** - less waiting during TDD
4. **True isolation** - each process gets its own database

### How It Works
Pest automatically:
1. Detects available CPU cores (default: 12 processes)
2. Creates temporary databases: `{base_name}_test_{n}`
3. Runs migrations on each temporary database
4. Distributes tests across processes
5. Cleans up temporary databases after completion

### Configuration

The parallel execution is configured in `composer.json`:
```json
{
    "scripts": {
        "test": [
            "@php artisan config:clear --ansi",
            "vendor/bin/pest --parallel"
        ],
        "test:sequential": [
            "@php artisan config:clear --ansi",
            "vendor/bin/pest"
        ]
    }
}
```

## Testing Commands

```bash
# Run all tests in parallel (fastest)
composer test

# Run tests sequentially (if debugging)
composer test:sequential

# Run specific test file in parallel
./vendor/bin/pest tests/Feature/Admin/BlogPostTest.php --parallel

# Control number of parallel processes
./vendor/bin/pest --parallel --processes=8

# Run with coverage (still parallel)
composer test:coverage
```

## Security Considerations

**Q: Is it safe to grant CREATEDB privilege?**

**A: Yes, for development and testing environments.** The user can only:
- Create databases (which are isolated)
- Cannot access other users' databases
- Cannot modify PostgreSQL system settings
- Cannot create other users

**For production:** Use a separate user without CREATEDB:
```sql
-- Production user (no CREATEDB)
CREATE USER rd_blog_prod WITH ENCRYPTED PASSWORD 'production_password';
GRANT ALL PRIVILEGES ON DATABASE rd_blog_prod TO rd_blog_prod;

-- Test user (with CREATEDB for parallel tests)
CREATE USER rd_blog_test WITH ENCRYPTED PASSWORD 'test_password' CREATEDB;
```

## Troubleshooting

### Issue: Tests still fail after granting CREATEDB

**Solution**: Clear the config cache
```bash
php artisan config:clear
```

### Issue: "Too many connections" error

**Solution**: Reduce parallel processes
```bash
./vendor/bin/pest --parallel --processes=4
```

Or increase PostgreSQL `max_connections` in `postgresql.conf`:
```
max_connections = 100  # Increase if needed
```

### Issue: Temporary databases not cleaned up

**Solution**: Manually drop them
```bash
psql postgres -c "SELECT datname FROM pg_database WHERE datname LIKE 'rd_blog_test_test_%';"

# Drop all at once (PostgreSQL 13+)
psql postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname LIKE 'rd_blog_test_test_%';"
psql postgres -c "DROP DATABASE IF EXISTS rd_blog_test_test_1, rd_blog_test_test_2, rd_blog_test_test_3;"
```

## Updated Documentation

The following files have been updated to reflect this fix:
- [docs/DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md#phase-1-preparation) - Added CREATEDB privilege to setup instructions
- [README.md](../README.md#testing) - Documents parallel test execution

## References

- [Pest Parallel Testing Docs](https://pestphp.com/docs/parallel-testing)
- [PostgreSQL CREATE DATABASE Privilege](https://www.postgresql.org/docs/current/sql-createdatabase.html)
- [Laravel Testing Database](https://laravel.com/docs/12.x/database-testing)

---

**Fixed**: February 16, 2026  
**Test Status**: ✅ 906/906 passing in parallel mode  
**Performance**: 8.11s (3x faster than sequential)
