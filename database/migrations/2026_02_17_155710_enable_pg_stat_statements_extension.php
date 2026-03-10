<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Enables the pg_stat_statements extension which provides detailed query statistics.
     * This extension tracks:
     * - Query execution counts
     * - Total/average/min/max execution times
     * - Rows read/written
     * - Query text (normalized)
     *
     * Essential for production monitoring and performance analysis.
     *
     * Note: Requires PostgreSQL superuser privileges. If migration fails:
     * 1. Connect as superuser: psql your_database
     * 2. Run: CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
     * 3. Mark migration as run: php artisan migrate:status
     */
    public function up(): void
    {
        // Only enable on PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Check if extension already exists (installed by superuser)
        $exists = DB::selectOne(
            "SELECT COUNT(*) as count FROM pg_extension WHERE extname = 'pg_stat_statements'"
        );

        if ($exists->count > 0) {
            return; // Already installed
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_stat_statements');
        } catch (\Throwable $e) {
            if (! $this->isPgStatStatementsRecoverableSetupIssue($e)) {
                throw $e;
            }

            if (app()->environment(['testing', 'local'])) {
                // Silent skip for testing/development where superuser access
                // and shared_preload_libraries changes are commonly unavailable.
                return;
            }

            throw new \RuntimeException(
                "Unable to enable pg_stat_statements automatically.\n" .
                "Common causes:\n" .
                "  1) Missing superuser/CREATE EXTENSION privileges\n" .
                "  2) pg_stat_statements not loaded via shared_preload_libraries\n\n" .
                "To fix:\n" .
                "  psql {$this->getDatabaseName()} -c \"SHOW shared_preload_libraries;\"\n" .
                "  # Ensure postgresql.conf includes: shared_preload_libraries = 'pg_stat_statements'\n" .
                "  # Restart PostgreSQL after changing postgresql.conf\n" .
                "  psql {$this->getDatabaseName()} -c \"CREATE EXTENSION IF NOT EXISTS pg_stat_statements;\"\n" .
                "Then run: php artisan migrate:status to verify.",
                0,
                $e
            );
        }
    }

    /**
     * Get the current database name.
     */
    protected function getDatabaseName(): string
    {
        return DB::connection()->getDatabaseName();
    }

    /**
     * Determine whether extension setup failed due to common environment constraints.
     */
    protected function isPgStatStatementsRecoverableSetupIssue(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'permission denied')
            || str_contains($message, 'insufficient privilege')
            || str_contains($message, 'must be loaded via shared_preload_libraries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop on PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('DROP EXTENSION IF EXISTS pg_stat_statements');
        } catch (\Exception $e) {
            // Extension requires superuser - skip silently in test environments
            $message = $e->getMessage();
            if (str_contains($message, 'permission denied') || str_contains($message, 'Insufficient privilege')) {
                if (app()->environment(['testing', 'local'])) {
                    // Silent skip for testing/development
                    return;
                }
                
                throw new \RuntimeException(
                    "pg_stat_statements requires superuser privileges to drop.\n" .
                    "Please run as PostgreSQL superuser:\n" .
                    "  psql {$this->getDatabaseName()} -c \"DROP EXTENSION IF EXISTS pg_stat_statements;\"\n" .
                    "Then run: php artisan migrate:rollback to verify.",
                    0,
                    $e
                );
            }
            throw $e;
        }
    }
};
