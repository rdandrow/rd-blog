# CI PostgreSQL Setup Guide

**Date**: March 5, 2026  
**CI Platform**: GitHub Actions  
**Database**: PostgreSQL 16

## Overview

This guide documents the PostgreSQL configuration for CI/CD environments to ensure database connectivity during automated testing.

**Fork PR support:** The workflow uses `||` fallback defaults for all secrets, so CI runs without any repository secrets configured. This means pull requests from forks work out of the box. Repository secrets (`DB_TEST_USERNAME`, `DB_TEST_PASSWORD`, `DB_TEST_DATABASE`) only need to be set if you want to override the defaults on push to protected branches.

## Configuration

### GitHub Actions Workflow

**File**: `.github/workflows/tests.yml`

### PostgreSQL Service

```yaml
    # Safe CI defaults are used when repository secrets are absent (e.g. fork PRs).
    # On push to protected branches, configure the secrets below to override:
    #   DB_TEST_USERNAME / DB_TEST_PASSWORD / DB_TEST_DATABASE
    env:
      DB_USERNAME: ${{ secrets.DB_TEST_USERNAME || 'rd_blog_user' }}
      DB_PASSWORD: ${{ secrets.DB_TEST_PASSWORD || 'secret' }}
      DB_DATABASE: ${{ secrets.DB_TEST_DATABASE || 'rd_blog_test' }}

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: ${{ secrets.DB_TEST_USERNAME || 'rd_blog_user' }}
          POSTGRES_PASSWORD: ${{ secrets.DB_TEST_PASSWORD || 'secret' }}
          POSTGRES_DB: ${{ secrets.DB_TEST_DATABASE || 'rd_blog_test' }}
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
```

**Key Features:**
- **Health checks**: Ensures PostgreSQL is ready before tests run
- **Port mapping**: Makes PostgreSQL accessible on standard port 5432
- **Automatic startup**: Service starts before job steps execute
- **Secrets with fallbacks**: Repository secrets override the defaults; when absent (fork PRs), the hardcoded defaults are used — credentials are ephemeral and only reachable inside the CI runner

### PHP Extensions

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: 8.4
    extensions: pdo, pdo_pgsql, pgsql  # PostgreSQL extensions
    tools: composer:v2
    coverage: xdebug
```

**Required Extensions:**
- `pdo` - PHP Data Objects interface
- `pdo_pgsql` - PostgreSQL driver for PDO
- `pgsql` - Native PostgreSQL functions

### Database Configuration

```yaml
- name: Configure Database
  # DB_CONNECTION, DB_HOST, and DB_PORT are already set in .env.example.
  # Append the remaining credentials so artisan commands (migrate, etc.) resolve them.
  run: |
    echo "DB_DATABASE=${{ env.DB_DATABASE }}" >> .env
    echo "DB_USERNAME=${{ env.DB_USERNAME }}" >> .env
    echo "DB_PASSWORD=${{ env.DB_PASSWORD }}" >> .env
    php artisan config:clear
```

**Steps:**
1. Append the three credentials (resolved from secrets with fallback defaults) to `.env`
2. Clear cached config *after* writing credentials so the values are picked up

**Note:** `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, and `DB_PORT=5432` are already present in `.env.example` — no string replacement or extra `echo` lines needed.

`DB_SSLMODE` defaults to `prefer` for local/CI convenience. For production, prefer `require` or certificate-verified modes (`verify-ca` / `verify-full`).

### Migration Execution

```yaml
- name: Run Migrations
  run: php artisan migrate --force

- name: Verify FTS Language Alignment
  run: php artisan db:check-fts-language
```

**Important:**
- `--force` flag required in CI (non-interactive environment)
- Runs all pending migrations before tests
- Creates all tables, indexes, and optimizations
- Validates full-text search language alignment immediately after migrations

### FTS Language Guard (New)

The workflow runs `php artisan db:check-fts-language` to verify:
- `database.full_text_search.language` / `DB_FTS_LANGUAGE`
- language embedded in `blog_posts_search_index`

If they differ, CI fails fast with remediation guidance.

## Testing Configuration

### phpunit.xml

```xml
<php>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_HOST"       value="127.0.0.1"/>
    <env name="DB_PORT"       value="5432"/>
    <env name="DB_DATABASE"   value="rd_blog_test"/>
    <env name="DB_USERNAME"   value="rd_blog_user"/>
    <!-- DB_PASSWORD must be set in your shell environment or .env — never hardcode credentials here -->
    <!-- Other test environment variables -->
</php>
```

**Ensures:**
- Tests always use PostgreSQL with a consistent, complete connection tuple
- Consistent test database name and user (`rd_blog_user`)
- Isolated from development database
- `DB_PASSWORD` must be supplied via shell environment or `.env` (not committed)

## Troubleshooting

### Issue 1: Connection Refused

**Error:**
```
SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 5432 failed: Connection refused
```

**Causes:**
1. PostgreSQL service not configured in workflow
2. Missing health checks (service not ready)
3. Incorrect host/port configuration

**Solutions:**
- ✅ Add `services.postgres` section to workflow
- ✅ Include health check options
- ✅ Use `127.0.0.1` as host (not `localhost`)
- ✅ Wait for service to be healthy before running tests

### Issue 2: Missing PHP Extensions

**Error:**
```
could not find driver
```

**Cause:** PostgreSQL PHP extensions not installed

**Solution:**
```yaml
extensions: pdo, pdo_pgsql, pgsql
```

### Issue 3: Database Does Not Exist

**Error:**
```
SQLSTATE[08006] database "rd_blog_test" does not exist
```

**Cause:** Database not created by service

**Solution:**
Specify `POSTGRES_DB` in service configuration:
```yaml
env:
  POSTGRES_DB: rd_blog_test
```

### Issue 4: Authentication Failed

**Error:**
```
SQLSTATE[08006] password authentication failed
```

**Cause:** Credentials mismatch between service and .env

**Solution:**
Ensure matching credentials:
- Service: `POSTGRES_USER`, `POSTGRES_PASSWORD`
- Config: `DB_USERNAME`, `DB_PASSWORD`

### Issue 5: Migrations Not Running

**Error:**
```
SQLSTATE[42P01]: Undefined table
```

**Cause:** Migrations not executed before tests

**Solution:**
Add migration step before tests:
```yaml
- name: Run Migrations
  run: php artisan migrate --force
```

### Issue 6: FTS Language Mismatch

**Error pattern:**
```
Full-text language mismatch detected.
Config language : <x>
Index language  : <y>
```

**Cause:** `DB_FTS_LANGUAGE` changed, but `blog_posts_search_index` was created with a different language.

**Solution:** Rebuild the index with the configured language in a new migration.

```php
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

    DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_search_index');
    DB::statement("\n            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_search_index ON blog_posts\n            USING GIN (\n                to_tsvector('{$language}',\n                    coalesce(title, '') || ' ' ||\n                    coalesce(excerpt, '') || ' ' ||\n                    coalesce(content, '')\n                )\n            )\n        ");
  }
};
```

Then verify locally:
```bash
php artisan db:check-fts-language
```

## Verification

### Check Service Status

GitHub Actions will show service health in logs:
```
Waiting for postgres to be healthy...
postgres service is healthy
```

### Verify Connection

Add a verification step (optional):
```yaml
- name: Verify Database Connection
  run: |
    php artisan tinker --execute="
      echo 'PostgreSQL Version: ' . DB::selectOne('SELECT version()')->version;
      echo '\nTest Database: ' . config('database.connections.pgsql.database');
    "
```

### Check Indexes

Verify PostgreSQL optimizations are applied:
```yaml
- name: Verify Indexes
  run: |
    php artisan db:analyze-performance --indexes
```

## Best Practices

### 1. Use Health Checks

**Always include health checks** to ensure PostgreSQL is ready:
```yaml
options: >-
  --health-cmd pg_isready
  --health-interval 10s
  --health-timeout 5s
  --health-retries 5
```

### 2. Match Local Environment

**Keep CI and local environments consistent:**
- Same PostgreSQL version (16)
- Same database name pattern
- Same user permissions

### 3. Cache Dependencies

**Speed up CI with caching:**
```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
```

### 4. Parallel Testing

**For faster test execution:**
```yaml
- name: Backend Tests
  run: ./vendor/bin/pest --parallel --processes=4
```

**Note:** Requires `CREATEDB` privilege (already configured in PostgreSQL service)

### 5. Separate Test Database

**Never use production/development database in CI:**
- Development: `rd_blog_dev`
- Testing: `rd_blog_test`
- CI: `rd_blog_test` (isolated)

## CI Workflow Steps (Order Matters)

```yaml
1. Checkout code
2. Setup PHP (with extensions)
3. Setup Node
4. Install dependencies (Composer, NPM)
5. Copy .env.example
6. Configure database credentials
7. Generate application key
8. Run migrations ← Before tests!
9. Build assets
10. Run tests (backend, frontend)
```

**Critical:** Migrations must run before tests!

## Environment Variables

### Service (Docker Container)

```yaml
POSTGRES_USER=rd_blog_user        # default; overridden by DB_TEST_USERNAME secret
POSTGRES_PASSWORD=secret          # default; overridden by DB_TEST_PASSWORD secret
POSTGRES_DB=rd_blog_test          # default; overridden by DB_TEST_DATABASE secret
```

### Application (.env in CI)

```bash
# From .env.example (already present):
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432

# Appended by the «Configure Database» step:
DB_DATABASE=rd_blog_test          # or $DB_TEST_DATABASE secret
DB_USERNAME=rd_blog_user          # or $DB_TEST_USERNAME secret
DB_PASSWORD=secret                # or $DB_TEST_PASSWORD secret
```

**Must Match:** User, password, and database name between service and application config

## Local Testing of CI Configuration

### Simulate CI Environment

```bash
# 1. Start PostgreSQL with same config
docker run --name test-postgres \
  -e POSTGRES_USER=rd_blog_user \
  -e POSTGRES_PASSWORD=secret \
  -e POSTGRES_DB=rd_blog_test \
  -p 5432:5432 \
  -d postgres:16

# 2. Configure .env  (DB_CONNECTION, DB_HOST, DB_PORT already set in .env.example)
cp .env.example .env
echo "DB_DATABASE=rd_blog_test" >> .env
echo "DB_USERNAME=rd_blog_user" >> .env
echo "DB_PASSWORD=secret"       >> .env

# 3. Run migrations
php artisan migrate --force

# 4. Run tests
./vendor/bin/pest

# 5. Cleanup
docker stop test-postgres
docker rm test-postgres
```

## Performance in CI

### Typical Execution Times

```
Service startup:     ~10-15 seconds
PHP setup:           ~5 seconds
Dependencies:        ~30-60 seconds (with cache)
Migrations:          ~5-10 seconds
Tests (906 tests):   ~12-20 seconds
Total:               ~60-100 seconds
```

### Optimization Tips

1. **Cache Composer dependencies** - Saves 30-45 seconds
2. **Cache npm packages** - Saves 20-30 seconds
3. **Use parallel testing** - Saves 50% test time
4. **Skip coverage** if not needed - Saves 20% test time

## References

- [GitHub Actions PostgreSQL Service](https://docs.github.com/en/actions/using-containerized-services/creating-postgresql-service-containers)
- [Setup PHP Action](https://github.com/shivammathur/setup-php)
- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)
- [PostgreSQL Optimization Reference](POSTGRESQL_OPTIMIZATION_REFERENCE.md)

## Summary

✅ **PostgreSQL 16 service** configured with health checks  
✅ **PHP extensions** (pdo, pdo_pgsql, pgsql) installed  
✅ **Database credentials** properly configured  
✅ **Migrations** run before tests  
✅ **Parallel testing** enabled (CREATEDB privilege)  
✅ **All 906 tests** should pass in CI  

**Key Takeaway:** The PostgreSQL service must be configured in the GitHub Actions workflow with proper health checks, and database credentials must be set before running migrations and tests.
