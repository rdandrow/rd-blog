<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeQueryPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze-performance 
                            {--table=* : Specific tables to analyze}
                            {--indexes : Show index usage statistics}
                            {--slow-queries : Show slow query patterns}
                            {--cache : Show cache hit rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze PostgreSQL database query performance and index usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('This command only works with PostgreSQL databases.');
            return self::FAILURE;
        }

        $this->info('🔍 PostgreSQL Performance Analysis');
        $this->newLine();

        if ($this->option('indexes')) {
            $this->showIndexUsage();
        }

        if ($this->option('slow-queries')) {
            $this->showSlowQueries();
        }

        if ($this->option('cache')) {
            $this->showCacheStats();
        }

        if (!$this->option('indexes') && !$this->option('slow-queries') && !$this->option('cache')) {
            // Show all by default
            $this->showIndexUsage();
            $this->newLine(2);
            $this->showTableStats();
            $this->newLine(2);
            $this->showCacheStats();
        }

        return self::SUCCESS;
    }

    /**
     * Show index usage statistics.
     */
    protected function showIndexUsage(): void
    {
        $this->info('📊 Index Usage Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $tables = $this->option('table') ?: ['blog_posts', 'comments', 'blog_post_likes', 'user_follows'];

        foreach ($tables as $table) {
            $indexes = DB::select("
                SELECT 
                    schemaname,
                    relname as tablename,
                    indexrelname as indexname,
                    idx_scan,
                    idx_tup_read,
                    idx_tup_fetch,
                    pg_size_pretty(pg_relation_size(indexrelid)) as size
                FROM pg_stat_user_indexes
                WHERE schemaname = 'public' 
                AND relname = ?
                ORDER BY idx_scan DESC
            ", [$table]);

            if (empty($indexes)) {
                continue;
            }

            $this->newLine();
            $this->line("Table: <comment>{$table}</comment>");
            
            $data = array_map(function ($index) {
                return [
                    'Index' => $index->indexname,
                    'Scans' => number_format($index->idx_scan),
                    'Tuples Read' => number_format($index->idx_tup_read),
                    'Size' => $index->size,
                    'Status' => $index->idx_scan > 0 ? '✓ Used' : '⚠ Unused',
                ];
            }, $indexes);

            $this->table(
                ['Index', 'Scans', 'Tuples Read', 'Size', 'Status'],
                $data
            );
        }
    }

    /**
     * Show table statistics.
     */
    protected function showTableStats(): void
    {
        $this->info('📈 Table Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $tables = $this->option('table') ?: ['blog_posts', 'comments', 'blog_post_likes', 'user_follows', 'users'];

        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        
        $stats = DB::select("
            SELECT 
                schemaname,
                relname as tablename,
                n_tup_ins as inserts,
                n_tup_upd as updates,
                n_tup_del as deletes,
                n_live_tup as live_tuples,
                n_dead_tup as dead_tuples,
                last_vacuum,
                last_autovacuum,
                pg_size_pretty(pg_total_relation_size(quote_ident(schemaname) || '.' || quote_ident(relname))) as total_size
            FROM pg_stat_user_tables
            WHERE schemaname = 'public'
            AND relname IN ({$placeholders})
            ORDER BY pg_total_relation_size(quote_ident(schemaname) || '.' || quote_ident(relname)) DESC
        ", $tables);

        $data = array_map(function ($stat) {
            $deadPercent = $stat->live_tuples > 0 
                ? round(($stat->dead_tuples / $stat->live_tuples) * 100, 1) 
                : 0;

            return [
                'Table' => $stat->tablename,
                'Rows' => number_format($stat->live_tuples),
                'Dead' => number_format($stat->dead_tuples) . " ({$deadPercent}%)",
                'Size' => $stat->total_size,
                'Vacuum' => $stat->last_autovacuum ? 'Auto' : ($stat->last_vacuum ? 'Manual' : 'Never'),
            ];
        }, $stats);

        $this->table(
            ['Table', 'Rows', 'Dead Tuples', 'Size', 'Vacuum'],
            $data
        );

        // Check for bloat
        foreach ($stats as $stat) {
            $deadPercent = $stat->live_tuples > 0 
                ? ($stat->dead_tuples / $stat->live_tuples) * 100 
                : 0;

            if ($deadPercent > 20) {
                $this->warn("⚠ Table {$stat->tablename} has high dead tuple percentage ({$deadPercent}%). Consider running VACUUM.");
            }
        }
    }

    /**
     * Show cache hit rates.
     */
    protected function showCacheStats(): void
    {
        $this->info('💾 Cache Hit Rates');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Database cache hit rate
        $dbCache = DB::selectOne("
            SELECT 
                sum(heap_blks_read) as heap_read,
                sum(heap_blks_hit) as heap_hit,
                sum(heap_blks_hit) / nullif(sum(heap_blks_hit) + sum(heap_blks_read), 0) * 100 as cache_hit_rate
            FROM pg_statio_user_tables
        ");

        $cacheRate = round($dbCache->cache_hit_rate ?? 0, 2);
        $status = $cacheRate > 99 ? '✓' : ($cacheRate > 90 ? '⚠' : '✗');

        $this->line("Database Cache Hit Rate: <comment>{$status} {$cacheRate}%</comment>");
        
        if ($cacheRate < 90) {
            $this->warn('Low cache hit rate detected. Consider increasing shared_buffers in postgresql.conf');
        } elseif ($cacheRate > 99) {
            $this->info('Excellent cache performance!');
        }

        // Index cache hit rate
        $indexCache = DB::selectOne("
            SELECT 
                sum(idx_blks_read) as idx_read,
                sum(idx_blks_hit) as idx_hit,
                sum(idx_blks_hit) / nullif(sum(idx_blks_hit) + sum(idx_blks_read), 0) * 100 as cache_hit_rate
            FROM pg_statio_user_indexes
        ");

        $indexCacheRate = round($indexCache->cache_hit_rate ?? 0, 2);
        $indexStatus = $indexCacheRate > 99 ? '✓' : ($indexCacheRate > 90 ? '⚠' : '✗');

        $this->line("Index Cache Hit Rate: <comment>{$indexStatus} {$indexCacheRate}%</comment>");
    }

    /**
     * Show slow query patterns (requires pg_stat_statements extension).
     */
    protected function showSlowQueries(): void
    {
        $this->info('🐢 Slow Query Analysis');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Check if pg_stat_statements is enabled
        $hasExtension = DB::selectOne("
            SELECT COUNT(*) as count 
            FROM pg_extension 
            WHERE extname = 'pg_stat_statements'
        ");

        if ($hasExtension->count == 0) {
            $this->warn('pg_stat_statements extension not enabled. Enable it for detailed query statistics.');
            $this->line('Run: CREATE EXTENSION IF NOT EXISTS pg_stat_statements;');
            return;
        }

        $slowQueries = DB::select("
            SELECT 
                substring(query, 1, 100) as query_start,
                calls,
                round(mean_exec_time::numeric, 2) as avg_time_ms,
                round(max_exec_time::numeric, 2) as max_time_ms,
                round((total_exec_time / 1000)::numeric, 2) as total_time_sec
            FROM pg_stat_statements
            WHERE query NOT LIKE '%pg_stat_statements%'
            AND mean_exec_time > 10
            ORDER BY mean_exec_time DESC
            LIMIT 10
        ");

        if (empty($slowQueries)) {
            $this->info('No slow queries detected (threshold: >10ms)');
            return;
        }

        $data = array_map(function ($query) {
            return [
                'Query' => $query->query_start . '...',
                'Calls' => number_format($query->calls),
                'Avg Time' => $query->avg_time_ms . 'ms',
                'Max Time' => $query->max_time_ms . 'ms',
                'Total' => $query->total_time_sec . 's',
            ];
        }, $slowQueries);

        $this->table(
            ['Query (first 100 chars)', 'Calls', 'Avg Time', 'Max Time', 'Total'],
            $data
        );
    }
}
