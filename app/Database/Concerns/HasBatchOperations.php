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
     * More efficient than individual UPDATE queries. Processes records in chunks
     * to avoid hitting PostgreSQL parameter limits and memory issues.
     *
     * @param array $records Array where key is ID and value is array of columns to update
     * @param string $keyColumn Primary key column name (default: 'id')
     * @param int $chunkSize Number of records to update per query (default: 500)
     * @return int Number of rows affected
     */
    public static function bulkUpdate(array $records, string $keyColumn = 'id', int $chunkSize = 500): int
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
        
        // SECURITY: Validate column names against actual database columns to prevent SQL injection
        // Column names cannot be parameterized in SQL, so we must whitelist them
        $validColumns = static::getValidColumnNames($model);
        $columns = array_filter($columns, function($column) use ($validColumns) {
            return in_array($column, $validColumns, true);
        });
        
        if (empty($columns)) {
            return 0; // No valid columns to update
        }
        
        // Process records in chunks to avoid parameter limits and memory issues
        $totalAffected = 0;
        
        DB::transaction(function () use ($records, $columns, $keyColumn, $table, $chunkSize, &$totalAffected) {
            foreach (array_chunk($records, $chunkSize, true) as $chunk) {
                // Build CASE statements for each column with parameterized queries
                $caseStatements = [];
                $bindings = [];
                
                foreach ($columns as $column) {
                    $cases = [];
                    foreach ($chunk as $id => $data) {
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
                        // Column name is validated above, safe to interpolate
                        $caseStatements[] = "{$column} = CASE " . implode(' ', $cases) . " ELSE {$column} END";
                    }
                }

                if (empty($caseStatements)) {
                    continue;
                }

                // Add IDs for WHERE clause
                $ids = array_keys($chunk);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $bindings = array_merge($bindings, $ids);
                
                $updates = implode(', ', $caseStatements);
                $query = "UPDATE {$table} SET {$updates} WHERE {$keyColumn} IN ({$placeholders})";
                
                $totalAffected += DB::affectingStatement($query, $bindings);
            }
        });
        
        return $totalAffected;
    }

    /**
     * Get valid column names for the model's table.
     * 
     * This is used to validate column names before using them in raw SQL queries,
     * preventing SQL injection through column name manipulation.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array Array of valid column names
     */
    protected static function getValidColumnNames($model): array
    {
        static $columnCache = [];
        
        $table = $model->getTable();
        
        // Cache column names to avoid repeated database queries
        if (isset($columnCache[$table])) {
            return $columnCache[$table];
        }
        
        // Get columns from database schema
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        
        $columnCache[$table] = $columns;
        
        return $columns;
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
        
        // SECURITY: Validate column name against database schema to prevent SQL injection
        $validColumns = static::getValidColumnNames($model);
        
        if (!in_array($keyColumn, $validColumns, true)) {
            throw new \InvalidArgumentException("Invalid key column: {$keyColumn}");
        }
        
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
        
        // SECURITY: Validate column names against actual database columns
        $validColumns = static::getValidColumnNames($model);
        
        // Validate returning column
        if (!in_array($returningColumn, $validColumns, true)) {
            throw new \InvalidArgumentException("Invalid returning column: {$returningColumn}");
        }
        
        // Get and validate columns from first record
        $columns = array_keys($records[0]);
        $columns = array_filter($columns, function($column) use ($validColumns) {
            return in_array($column, $validColumns, true);
        });
        
        if (empty($columns)) {
            throw new \InvalidArgumentException('No valid columns provided for insert');
        }
        
        // Column names are validated above, safe to use in query
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
     * Processes records in chunks to avoid PostgreSQL parameter limits.
     *
     * @param array $increments Array where key is ID and value is increment amount
     * @param string $column Column to increment
     * @param string $keyColumn Primary key column (default: 'id')
     * @param int $chunkSize Number of records to update per query (default: 1000)
     * @return int Number of rows affected
     */
    public static function bulkIncrement(array $increments, string $column, string $keyColumn = 'id', int $chunkSize = 1000): int
    {
        if (empty($increments)) {
            return 0;
        }

        $model = new static;
        $table = $model->getTable();
        
        // Security: Validate column names against database schema to prevent SQL injection
        $validColumns = static::getValidColumnNames($model);
        
        if (!in_array($column, $validColumns, true)) {
            throw new \InvalidArgumentException("Invalid increment column: {$column}");
        }
        
        if (!in_array($keyColumn, $validColumns, true)) {
            throw new \InvalidArgumentException("Invalid key column: {$keyColumn}");
        }
        
        // Process increments in chunks to avoid parameter limits
        $totalAffected = 0;
        
        DB::transaction(function () use ($increments, $column, $keyColumn, $table, $chunkSize, &$totalAffected) {
            foreach (array_chunk($increments, $chunkSize, true) as $chunk) {
                // Build CASE statement with parameterized queries
                $cases = [];
                $bindings = [];
                
                foreach ($chunk as $id => $amount) {
                    $cases[] = "WHEN {$keyColumn} = ? THEN {$column} + ?";
                    $bindings[] = $id;
                    $bindings[] = $amount;
                }
                
                $caseStatement = "CASE " . implode(' ', $cases) . " ELSE {$column} END";
                
                // Add IDs for WHERE clause
                $ids = array_keys($chunk);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $bindings = array_merge($bindings, $ids);
                
                $query = "UPDATE {$table} SET {$column} = {$caseStatement} WHERE {$keyColumn} IN ({$placeholders})";
                
                $totalAffected += DB::affectingStatement($query, $bindings);
            }
        });
        
        return $totalAffected;
    }
}
