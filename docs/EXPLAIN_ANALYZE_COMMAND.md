# EXPLAIN ANALYZE Helper Command Guide

**Command**: `php artisan db:explain`  
**Purpose**: Analyze PostgreSQL query execution plans  
**Status**: ✅ Implemented  
**File**: `app/Console/Commands/ExplainQuery.php`

## Overview

The `db:explain` command is a developer productivity tool that makes it easy to analyze query execution plans in PostgreSQL using EXPLAIN ANALYZE. It provides color-coded output, performance assessments, and optimization suggestions to help identify and fix slow queries.

## Command Signature

```bash
php artisan db:explain {query?} [options]
```

### Arguments

- `query` - SQL query to analyze (optional if using `--file`)

### Options

| Option | Description | Default |
|--------|-------------|---------|
| `--file=PATH` | Read query from .sql file | - |
| `--format=FORMAT` | Output format: `text` or `json` | `text` |
| `--buffers` | Show buffer usage statistics | `false` |
| `--detailed` | Show verbose output (includes VERBOSE flag) | `false` |
| `--no-costs` | Exclude cost estimates from output | `false` |
| `--no-execute` | Run EXPLAIN without ANALYZE (doesn't execute) | `false` |
| `--allow-write` | Allow mutating statements with ANALYZE (dangerous) | `false` |
| `--suggest` | Show optimization suggestions | `false` |

## Features

### 1. Color-Coded Output

The command highlights different parts of the execution plan with colors:

- **🟢 Green**: Index scans (efficient queries)
- **🟡 Yellow**: Sequential scans (may need optimization)
- **🔵 Blue**: Cost estimates
- **🟣 Magenta**: Timing information (planning/execution)

### 2. Performance Assessment

Automatic performance classification based on execution time:

| Time | Assessment | Indicator |
|------|------------|-----------|
| < 10ms | Excellent | ✓ Green |
| 10-100ms | Good | ⚠ Yellow |
| 100-500ms | Slow | ⚠ Orange |
| > 500ms | Very Slow | ✗ Red |

### 3. Optimization Suggestions

When using `--suggest`, the command automatically detects:

- **Sequential scans** on large tables
- **Missing indexes** on filter columns
- **High query costs** (> 10,000)
- **External sorts** (using disk instead of memory)
- **Inefficient nested loops** with many rows

### 4. Multiple Output Formats

- **Text**: Human-readable colored output (default)
- **JSON**: Machine-readable for programmatic analysis

### 5. File Input Support

Read queries from `.sql` files for complex or multi-line queries.

## Usage Examples

### Basic Query Analysis

```bash
php artisan db:explain "SELECT * FROM blog_posts WHERE is_published = true"
```

**Output:**
```
🔍 Analyzing Query Plan...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Query Execution Plan
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Seq Scan on blog_posts  (cost=0.00..10.40 rows=20 width=1690) (actual time=0.013..0.025 rows=20 loops=1)
  Filter: is_published
  Rows Removed by Filter: 5
Planning Time: 0.156 ms
Execution Time: 0.048 ms

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Timing Summary
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Planning Time: 0.156 ms
Execution Time: 0.048 ms
Total Time: 0.204 ms

✓ Excellent performance - Query executes very fast
```

### With Optimization Suggestions

```bash
php artisan db:explain "SELECT * FROM blog_posts WHERE content LIKE '%search%'" --suggest
```

**Output (includes suggestions):**
```
[...execution plan...]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Optimization Suggestions
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔍 Sequential scan detected on table 'blog_posts'
   Consider adding an index on the filtered columns
📊 Table scan with filter condition detected
   An index on the filter column(s) could improve performance
```

### Analyze Query from File

Create a file `query.sql`:
```sql
SELECT 
    bp.*,
    u.name as author_name,
    COUNT(c.id) as comment_count,
    COUNT(DISTINCT bpl.user_id) as like_count
FROM blog_posts bp
LEFT JOIN users u ON bp.user_id = u.id
LEFT JOIN comments c ON bp.id = c.blog_post_id
LEFT JOIN blog_post_likes bpl ON bp.id = bpl.blog_post_id
WHERE bp.is_published = true
  AND bp.published_at > NOW() - INTERVAL '30 days'
GROUP BY bp.id, u.name
ORDER BY comment_count DESC, like_count DESC
LIMIT 10;
```

Then analyze:
```bash
php artisan db:explain --file=query.sql --buffers --detailed --suggest
```

### Show Buffer Statistics

```bash
php artisan db:explain "SELECT * FROM blog_posts" --buffers
```

This shows:
- Shared blocks hit (from cache)
- Shared blocks read (from disk)
- Shared blocks written
- Temporary blocks written

### Detailed Verbose Output

```bash
php artisan db:explain "SELECT * FROM blog_posts" --detailed
```

Includes:
- Column names
- Table aliases
- Output expressions
- Filter predicates
- Additional planner details

### JSON Format

```bash
php artisan db:explain "SELECT * FROM users LIMIT 10" --format=json
```

**Output:**
```json
[
  {
    "Plan": {
      "Node Type": "Limit",
      "Parallel Aware": false,
      "Startup Cost": 0,
      "Total Cost": 2.30,
      "Plan Rows": 10,
      "Plan Width": 520,
      "Actual Startup Time": 0.015,
      "Actual Total Time": 0.023,
      "Actual Rows": 10,
      "Actual Loops": 1,
      "Plans": [...]
    },
    "Planning Time": 0.089,
    "Execution Time": 0.045
  }
]
```

### EXPLAIN Without Execution

For queries that modify data (UPDATE, DELETE, INSERT), use `--no-execute` to get the plan without actually running the query:

```bash
php artisan db:explain "UPDATE blog_posts SET views = views + 1 WHERE id = 1" --no-execute
```

This runs `EXPLAIN` instead of `EXPLAIN ANALYZE`, so the query is not executed.

### Safety Guard for Mutating Queries

By default, mutating statements are blocked when using `EXPLAIN ANALYZE` because ANALYZE executes the statement.

```bash
# Blocked by default (returns non-zero)
php artisan db:explain "UPDATE blog_posts SET views = views + 1 WHERE id = 1"

# Safe planning mode (allowed)
php artisan db:explain "UPDATE blog_posts SET views = views + 1 WHERE id = 1" --no-execute

# Explicitly allow execution (use with care)
php artisan db:explain "UPDATE blog_posts SET views = views + 1 WHERE id = 1" --allow-write
```

### Complex Multi-Join Query

```bash
php artisan db:explain "
  WITH popular_posts AS (
    SELECT 
      bp.id,
      COUNT(DISTINCT bpl.user_id) as like_count
    FROM blog_posts bp
    LEFT JOIN blog_post_likes bpl ON bp.id = bpl.blog_post_id
    WHERE bp.is_published = true
    GROUP BY bp.id
    HAVING COUNT(DISTINCT bpl.user_id) > 5
  )
  SELECT 
    bp.*,
    u.name,
    pp.like_count,
    COUNT(c.id) as comment_count
  FROM popular_posts pp
  JOIN blog_posts bp ON pp.id = bp.id
  JOIN users u ON bp.user_id = u.id
  LEFT JOIN comments c ON bp.id = c.blog_post_id
  GROUP BY bp.id, u.name, pp.like_count
  ORDER BY pp.like_count DESC, comment_count DESC
  LIMIT 20
" --buffers --suggest
```

## Understanding the Output

### Cost Estimates

```
Seq Scan on blog_posts  (cost=0.00..10.40 rows=20 width=1690)
                          ^^^^^^^^^^^^
```

- **Startup Cost**: `0.00` - Cost to return first row
- **Total Cost**: `10.40` - Cost to return all rows
- **Rows**: `20` - Estimated number of rows
- **Width**: `1690` - Average row size in bytes

### Actual Statistics (with ANALYZE)

```
(actual time=0.013..0.025 rows=20 loops=1)
             ^^^^^^^^^^^^^ ^^^^^^^ ^^^^^^
```

- **Time**: `0.013..0.025` - Actual startup and total time (ms)
- **Rows**: `20` - Actual rows returned
- **Loops**: `1` - Number of times node was executed

### Planning vs Execution Time

```
Planning Time: 0.156 ms
Execution Time: 0.048 ms
```

- **Planning Time**: Time PostgreSQL spent creating the execution plan
- **Execution Time**: Time actually running the query

## Common Patterns and Solutions

### Pattern 1: Sequential Scan on Large Table

**Query:**
```sql
SELECT * FROM blog_posts WHERE title LIKE '%keyword%'
```

**Plan Shows:**
```
Seq Scan on blog_posts (cost=0.00..1234.56 rows=100 width=1690)
  Filter: (title ~~ '%keyword%'::text)
```

**Suggestions:**
- For exact matches: Add B-tree index on `title`
- For full-text search: Use `to_tsvector` with GIN index
- For prefix searches: Use `text_pattern_ops` index

### Pattern 2: Missing Index

**Query:**
```sql
SELECT * FROM blog_posts WHERE user_id = 123
```

**Plan Shows:**
```
Seq Scan on blog_posts (cost=0.00..15.00 rows=5 width=1690)
  Filter: (user_id = 123)
```

**Solution:**
```sql
CREATE INDEX blog_posts_user_id_index ON blog_posts(user_id);
```

### Pattern 3: Inefficient Join

**Query:**
```sql
SELECT * FROM blog_posts bp
JOIN comments c ON bp.id = c.blog_post_id
```

**Plan Shows:**
```
Nested Loop (cost=0.00..5000.00 rows=10000 width=2000)
  -> Seq Scan on blog_posts bp
  -> Seq Scan on comments c
       Filter: (blog_post_id = bp.id)
```

**Solution:**
```sql
-- Add foreign key index
CREATE INDEX comments_blog_post_id_index ON comments(blog_post_id);
```

### Pattern 4: External Sort

**Query:**
```sql
SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 100
```

**Plan Shows:**
```
Limit
  -> Sort (actual rows=100 loops=1)
       Sort Method: external merge  Disk: 1024kB
       -> Seq Scan on blog_posts
```

**Solutions:**
- Increase `work_mem` setting
- Add index on `created_at DESC`
- Reduce result set before sorting

## Tips and Best Practices

### 1. Start Simple

Begin with basic analysis, then add options as needed:
```bash
# Step 1: Basic analysis
php artisan db:explain "SELECT ..."

# Step 2: Add suggestions
php artisan db:explain "SELECT ..." --suggest

# Step 3: Deep dive with buffers
php artisan db:explain "SELECT ..." --buffers --detailed --suggest
```

### 2. Save Complex Queries to Files

For long queries, use `.sql` files:
```bash
echo "SELECT ..." > my_query.sql
php artisan db:explain --file=my_query.sql --suggest
```

### 3. Compare Before/After Optimization

```bash
# Before optimization
php artisan db:explain "SELECT * FROM blog_posts WHERE user_id = 1" > before.txt

# Create index
psql rd_blog_dev -c "CREATE INDEX blog_posts_user_id_index ON blog_posts(user_id);"

# After optimization
php artisan db:explain "SELECT * FROM blog_posts WHERE user_id = 1" > after.txt

# Compare
diff before.txt after.txt
```

### 4. Use --no-execute for Dangerous Queries

```bash
# Safe: analyze without executing
php artisan db:explain "DELETE FROM blog_posts WHERE created_at < '2020-01-01'" --no-execute
```

### 5. Automate Performance Testing

Create a script `check-performance.sh`:
```bash
#!/bin/bash

QUERIES=(
  "SELECT * FROM blog_posts WHERE is_published = true"
  "SELECT * FROM comments WHERE blog_post_id = 1"
  "SELECT * FROM users WHERE email = 'test@example.com'"
)

for query in "${QUERIES[@]}"; do
  echo "Testing: $query"
  php artisan db:explain "$query" --suggest
  echo "---"
done
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Query Performance Check

on: [pull_request]

jobs:
  performance:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run migrations
        run: php artisan migrate
      
      - name: Check query performance
        run: |
          php artisan db:explain "SELECT * FROM blog_posts" --suggest
```

## Troubleshooting

### Error: "This command only works with PostgreSQL databases"

**Cause**: Not connected to PostgreSQL

**Solution**: Check `DB_CONNECTION=pgsql` in `.env`

### Error: "Failed to analyze query: syntax error"

**Cause**: Invalid SQL syntax

**Solution**: Verify SQL syntax is correct
```bash
# Test in psql first
psql rd_blog_dev -c "YOUR_QUERY"
```

### No Optimization Suggestions Shown

**Cause**: Query is already optimal or `--suggest` not used

**Solution**: Add `--suggest` flag
```bash
php artisan db:explain "SELECT ..." --suggest
```

### "Planning Time" much higher than "Execution Time"

**Cause**: Complex query planning, statistics may need updating

**Solution**: Run ANALYZE on tables
```bash
psql rd_blog_dev -c "ANALYZE blog_posts;"
```

## Related Documentation

- [PHASE_4_3_PERFORMANCE_MONITORING.md](PHASE_4_3_PERFORMANCE_MONITORING.md) - Complete monitoring guide
- [POSTGRESQL_OPTIMIZATION_REFERENCE.md](POSTGRESQL_OPTIMIZATION_REFERENCE.md) - Optimization reference
- [PG_STAT_STATEMENTS_SETUP.md](PG_STAT_STATEMENTS_SETUP.md) - pg_stat_statements extension
- [PostgreSQL EXPLAIN Documentation](https://www.postgresql.org/docs/16/sql-explain.html)

## Summary

The `db:explain` command is your go-to tool for:

✓ **Understanding** how PostgreSQL executes your queries  
✓ **Identifying** performance bottlenecks  
✓ **Discovering** missing indexes  
✓ **Optimizing** slow queries  
✓ **Learning** PostgreSQL query planning

**Quick Start:**
```bash
# Analyze any query
php artisan db:explain "SELECT * FROM blog_posts WHERE is_published = true" --suggest
```

**For detailed analysis of complex queries, save to file and use all options:**
```bash
php artisan db:explain --file=complex.sql --buffers --detailed --suggest
```
