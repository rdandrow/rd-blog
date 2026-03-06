<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExplainQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:explain
                            {query? : SQL query to analyze}
                            {--file= : Read query from file}
                            {--format=text : Output format (text, json)}
                            {--buffers : Show buffer usage statistics}
                            {--detailed : Show detailed verbose output}
                            {--no-costs : Exclude cost estimates from output}
                            {--no-execute : Run EXPLAIN without ANALYZE (no execution)}
                            {--allow-write : Allow mutating statements with EXPLAIN ANALYZE (dangerous)}
                            {--suggest : Show optimization suggestions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze PostgreSQL query execution plan using EXPLAIN ANALYZE';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('This command only works with PostgreSQL databases.');
            return self::FAILURE;
        }

        $query = $this->getQuery();
        if (! $query) {
            return self::FAILURE;
        }

        if (! $this->validateQuerySafety($query)) {
            return self::FAILURE;
        }

        $this->info('🔍 Analyzing Query Plan...');
        $this->newLine();

        try {
            $explainOptions = $this->buildExplainOptions();
            $result = $this->executeExplain($query, $explainOptions);

            if ($this->option('format') === 'json') {
                $this->displayJsonResult($result);
            } else {
                $this->displayTextResult($result);
            }

            if ($this->option('suggest')) {
                $this->showSuggestions($result);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to analyze query: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Query: ' . substr($query, 0, 200) . '...');
            return self::FAILURE;
        }
    }

    /**
     * Get the query to analyze from argument or file.
     */
    private function getQuery(): ?string
    {
        if ($file = $this->option('file')) {
            if (! file_exists($file)) {
                $this->error("File not found: {$file}");
                return null;
            }
            $query = file_get_contents($file);
            $this->comment("Reading query from: {$file}");
            $this->newLine();
        } else {
            $query = $this->argument('query');
        }

        if (! $query) {
            $this->error('Please provide a query via argument or --file option.');
            $this->newLine();
            $this->line('Examples:');
            $this->line('  php artisan db:explain "SELECT * FROM blog_posts WHERE is_published = true"');
            $this->line('  php artisan db:explain --file=query.sql');
            return null;
        }

        return trim($query);
    }

    /**
     * Validate query safety for EXPLAIN ANALYZE execution.
     */
    private function validateQuerySafety(string $query): bool
    {
        // EXPLAIN (without ANALYZE) does not execute statements, so it is safe.
        if ($this->option('no-execute')) {
            return true;
        }

        // Explicit opt-in for mutating statements.
        if ($this->option('allow-write')) {
            return true;
        }

        if (! $this->isPotentiallyMutatingQuery($query)) {
            return true;
        }

        $this->error('Mutating statements are blocked by default when using EXPLAIN ANALYZE.');
        $this->line('Use --no-execute to inspect the plan safely without executing the statement.');
        $this->line('If you intentionally want execution, re-run with --allow-write.');

        return false;
    }

    /**
     * Detect whether query text appears to be a mutating statement.
     */
    private function isPotentiallyMutatingQuery(string $query): bool
    {
        $normalized = ltrim($query);
        $normalized = ltrim($normalized, " \t\n\r(");
        $firstToken = strtolower((string) strtok($normalized, " \t\n\r"));

        if ($firstToken === 'with') {
            return preg_match('/\b(insert|update|delete|merge)\b/i', $normalized) === 1;
        }

        $mutatingTokens = [
            'insert',
            'update',
            'delete',
            'merge',
            'truncate',
            'alter',
            'drop',
            'create',
            'grant',
            'revoke',
            'vacuum',
            'analyze',
            'reindex',
            'cluster',
            'comment',
            'call',
            'do',
            'copy',
        ];

        return in_array($firstToken, $mutatingTokens, true);
    }

    /**
     * Build EXPLAIN options based on command flags.
     */
    private function buildExplainOptions(): array
    {
        $options = [];

        if (! $this->option('no-execute')) {
            $options[] = 'ANALYZE';
        }

        if ($this->option('buffers')) {
            $options[] = 'BUFFERS';
        }

        if ($this->option('detailed')) {
            $options[] = 'VERBOSE';
        }

        // Include COSTS by default unless --no-costs flag is set
        if (!$this->option('no-costs')) {
            $options[] = 'COSTS';
        }

        if ($this->option('format') === 'json') {
            $options[] = 'FORMAT JSON';
        } else {
            $options[] = 'FORMAT TEXT';
        }

        return $options;
    }

    /**
     * Execute EXPLAIN with specified options.
     */
    private function executeExplain(string $query, array $options): array
    {
        $optionsStr = implode(', ', $options);
        $explainQuery = "EXPLAIN ({$optionsStr}) {$query}";

        return DB::select($explainQuery);
    }

    /**
     * Display results in text format.
     */
    private function displayTextResult(array $result): void
    {
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('<fg=cyan>Query Execution Plan</>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $executionTime = null;
        $planningTime = null;

        foreach ($result as $row) {
            $line = is_object($row) ? $row->{'QUERY PLAN'} : $row['QUERY PLAN'];

            // Extract and highlight key information
            if (str_contains($line, 'Seq Scan')) {
                $this->line('<fg=yellow>' . $line . '</>');
            } elseif (str_contains($line, 'Index')) {
                $this->line('<fg=green>' . $line . '</>');
            } elseif (str_contains($line, 'cost=')) {
                $this->line('<fg=blue>' . $line . '</>');
            } elseif (str_contains($line, 'Planning Time:')) {
                preg_match('/Planning Time: ([\d.]+)/', $line, $matches);
                $planningTime = $matches[1] ?? null;
                $this->line('<fg=magenta>' . $line . '</>');
            } elseif (str_contains($line, 'Execution Time:')) {
                preg_match('/Execution Time: ([\d.]+)/', $line, $matches);
                $executionTime = $matches[1] ?? null;
                $this->line('<fg=magenta>' . $line . '</>');
            } else {
                $this->line($line);
            }
        }

        $this->newLine();
        $this->displayTimingSummary($executionTime, $planningTime);
    }

    /**
     * Display results in JSON format.
     */
    private function displayJsonResult(array $result): void
    {
        $jsonPlan = is_object($result[0]) ? $result[0]->{'QUERY PLAN'} : $result[0]['QUERY PLAN'];
        $plan = json_decode($jsonPlan, true);

        $this->line(json_encode($plan, JSON_PRETTY_PRINT));
        $this->newLine();

        if (isset($plan[0]['Planning Time']) || isset($plan[0]['Execution Time'])) {
            $this->displayTimingSummary(
                $plan[0]['Execution Time'] ?? null,
                $plan[0]['Planning Time'] ?? null
            );
        }
    }

    /**
     * Display timing summary with color-coded performance indicators.
     */
    private function displayTimingSummary(float|string|null $executionTime, float|string|null $planningTime): void
    {
        // Convert to float if string
        $executionTime = $executionTime ? (float) $executionTime : null;
        $planningTime = $planningTime ? (float) $planningTime : null;

        if (! $executionTime && ! $planningTime) {
            return;
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('<fg=cyan>Timing Summary</>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($planningTime) {
            $color = $this->getTimingColor($planningTime);
            $this->line("<fg={$color}>Planning Time: {$planningTime} ms</>");
        }

        if ($executionTime) {
            $color = $this->getTimingColor($executionTime);
            $this->line("<fg={$color}>Execution Time: {$executionTime} ms</>");
        }

        if ($executionTime && $planningTime) {
            $total = $executionTime + $planningTime;
            $color = $this->getTimingColor($total);
            $this->line("<fg={$color}>Total Time: " . number_format($total, 3) . " ms</>");
        }

        $this->newLine();
        $this->displayPerformanceAssessment($executionTime ?? 0);
    }

    /**
     * Get color based on timing threshold.
     */
    private function getTimingColor(float $timeMs): string
    {
        if ($timeMs < 10) {
            return 'green';
        }
        if ($timeMs < 100) {
            return 'yellow';
        }
        if ($timeMs < 500) {
            return 'red';
        }
        return 'red';
    }

    /**
     * Display performance assessment message.
     */
    private function displayPerformanceAssessment(float $timeMs): void
    {
        if ($timeMs < 10) {
            $this->info('✓ Excellent performance - Query executes very fast');
        } elseif ($timeMs < 100) {
            $this->comment('⚠ Good performance - Consider optimization if this runs frequently');
        } elseif ($timeMs < 500) {
            $this->warn('⚠ Slow query - Should be optimized');
        } else {
            $this->error('✗ Very slow query - Optimization required');
        }
    }

    /**
     * Analyze plan and show optimization suggestions.
     */
    private function showSuggestions(array $result): void
    {
        $planText = implode("\n", array_map(function ($row) {
            return is_object($row) ? $row->{'QUERY PLAN'} : $row['QUERY PLAN'];
        }, $result));

        $suggestions = [];

        // Check for sequential scans
        if (preg_match('/Seq Scan on (\w+)/', $planText, $matches)) {
            $suggestions[] = "🔍 Sequential scan detected on table '{$matches[1]}'";
            $suggestions[] = "   Consider adding an index on the filtered columns";
        }

        // Check for missing indexes
        if (str_contains($planText, 'Seq Scan') && str_contains($planText, 'Filter:')) {
            $suggestions[] = '📊 Table scan with filter condition detected';
            $suggestions[] = '   An index on the filter column(s) could improve performance';
        }

        // Check for high costs
        if (preg_match('/cost=[\d.]+\.\.([\d.]+)/', $planText, $matches)) {
            $cost = (float) $matches[1];
            if ($cost > 10000) {
                $suggestions[] = '💰 High query cost detected (' . number_format($cost, 2) . ')';
                $suggestions[] = '   Review the query plan for optimization opportunities';
            }
        }

        // Check for sorts
        if (str_contains($planText, 'Sort') && str_contains($planText, 'external')) {
            $suggestions[] = '💾 External sort detected (using disk instead of memory)';
            $suggestions[] = '   Consider increasing work_mem or adding an index for sorting';
        }

        // Check for nested loops with many rows
        if (preg_match('/Nested Loop.*rows=(\d+)/', $planText, $matches)) {
            $rows = (int) $matches[1];
            if ($rows > 10000) {
                $suggestions[] = '🔄 Nested loop with many rows (' . number_format($rows) . ')';
                $suggestions[] = '   Consider using a hash join by adding appropriate indexes';
            }
        }

        if (empty($suggestions)) {
            $this->newLine();
            $this->info('✓ No obvious optimization opportunities detected');
            return;
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('<fg=cyan>Optimization Suggestions</>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        foreach ($suggestions as $suggestion) {
            $this->line("<fg=yellow>{$suggestion}</>");
        }

        $this->newLine();
    }
}
