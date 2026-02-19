<?php

declare(strict_types=1);

namespace App\Database\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Trait HasBatchOperations
 *
 * Provides PostgreSQL-optimized batch operation methods for Eloquent models.
 * These methods leverage PostgreSQL-specific features for better performance.
 * 
 * IMPORTANT: When using batch operations on models with cache invalidation,
 * wrap operations with withoutCacheInvalidation() to avoid invalidating cache
 * on every single record:
 * 
 * Example:
 *   BlogPost::withoutCacheInvalidation(function() {
 *       BlogPost::bulkUpdate($updates);
 *   });
 */
trait HasBatchOperations
{
    /**
     * Insert multiple records efficiently using chunked inserts.
     *
     * Note: This bypasses Eloquent events and does not return model instances.
     * Use for bulk imports where performance is critical. Processes records in
     * chunks to avoid hitting PostgreSQL parameter limits (typically 65535).
     *
     * This uses standard INSERT statements. For extremely large datasets 
     * (millions of rows), consider using PostgreSQL's COPY command directly 
     * via psql command line tool for even better performance.
     *
     * @param array $records Array of arrays, each containing column => value pairs
     * @param int $chunkSize Number of records to insert per query (default: 1000)
     * @return int Number of rows inserted
     */
    public static function bulkInsert(array $records, int $chunkSize = 1000): int
    {
        if (empty($records)) {
            return 0;
        }

        $model = new static;
        $inserted = 0;
        
        // Process in chunks to avoid parameter limits and memory issues
        DB::transaction(function () use ($model, $records, $chunkSize, &$inserted) {
            foreach (array_chunk($records, $chunkSize) as $chunk) {
                // Normalize records to ensure consistent columns
                $columns = array_keys($chunk[0]);
                $normalized = [];
                
                foreach ($chunk as $record) {
                    $row = [];
                    foreach ($columns as $column) {
                        $value = $record[$column] ?? null;
                        
                        // Handle arrays/JSON - convert to JSON string
                        if (is_array($value)) {
                            $row[$column] = json_encode($value);
                        } else {
                            $row[$column] = $value;
                        }
                    }
                    $normalized[] = $row;
                }
                
                // Use Laravel's insert for reliable, cross-database compatibility
                $model->getConnection()->table($model->getTable())->insert($normalized);
                $inserted += count($normalized);
            }
        });

        return $inserted;
    }

    /**
     * Upsert records using PostgreSQL INSERT ... ON CONFLICT.
     *
     * More efficient than checking existence and inserting/updating individually.
     *
     * @param array $records Array of records to upsert
     * @param array|string $uniqueBy Column(s) that determine uniqueness
     * @param array|null $update Columns to update on conflict (null = all except unique)
     * @return int Number of rows affected
     */
    public static function upsertBatch(array $records, array|string $uniqueBy, ?array $update = null): int
    {
        if (empty($records)) {
            return 0;
        }

        $model = new static;
        
        // Use Laravel's native upsert which uses ON CONFLICT on PostgreSQL
        return $model->newQuery()->upsert(
            $records,
            is_array($uniqueBy) ? $uniqueBy : [$uniqueBy],
            $update
        );
    }

    /**
     * Bulk update records with different values using CASE statements.
     *
     * More efficient than individual UPDATE queries.
     *
     * @param array $records Array where key is ID and value is array of columns to update
     * @param string $keyColumn Primary key column name (default: 'id')
     * @return int Number of rows affected
     */
    public static function bulkUpdate(array $records, string $keyColumn = 'id'): int
    {
        if (empty($records)) {
            return 0;
        }

        $model = new static;
        $table = $model->getTable();
        
        // Get union of all column names across all records (excluding the key)
        $columns = [];
        foreach ($records as $record) {
            $columns = array_merge($columns, array_keys($record));
        }
        $columns = array_unique($columns);
        
        // Build CASE statements for each column with parameterized queries
        $caseStatements = [];
        $bindings = [];
        
        foreach ($columns as $column) {
            $cases = [];
            foreach ($records as $id => $data) {
                if (array_key_exists($column, $data)) {
                    $value = $data[$column];
                    
                    // Add ID to bindings
                    $bindings[] = $id;
                    
                    // Handle different data types with proper parameterization
                    if ($value === null) {
                        $cases[] = "WHEN {$keyColumn} = ? THEN NULL";
                    } elseif (is_bool($value)) {
                        $cases[] = "WHEN {$keyColumn} = ? THEN ?";
                        $bindings[] = $value;
                    } elseif (is_array($value)) {
                        $cases[] = "WHEN {$keyColumn} = ? THEN ?::json";
                        $bindings[] = json_encode($value);
                    } else {
                        $cases[] = "WHEN {$keyColumn} = ? THEN ?";
                        $bindings[] = $value;
                    }
                }
            }
            
            if (!empty($cases)) {
                $caseStatements[] = "{$column} = CASE " . implode(' ', $cases) . " ELSE {$column} END";
            }
        }

        if (empty($caseStatements)) {
            return 0;
        }

        // Add IDs for WHERE clause
        $ids = array_keys($records);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids);
        
        $updates = implode(', ', $caseStatements);
        $query = "UPDATE {$table} SET {$updates} WHERE {$keyColumn} IN ({$placeholders})";
        
        return DB::affectingStatement($query, $bindings);
    }

    /**
     * Bulk delete records in a single query.
     *
     * More efficient than deleting individually.
     *
     * @param array $ids Array of IDs to delete
     * @param string $keyColumn Column name for the key (default: 'id')
     * @return int Number of rows deleted
     */
    public static function bulkDelete(array $ids, string $keyColumn = 'id'): int
    {
        if (empty($ids)) {
            return 0;
        }

        $model = new static;
        return $model->newQuery()->whereIn($keyColumn, $ids)->delete();
    }

    /**
     * Chunk through records and process in batches within a transaction.
     *
     * Useful for processing large datasets without memory issues.
     *
     * @param int $chunkSize Number of records per batch
     * @param callable $callback Function to execute on each chunk
     * @return bool Success status
     */
    public static function processBatch(int $chunkSize, callable $callback): bool
    {
        $model = new static;
        
        try {
            DB::transaction(function () use ($model, $chunkSize, $callback) {
                $model->newQuery()->chunk($chunkSize, $callback);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Insert records and return the inserted IDs using RETURNING clause.
     *
     * PostgreSQL-specific feature for getting IDs without additional queries.
     *
     * @param array $records Array of records to insert
     * @param string $returningColumn Column to return (default: 'id')
     * @return array Array of returned values
     */
    public static function insertReturning(array $records, string $returningColumn = 'id'): array
    {
        if (empty($records)) {
            return [];
        }

        $model = new static;
        $table = $model->getTable();
        
        // Get columns from first record
        $columns = array_keys($records[0]);
        $columnList = implode(',', array_map(fn($col) => '"' . $col . '"', $columns));
        
        // Build values
        $valueSets = [];
        $bindings = [];
        
        foreach ($records as $record) {
            $placeholders = [];
            foreach ($columns as $column) {
                $value = $record[$column] ?? null;
                
                // Handle JSON arrays
                if (is_array($value)) {
                    $placeholders[] = '?::jsonb';
                    $bindings[] = json_encode($value);
                } else {
                    $placeholders[] = '?';
                    $bindings[] = $value;
                }
            }
            $valueSets[] = '(' . implode(',', $placeholders) . ')';
        }
        
        $values = implode(',', $valueSets);
        
        $query = "INSERT INTO {$table} ({$columnList}) VALUES {$values} RETURNING {$returningColumn}";
        
        $results = DB::select($query, $bindings);
        
        return array_map(fn($row) => is_object($row) ? $row->$returningColumn : $row[$returningColumn], $results);
    }

    /**
     * Batch increment/decrement a column for multiple records.
     *
     * @param array $increments Array where key is ID and value is increment amount
     * @param string $column Column to increment
     * @param string $keyColumn Primary key column (default: 'id')
     * @return int Number of rows affected
     */
    public static function bulkIncrement(array $increments, string $column, string $keyColumn = 'id'): int
    {
        if (empty($increments)) {
            return 0;
        }

        $model = new static;
        $table = $model->getTable();
        
        // Build CASE statement with parameterized queries
        $cases = [];
        $bindings = [];
        
        foreach ($increments as $id => $amount) {
            $cases[] = "WHEN {$keyColumn} = ? THEN {$column} + ?";
            $bindings[] = $id;
            $bindings[] = $amount;
        }
        
        $caseStatement = "CASE " . implode(' ', $cases) . " ELSE {$column} END";
        
        // Add IDs for WHERE clause
        $ids = array_keys($increments);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids);
        
        $query = "UPDATE {$table} SET {$column} = {$caseStatement} WHERE {$keyColumn} IN ({$placeholders})";
        
        return DB::affectingStatement($query, $bindings);
    }
}
