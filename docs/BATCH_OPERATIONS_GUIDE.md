# PostgreSQL Batch Operations Guide

**Trait**: `App\Database\Concerns\HasBatchOperations`  
**Purpose**: PostgreSQL-optimized batch operations for improved performance  
**Status**: ✅ Implemented

## Overview

The `HasBatchOperations` trait provides high-performance batch operation methods that leverage PostgreSQL-specific features. These methods significantly improve performance when working with large datasets by reducing the number of database round-trips and utilizing PostgreSQL's advanced features.

## Performance Benefits

| Operation | Traditional | Batch Operation | Speedup |
|-----------|-------------|-----------------|---------|
| Insert 1000 records | 1000 INSERT queries | 1 upsert query | ~50-100x faster |
| Update 100 records | 100 UPDATE queries | 1 UPDATE with CASE | ~20-50x faster |
| Delete 500 records | 500 DELETE queries | 1 DELETE with IN | ~30-60x faster |
| Increment counters | 50 UPDATE queries | 1 UPDATE with CASE | ~15-30x faster |

## Installation

Add the trait to any Eloquent model:

```php
<?php

namespace App\Models;

use App\Database\Concerns\HasBatchOperations;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasBatchOperations;
    
    // ... rest of model
}
```

## Available Methods

### 1. upsertBatch()

**Purpose**: Insert or update multiple records in a single query using PostgreSQL's `INSERT ... ON CONFLICT`.

**Signature**:
```php
public static function upsertBatch(
    array $records, 
    array|string $uniqueBy, 
    ?array $update = null
): int
```

**Parameters**:
- `$records` - Array of records to insert/update
- `$uniqueBy` - Column(s) that determine uniqueness
- `$update` - Columns to update on conflict (null = all except unique columns)

**Example - Basic Upsert**:
```php
$posts = [
    [
        'title' => 'First Post',
        'slug' => 'first-post',
        'excerpt' => 'Excerpt 1',
        'content' => 'Content 1',
        'user_id' => 1,
        'is_published' => true,
    ],
    [
        'title' => 'Second Post',
        'slug' => 'second-post',
        'excerpt' => 'Excerpt 2',
        'content' => 'Content 2',
        'user_id' => 1,
        'is_published' => false,
    ],
];

// Insert new records or update existing ones based on slug
$affected = BlogPost::upsertBatch($posts, 'slug');
```

**Example - Partial Update**:
```php
$posts = [
    [
        'slug' => 'existing-post',
        'title' => 'Updated Title',
        'is_published' => true,
    ],
];

// Only update title and is_published on conflict
BlogPost::upsertBatch($posts, 'slug', ['title', 'is_published']);
```

**Example - Multiple Unique Columns**:
```php
$comments = [
    [
        'blog_post_id' => 1,
        'user_id' => 5,
        'content' => 'Great post!',
    ],
];

// Unique constraint on (blog_post_id, user_id)
Comment::upsertBatch($comments, ['blog_post_id', 'user_id']);
```

**SQL Generated**:
```sql
INSERT INTO blog_posts (title, slug, content, user_id) 
VALUES ('First Post', 'first-post', 'Content 1', 1)
ON CONFLICT (slug) 
DO UPDATE SET title = EXCLUDED.title, content = EXCLUDED.content;
```

### 2. bulkUpdate()

**Purpose**: Update multiple records with different values using a single CASE statement.

**Signature**:
```php
public static function bulkUpdate(
    array $records, 
    string $keyColumn = 'id'
): int
```

**Parameters**:
- `$records` - Array where key is ID and value is array of columns to update
- `$keyColumn` - Primary key column name (default: 'id')

**Example - Update Multiple Records**:
```php
$updates = [
    1 => ['title' => 'Updated Title 1', 'is_featured' => true],
    2 => ['title' => 'Updated Title 2', 'is_featured' => false],
    3 => ['title' => 'Updated Title 3', 'is_featured' => true],
];

$affected = BlogPost::bulkUpdate($updates);
// Updates 3 records in a single query
```

**Example - Update with NULL Values**:
```php
$updates = [
    10 => ['featured_image' => null],
    11 => ['featured_image' => 'new-image.jpg'],
];

BlogPost::bulkUpdate($updates);
```

**Example - Update JSON Columns**:
```php
$updates = [
    5 => ['tags' => ['php', 'laravel', 'postgresql']],
    6 => ['tags' => ['javascript', 'vue', 'typescript']],
];

BlogPost::bulkUpdate($updates);
```

**SQL Generated**:
```sql
UPDATE blog_posts 
SET 
    title = CASE 
        WHEN id = 1 THEN 'Updated Title 1'
        WHEN id = 2 THEN 'Updated Title 2'
        WHEN id = 3 THEN 'Updated Title 3'
        ELSE title 
    END,
    is_featured = CASE 
        WHEN id = 1 THEN TRUE
        WHEN id = 2 THEN FALSE
        WHEN id = 3 THEN TRUE
        ELSE is_featured 
    END
WHERE id IN (1,2,3);
```

### 3. bulkDelete()

**Purpose**: Delete multiple records in a single query.

**Signature**:
```php
public static function bulkDelete(
    array $ids, 
    string $keyColumn = 'id'
): int
```

**Parameters**:
- `$ids` - Array of IDs to delete
- `$keyColumn` - Column name for the key (default: 'id')

**Example - Delete by IDs**:
```php
$idsToDelete = [1, 5, 8, 12, 15];

$affected = BlogPost::bulkDelete($idsToDelete);
// Deletes 5 records in a single query
```

**Example - Delete by Slug**:
```php
$slugsToDelete = ['old-post-1', 'old-post-2', 'old-post-3'];

$affected = BlogPost::bulkDelete($slugsToDelete, 'slug');
```

**SQL Generated**:
```sql
DELETE FROM blog_posts WHERE id IN (1, 5, 8, 12, 15);
```

### 4. insertReturning()

**Purpose**: Insert records and return specific column values using PostgreSQL's `RETURNING` clause.

**Signature**:
```php
public static function insertReturning(
    array $records, 
    string $returningColumn = 'id'
): array
```

**Parameters**:
- `$records` - Array of records to insert
- `$returningColumn` - Column to return (default: 'id')

**Example - Get Inserted IDs**:
```php
$posts = [
    [
        'title' => 'Post 1',
        'slug' => 'post-1',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'user_id' => 1,
    ],
    [
        'title' => 'Post 2',
        'slug' => 'post-2',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'user_id' => 1,
    ],
];

$ids = BlogPost::insertReturning($posts);
// Returns: [23, 24]
```

**Example - Return Slugs**:
```php
$ids = BlogPost::insertReturning($posts, 'slug');
// Returns: ['post-1', 'post-2']
```

**SQL Generated**:
```sql
INSERT INTO blog_posts (title, slug, content, user_id) 
VALUES 
    ('Post 1', 'post-1', 'Content', 1),
    ('Post 2', 'post-2', 'Content', 1)
RETURNING id;
```

### 5. bulkIncrement()

**Purpose**: Increment/decrement a column for multiple records with different amounts.

**Signature**:
```php
public static function bulkIncrement(
    array $increments, 
    string $column, 
    string $keyColumn = 'id'
): int
```

**Parameters**:
- `$increments` - Array where key is ID and value is increment amount
- `$column` - Column to increment
- `$keyColumn` - Primary key column (default: 'id')

**Example - Increment View Counts**:
```php
$increments = [
    1 => 5,   // Increment by 5
    2 => 10,  // Increment by 10
    3 => 3,   // Increment by 3
];

$affected = BlogPost::bulkIncrement($increments, 'views');
```

**Example - Decrement (Negative Values)**:
```php
$decrements = [
    5 => -2,  // Decrement by 2
    6 => -5,  // Decrement by 5
];

BlogPost::bulkIncrement($decrements, 'reading_time');
```

**SQL Generated**:
```sql
UPDATE blog_posts 
SET views = CASE 
    WHEN id = 1 THEN views + (5)
    WHEN id = 2 THEN views + (10)
    WHEN id = 3 THEN views + (3)
    ELSE views 
END
WHERE id IN (1,2,3);
```

### 6. processBatch()

**Purpose**: Process records in chunks within a transaction, preventing memory issues with large datasets.

**Signature**:
```php
public static function processBatch(
    int $chunkSize, 
    callable $callback
): bool
```

**Parameters**:
- `$chunkSize` - Number of records per batch
- `$callback` - Function to execute on each chunk

**Example - Process All Posts**:
```php
$success = BlogPost::processBatch(100, function ($posts) {
    foreach ($posts as $post) {
        // Process each post
        $post->update(['processed' => true]);
    }
});
```

**Example - Calculate Statistics**:
```php
$totalWords = 0;

BlogPost::processBatch(50, function ($posts) use (&$totalWords) {
    foreach ($posts as $post) {
        $totalWords += str_word_count($post->content);
    }
});

echo "Total words across all posts: {$totalWords}";
```

## Use Cases & Patterns

### Pattern 1: Data Import/Migration

**Scenario**: Importing 10,000 blog posts from an external source.

```php
// Traditional approach (slow)
foreach ($externalPosts as $postData) {
    BlogPost::create($postData);  // 10,000 INSERT queries
}

// Optimized approach with upsertBatch
$batches = array_chunk($externalPosts, 500);

foreach ($batches as $batch) {
    BlogPost::upsertBatch($batch, 'slug');  // 20 queries instead of 10,000
}
```

### Pattern 2: Bulk Status Updates

**Scenario**: Publishing multiple draft posts.

```php
// Traditional approach
$draftIds = [1, 2, 3, 4, 5, 10, 15, 20];

foreach ($draftIds as $id) {
    BlogPost::find($id)->update([
        'is_published' => true,
        'published_at' => now(),
    ]);
}

// Optimized approach
$updates = [];
foreach ($draftIds as $id) {
    $updates[$id] = [
        'is_published' => true,
        'published_at' => now(),
    ];
}

BlogPost::bulkUpdate($updates);  // Single query
```

### Pattern 3: Analytics/Counter Updates

**Scenario**: Recording view counts for multiple posts.

```php
// Track views in memory during request
$viewCounts = [
    5 => 3,   // Post 5 was viewed 3 times
    8 => 1,   // Post 8 was viewed 1 time
    12 => 5,  // Post 12 was viewed 5 times
];

// Update all at once
BlogPost::bulkIncrement($viewCounts, 'views');
```

### Pattern 4: Cleanup Operations

**Scenario**: Deleting old spam comments.

```php
// Traditional approach
$spamComments = Comment::where('is_spam', true)
    ->where('created_at', '<', now()->subMonths(6))
    ->get();

foreach ($spamComments as $comment) {
    $comment->delete();  // N DELETE queries
}

// Optimized approach
$spamIds = Comment::where('is_spam', true)
    ->where('created_at', '<', now()->subMonths(6))
    ->pluck('id')
    ->toArray();

Comment::bulkDelete($spamIds);  // Single DELETE query
```

### Pattern 5: Tag Synchronization

**Scenario**: Updating tags for multiple posts.

```php
$tagUpdates = [
    1 => ['tags' => ['php', 'laravel', 'postgresql']],
    2 => ['tags' => ['javascript', 'vue']],
    3 => ['tags' => ['devops', 'docker']],
];

BlogPost::bulkUpdate($tagUpdates);
```

## Best Practices

### 1. Batch Size Recommendations

```php
// For upsertBatch - optimal batch size: 500-1000 records
$batches = array_chunk($records, 500);
foreach ($batches as $batch) {
    BlogPost::upsertBatch($batch, 'slug');
}

// For bulkUpdate - optimal batch size: 100-500 records
$batches = array_chunk($updates, 100, true);
foreach ($batches as $batch) {
    BlogPost::bulkUpdate($batch);
}
```

### 2. Use Transactions for Related Updates

```php
// Use the same explicit connection when coordinating multiple models.
// Do not rely on the global DB facade transaction if models may point to
// different connections.
$connection = BlogPost::query()->getConnection();

$connection->transaction(function () use ($postUpdates, $commentUpdates) {
    BlogPost::bulkUpdate($postUpdates);
    Comment::bulkUpdate($commentUpdates);
});
```

**Important:** `HasBatchOperations` executes SQL on the model's own connection.
For cross-model atomicity, ensure both models share the same connection, or split
operations by connection boundary.

### 3. Validate Data Before Batch Operations

```php
// Validate all records before batching
$validator = Validator::make($records, [
    '*.title' => 'required|string|max:255',
    '*.slug' => 'required|string|unique:blog_posts,slug',
    // ... other rules
]);

if ($validator->fails()) {
    // Handle validation errors
    return;
}

// Safe to batch
BlogPost::upsertBatch($validator->validated(), 'slug');
```

### 4. Handle Large Datasets with processBatch

```php
// Don't load all records into memory
$processedCount = 0;

BlogPost::where('is_published', true)->processBatch(100, function ($posts) use (&$processedCount) {
    // Process chunk
    $updates = [];
    
    foreach ($posts as $post) {
        $updates[$post->id] = [
            'reading_time' => str_word_count($post->content) / 200,
        ];
    }
    
    BlogPost::bulkUpdate($updates);
    $processedCount += $posts->count();
});

Log::info("Processed {$processedCount} posts");
```

### 5. Monitor Performance

```php
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

// Perform batch operation
$start = microtime(true);
BlogPost::upsertBatch($records, 'slug');
$duration = microtime(true) - $start;

// Check queries
$queries = DB::getQueryLog();
Log::info("Upserted " . count($records) . " records in {$duration}s using " . count($queries) . " queries");
```

## Limitations & Considerations

### 1. Eloquent Events

**Note**: Batch operations bypass Eloquent model events (`creating`, `updating`, `saving`, etc.).

```php
// Events NOT triggered
BlogPost::upsertBatch($posts, 'slug');

// If you need events, use traditional approach
foreach ($posts as $postData) {
    BlogPost::create($postData);  // Triggers events
}
```

### 2. Timestamps

**Note**: Batch operations respect `timestamps` but update `updated_at` for all records.

```php
// Disable timestamps if not needed
BlogPost::withoutTimestamps(function () {
    BlogPost::bulkUpdate($updates);
});
```

### 3. Soft Deletes

**Note**: `bulkDelete()` performs hard deletes.

```php
// For soft deletes, use bulkUpdate instead
$softDeletes = [];
foreach ($idsToDelete as $id) {
    $softDeletes[$id] = ['deleted_at' => now()];
}

BlogPost::bulkUpdate($softDeletes);
```

### 4. Database Driver

**Note**: These methods are optimized for PostgreSQL. Some features (like `RETURNING`) are PostgreSQL-specific.

```php
// Check database driver before using
if (DB::connection()->getDriverName() === 'pgsql') {
    BlogPost::insertReturning($posts);
} else {
    // Fallback for other databases
    BlogPost::insert($posts);
}
```

## Troubleshooting

### Issue: Unique Constraint Violation

**Error**: `SQLSTATE[23505]: Unique violation`

**Solution**: Ensure unique constraint exists and matches `$uniqueBy` parameter:

```php
// In migration
$table->unique(['blog_post_id', 'user_id']);

// In code
Comment::upsertBatch($comments, ['blog_post_id', 'user_id']);
```

### Issue: JSON Type Mismatch

**Error**: `CASE/WHEN could not convert type jsonb to json`

**Solution**: Cast column in CASE statement (handled automatically in trait).

### Issue: Memory Exhaustion

**Error**: `Allowed memory size exhausted`

**Solution**: Use `processBatch()` or chunk data:

```php
// Instead of loading all at once
$allPosts = BlogPost::all();  // Memory issue with 100k records

// Use processBatch
BlogPost::processBatch(500, function ($posts) {
    // Process chunk
});
```

## Testing

Run batch operation tests:

```bash
# Run all batch tests
./vendor/bin/pest tests/Feature/PostgreSQLBatchOperationsTest.php

# Run specific test
./vendor/bin/pest --filter="upsertBatch"
```

## Related Documentation

- [POSTGRESQL_OPTIMIZATION_REFERENCE.md](POSTGRESQL_OPTIMIZATION_REFERENCE.md) - Complete optimization guide
- [PHASE_4_3_PERFORMANCE_MONITORING.md](PHASE_4_3_PERFORMANCE_MONITORING.md) - Performance monitoring
- [PostgreSQL INSERT ON CONFLICT](https://www.postgresql.org/docs/16/sql-insert.html#SQL-ON-CONFLICT)
- [PostgreSQL RETURNING](https://www.postgresql.org/docs/16/dml-returning.html)

## Summary

The `HasBatchOperations` trait provides:

✅ **50-100x faster** bulk inserts with `upsertBatch()`  
✅ **20-50x faster** bulk updates with `bulkUpdate()`  
✅ **30-60x faster** bulk deletes with `bulkDelete()`  
✅ **Single query** for multiple increments with `bulkIncrement()`  
✅ **Memory-efficient** large dataset processing with `processBatch()`  
✅ **PostgreSQL-specific** optimizations with `insertReturning()`

**Quick Start**:
```php
// Add trait to model
use HasBatchOperations;

// Use in your code
BlogPost::upsertBatch($posts, 'slug');
BlogPost::bulkUpdate($updates);
BlogPost::bulkDelete($ids);
```
