# PostgreSQL Connection Pooling Guide

**Project**: rd-blog  
**Database**: PostgreSQL 16  
**Target**: Production & High-Traffic Environments  
**Updated**: February 17, 2026

## Table of Contents

- [Overview](#overview)
- [Why Connection Pooling?](#why-connection-pooling)
- [PgBouncer Setup](#pgbouncer-setup)
- [Configuration](#configuration)
- [Laravel Integration](#laravel-integration)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

## Overview

Connection pooling is a critical optimization for PostgreSQL applications that need to handle many concurrent connections efficiently. Instead of creating a new database connection for every request, a connection pooler maintains a pool of reusable connections.

### Performance Benefits

| Metric | Without Pooling | With PgBouncer | Improvement |
|--------|----------------|----------------|-------------|
| Connection Time | 5-50ms | <1ms | **50x faster** |
| Max Concurrent Connections | 100-200 | 1000+ | **10x more** |
| Memory Usage | High | Low | **70% reduction** |
| Response Time (P95) | 200ms | 50ms | **4x faster** |

### When You Need Connection Pooling

✅ **Use connection pooling when:**
- Handling >100 concurrent requests
- Deploying serverless functions (Lambda, Cloud Functions)
- Using connection-heavy frameworks (multiple workers)
- Database connection limit reached frequently
- High connection/disconnection overhead observed

❌ **Skip connection pooling when:**
- Low traffic (<50 concurrent users)
- Single-process application
- Development/testing environments
- Database has unlimited connections

## Why Connection Pooling?

### The Problem

PostgreSQL connections are expensive:

```
Each connection costs:
├── 10MB of memory (per backend process)
├── 5-50ms connection establishment time
├── SSL/TLS handshake overhead
└── Authentication overhead
```

**Example**: 100 concurrent Laravel workers × 10MB = **1GB memory** just for connections!

### The Solution

PgBouncer acts as a lightweight proxy:

```
[Laravel Workers] → [PgBouncer] → [PostgreSQL]
    1000 clients        20 conns      20 conns
```

**Benefits**:
- **Connection Reuse**: One connection serves multiple requests
- **Memory Efficiency**: Fewer PostgreSQL backend processes
- **Faster Response**: No connection establishment overhead
- **Connection Limiting**: Protect database from overload

## PgBouncer Setup

### Installation

#### macOS (Homebrew)
```bash
brew install pgbouncer

# Verify installation
pgbouncer --version
# PgBouncer 1.22.0
```

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install pgbouncer

# Verify
pgbouncer --version
```

#### Docker
```bash
# Pull official image
docker pull pgbouncer/pgbouncer:latest

# Run with configuration
docker run -d \
  --name pgbouncer \
  -p 6432:6432 \
  -v /path/to/pgbouncer.ini:/etc/pgbouncer/pgbouncer.ini \
  -v /path/to/userlist.txt:/etc/pgbouncer/userlist.txt \
  pgbouncer/pgbouncer
```

### Configuration Files

#### 1. Create pgbouncer.ini

```ini
[databases]
; Database connection string
; Format: dbname = host=hostname port=5432 dbname=database
rd_blog_prod = host=localhost port=5432 dbname=rd_blog_prod user=rd_blog_user password=your_password

; Fallback database (optional)
* = host=localhost port=5432

[pgbouncer]
; Connection pooling mode
; - session: Connection returned to pool after session ends (safest)
; - transaction: Connection returned after each transaction (recommended)
; - statement: Connection returned after each statement (aggressive, may break some features)
pool_mode = transaction

; Listening address and port
listen_addr = 127.0.0.1
listen_port = 6432

; Authentication
auth_type = md5
auth_file = /etc/pgbouncer/userlist.txt

; Connection limits
max_client_conn = 1000        ; Maximum client connections
default_pool_size = 20        ; Connections per database
min_pool_size = 5             ; Minimum idle connections
reserve_pool_size = 5         ; Emergency reserve connections
reserve_pool_timeout = 5      ; Seconds to wait for emergency connection

; Connection lifetime
max_db_connections = 50       ; Total PostgreSQL connections
max_user_connections = 50     ; Connections per user

; Timeouts (in seconds)
server_lifetime = 3600        ; Close server connection after this time
server_idle_timeout = 600     ; Close idle server connection
query_timeout = 0             ; Query execution timeout (0 = disabled)
query_wait_timeout = 120      ; Maximum time queries wait for connection
client_idle_timeout = 0       ; Disconnect idle clients (0 = disabled)

; Logging
log_connections = 1
log_disconnections = 1
log_pooler_errors = 1
stats_period = 60

; Admin console
admin_users = admin
```

#### 2. Create userlist.txt

PgBouncer needs user credentials in MD5 format:

```bash
# Generate MD5 password hash
echo -n "passwordusername" | md5sum
# or on macOS:
echo -n "passwordusername" | md5

# Example for user 'rd_blog_user' with password 'secret123':
echo -n "secret123rd_blog_user" | md5
# Output: 9d7b8f2a1c4e5d6f3a8b1c2d3e4f5a6b
```

**userlist.txt format:**
```
"rd_blog_user" "md59d7b8f2a1c4e5d6f3a8b1c2d3e4f5a6b"
"admin" "md5e8b7d9c6a5f4e3d2c1b0a9f8e7d6c5b4"
```

### Starting PgBouncer

```bash
# Start PgBouncer
pgbouncer -d /etc/pgbouncer/pgbouncer.ini

# Check if running
ps aux | grep pgbouncer

# Test connection
psql -h 127.0.0.1 -p 6432 -U rd_blog_user rd_blog_prod
```

### macOS LaunchAgent (Auto-start)

Create `/Library/LaunchDaemons/pgbouncer.plist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>pgbouncer</string>
    <key>ProgramArguments</key>
    <array>
        <string>/opt/homebrew/bin/pgbouncer</string>
        <string>/opt/homebrew/etc/pgbouncer.ini</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardErrorPath</key>
    <string>/usr/local/var/log/pgbouncer.err</string>
    <key>StandardOutPath</key>
    <string>/usr/local/var/log/pgbouncer.log</string>
</dict>
</plist>
```

```bash
# Load service
sudo launchctl load /Library/LaunchDaemons/pgbouncer.plist

# Verify
sudo launchctl list | grep pgbouncer
```

## Configuration

### Pool Modes Comparison

| Mode | Description | Use Case | Compatibility |
|------|-------------|----------|---------------|
| **session** | Connection held for entire session | Long transactions, prepared statements | ✅ All features work |
| **transaction** | Connection returned after each transaction | Web applications, REST APIs | ⚠️ No session-level features |
| **statement** | Connection returned after each statement | Serverless, microservices | ❌ Many features break |

#### Recommended: Transaction Mode

Best balance for Laravel applications:

```ini
pool_mode = transaction
default_pool_size = 20
max_client_conn = 1000
```

**Limitations in transaction mode:**
- ❌ Prepared statements (Laravel doesn't use by default)
- ❌ Cursors (rarely used in Laravel)
- ❌ Advisory locks
- ❌ `LISTEN`/`NOTIFY` (use Redis for pub/sub instead)
- ✅ Transactions work perfectly
- ✅ All Laravel queries work
- ✅ Connection pooling benefits

### Calculating Pool Size

**Formula**: `pool_size = (2 × CPU_cores) + effective_spindle_count`

**Examples**:

```bash
# 4-core server with SSD
pool_size = (2 × 4) + 1 = 9
# Recommended: 10-15 connections

# 8-core server with SSD
pool_size = (2 × 8) + 1 = 17
# Recommended: 20-25 connections

# 16-core server with RAID
pool_size = (2 × 16) + 4 = 36
# Recommended: 40-50 connections
```

**General Guidelines**:
- **Small apps** (<1000 req/min): 10-20 connections
- **Medium apps** (1000-10000 req/min): 20-50 connections
- **Large apps** (>10000 req/min): 50-100 connections

## Laravel Integration

### Update .env

```env
# Before (direct connection)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rd_blog_prod
DB_USERNAME=rd_blog_user
DB_PASSWORD=your_password

# After (with PgBouncer)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=6432                    # PgBouncer port
DB_DATABASE=rd_blog_prod
DB_USERNAME=rd_blog_user
DB_PASSWORD=your_password
DB_POOL_SIZE=20                 # Optional: document pool size
```

### config/database.php

No changes needed! Laravel automatically uses the connection settings from `.env`.

Optional: Add connection pool metadata for documentation:

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
    'sslmode' => 'prefer',
    
    // Connection pool metadata (for documentation)
    'options' => [
        'pool_size' => env('DB_POOL_SIZE', 20),
        'pool_mode' => env('DB_POOL_MODE', 'transaction'),
    ],
],
```

### Testing the Connection

```bash
# Test database connection through PgBouncer
php artisan db:explain "SELECT 1"

# Run migrations
php artisan migrate --force

# Verify application works
php artisan tinker
>>> DB::select('SELECT current_database(), current_user');
```

### Deployment Configuration

#### Production .env

```env
# Production with PgBouncer
DB_CONNECTION=pgsql
DB_HOST=pgbouncer.internal        # Internal PgBouncer hostname
DB_PORT=6432
DB_DATABASE=rd_blog_prod
DB_USERNAME=rd_blog_user
DB_PASSWORD=${DB_PASSWORD}        # From environment/secrets

# Connection pool settings
DB_POOL_SIZE=25
DB_POOL_MODE=transaction

# SSL mode for PgBouncer → PostgreSQL
DB_SSLMODE=require
```

#### Docker Compose Setup

```yaml
version: '3.8'

services:
  postgres:
    image: postgres:16
    environment:
      POSTGRES_USER: rd_blog_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      POSTGRES_DB: rd_blog_prod
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U rd_blog_user"]
      interval: 10s
      timeout: 5s
      retries: 5

  pgbouncer:
    image: pgbouncer/pgbouncer:latest
    environment:
      DATABASES_HOST: postgres
      DATABASES_PORT: 5432
      DATABASES_DBNAME: rd_blog_prod
      DATABASES_USER: rd_blog_user
      DATABASES_PASSWORD: ${DB_PASSWORD}
      PGBOUNCER_POOL_MODE: transaction
      PGBOUNCER_DEFAULT_POOL_SIZE: 25
      PGBOUNCER_MAX_CLIENT_CONN: 1000
    ports:
      - "6432:6432"
    depends_on:
      postgres:
        condition: service_healthy

  app:
    build: .
    environment:
      DB_HOST: pgbouncer
      DB_PORT: 6432
      DB_DATABASE: rd_blog_prod
      DB_USERNAME: rd_blog_user
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      - pgbouncer

volumes:
  postgres_data:
```

## Monitoring

### PgBouncer Admin Console

Connect to admin console:

```bash
psql -h 127.0.0.1 -p 6432 -U admin pgbouncer
```

#### Show Pool Status

```sql
pgbouncer=# SHOW POOLS;
```

**Output:**
```
 database     | user          | cl_active | cl_waiting | sv_active | sv_idle | sv_used | sv_tested | sv_login | maxwait
--------------+---------------+-----------+------------+-----------+---------+---------+-----------+----------+---------
 rd_blog_prod | rd_blog_user  |        15 |          2 |         8 |       5 |      12 |         0 |        0 |       0
```

**Key Metrics:**
- `cl_active`: Active client connections
- `cl_waiting`: Clients waiting for connection
- `sv_active`: Active server (PostgreSQL) connections
- `sv_idle`: Idle server connections in pool
- `maxwait`: Longest wait time (seconds)

#### Show Statistics

```sql
pgbouncer=# SHOW STATS;
```

**Output:**
```
 database     | total_xact_count | total_query_count | total_received | total_sent | total_xact_time | avg_xact_time
--------------+------------------+-------------------+----------------+------------+-----------------+--------------
 rd_blog_prod |          1523456 |           4567890 |    12345678901 | 9876543210 |     45678901234 |           30
```

#### Show Configuration

```sql
pgbouncer=# SHOW CONFIG;
```

#### Reload Configuration

```sql
pgbouncer=# RELOAD;
```

### Monitoring Commands

```bash
# Watch pool status in real-time
watch -n 1 'psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "SHOW POOLS;"'

# Check for waiting clients
psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "SHOW POOLS;" | grep -v " 0 |"

# Monitor connection usage
psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "SHOW STATS;"
```

### Laravel Performance Monitoring

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\DB;

public function boot(): void
{
    if (app()->environment(['local', 'staging'])) {
        DB::listen(function ($query) {
            // Log slow queries
            if ($query->time > 100) {
                logger()->warning('Slow query through PgBouncer', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }
}
```

### Metrics to Track

| Metric | Target | Alert Threshold | Action |
|--------|--------|-----------------|--------|
| **cl_waiting** | 0 | >5 for 30s | Increase pool_size |
| **sv_active** | <pool_size | =pool_size sustained | Increase pool_size or optimize queries |
| **maxwait** | 0 | >5 seconds | Increase pool_size immediately |
| **avg_xact_time** | <100ms | >500ms | Investigate slow queries |
| **Connection errors** | 0 | >10/min | Check PgBouncer/PostgreSQL health |

## Troubleshooting

### Issue 1: "Too many connections"

**Symptom**: Clients get "too many connections" error

**Causes**:
- `max_client_conn` too low
- `default_pool_size` too small
- PostgreSQL `max_connections` exceeded

**Solution**:
```ini
# Increase client connections
max_client_conn = 2000

# Increase pool size gradually
default_pool_size = 30

# Check PostgreSQL max_connections
psql -c "SHOW max_connections;"
```

### Issue 2: Clients waiting for connections

**Symptom**: `cl_waiting > 0` consistently

**Solution**:
```ini
# Increase pool size
default_pool_size = 40

# Add reserve pool
reserve_pool_size = 10
reserve_pool_timeout = 3
```

### Issue 3: Prepared statement errors

**Symptom**: `prepared statement "..." does not exist`

**Cause**: Using `transaction` or `statement` mode with prepared statements

**Solution**:
```ini
# Switch to session mode (if needed)
pool_mode = session

# Or disable prepared statements in Laravel
# (Already disabled by default)
```

### Issue 4: Connection timeout

**Symptom**: Queries timeout waiting for connection

**Solution**:
```ini
# Increase timeout
query_wait_timeout = 300

# Check if pool is saturated
psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "SHOW POOLS;"
```

### Issue 5: PgBouncer won't start

**Symptom**: `FATAL: cannot open config file`

**Solution**:
```bash
# Check config file path
pgbouncer /path/to/pgbouncer.ini

# Verify file permissions
chmod 644 /etc/pgbouncer/pgbouncer.ini
chmod 600 /etc/pgbouncer/userlist.txt

# Check syntax
pgbouncer --check /etc/pgbouncer/pgbouncer.ini
```

### Issue 6: Authentication failed

**Symptom**: `authentication failed for user`

**Solution**:
```bash
# Regenerate MD5 hash correctly
echo -n "passwordusername" | md5

# Example for user 'myuser' password 'mypass':
echo -n "mypassmyuser" | md5

# Update userlist.txt with correct hash
echo '"myuser" "md5<hash>"' >> /etc/pgbouncer/userlist.txt

# Reload PgBouncer
psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "RELOAD;"
```

## Best Practices

### 1. Pool Sizing

**Start conservative, scale gradually**:

```ini
# Start with low pool size
default_pool_size = 15

# Monitor for 24 hours
# If cl_waiting > 0, increase by 5
default_pool_size = 20

# Continue until cl_waiting stays at 0
```

**Don't over-provision**:
- ❌ `pool_size = 200` for 4-core database
- ✅ `pool_size = 20` for 4-core database

### 2. Use Transaction Mode

```ini
# Best for Laravel
pool_mode = transaction

# Avoid statement mode (breaks many features)
# pool_mode = statement
```

### 3. Monitor Regularly

```bash
# Add to cron (every 5 minutes)
*/5 * * * * psql -h 127.0.0.1 -p 6432 -U admin pgbouncer -c "SHOW STATS;" >> /var/log/pgbouncer-stats.log
```

### 4. Set Reasonable Timeouts

```ini
# Don't let clients wait forever
query_wait_timeout = 120

# Close idle connections
server_idle_timeout = 600

# Rotate connections hourly
server_lifetime = 3600
```

### 5. Use Separate Pools for Different Workloads

```ini
[databases]
# Production web app
rd_blog_prod = host=localhost port=5432 dbname=rd_blog_prod pool_size=20

# Background jobs (can wait longer)
rd_blog_jobs = host=localhost port=5432 dbname=rd_blog_prod pool_size=10

# Analytics (fewer connections needed)
rd_blog_analytics = host=localhost port=5432 dbname=rd_blog_prod pool_size=5
```

### 6. Log Important Events

```ini
log_connections = 1
log_disconnections = 1
log_pooler_errors = 1
```

### 7. Production Checklist

- [ ] PgBouncer installed and running
- [ ] `userlist.txt` configured with MD5 hashes
- [ ] Pool size calculated based on CPU cores
- [ ] Transaction mode enabled
- [ ] Timeouts configured appropriately
- [ ] Admin user configured for monitoring
- [ ] Laravel `.env` updated to use PgBouncer port
- [ ] Connection tested end-to-end
- [ ] Monitoring dashboard set up
- [ ] Auto-restart configured (systemd/launchd)

## Related Documentation

- [POSTGRESQL_OPTIMIZATION_REFERENCE.md](POSTGRESQL_OPTIMIZATION_REFERENCE.md) - Complete optimization guide
- [PHASE_4_3_PERFORMANCE_MONITORING.md](PHASE_4_3_PERFORMANCE_MONITORING.md) - Query monitoring
- [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md) - Overall migration strategy
- [EXPLAIN_ANALYZE_COMMAND.md](EXPLAIN_ANALYZE_COMMAND.md) - Query analysis tool

## References

- [PgBouncer Official Documentation](https://www.pgbouncer.org/)
- [PostgreSQL Connection Pooling](https://www.postgresql.org/docs/current/runtime-config-connection.html)
- [Laravel Database Configuration](https://laravel.com/docs/11.x/database)
