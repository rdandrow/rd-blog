<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckFullTextSearchLanguage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-fts-language
                            {--index=blog_posts_search_index : Full-text index name to inspect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify PostgreSQL full-text index language matches database.full_text_search.language';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('This command only works with PostgreSQL databases.');
            return self::FAILURE;
        }

        $configuredLanguage = (string) config('database.full_text_search.language', 'english');

        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $configuredLanguage)) {
            $this->error("Invalid configured full-text language: {$configuredLanguage}");
            return self::FAILURE;
        }

        $indexName = (string) $this->option('index');
        $indexExpression = $this->getIndexExpression($indexName);

        if ($indexExpression === null) {
            $this->error("Index not found or does not use expression columns: {$indexName}");
            $this->line('Run migrations, or recreate the full-text index before using this check.');
            return self::FAILURE;
        }

        $indexLanguage = $this->extractTsVectorLanguage($indexExpression);

        if ($indexLanguage === null) {
            $this->error("Could not determine to_tsvector language from index expression for: {$indexName}");
            $this->line('Expression: ' . $indexExpression);
            return self::FAILURE;
        }

        if ($indexLanguage !== $configuredLanguage) {
            $this->warn('Full-text language mismatch detected.');
            $this->line("Config language : {$configuredLanguage}");
            $this->line("Index language  : {$indexLanguage}");
            $this->newLine();
            $this->line('Action required: create and run a migration to drop/recreate the full-text index with the configured language.');
            return self::FAILURE;
        }

        $this->info('✓ Full-text language is aligned.');
        $this->line("Config/index language: {$configuredLanguage}");

        return self::SUCCESS;
    }

    /**
     * Get index expression for a PostgreSQL expression index.
     */
    private function getIndexExpression(string $indexName): ?string
    {
        $row = DB::selectOne(
            <<<'SQL'
                SELECT pg_get_expr(i.indexprs, i.indrelid) AS expression
                FROM pg_index i
                JOIN pg_class idx ON idx.oid = i.indexrelid
                WHERE idx.relname = ?
                LIMIT 1
            SQL,
            [$indexName]
        );

        if ($row === null) {
            return null;
        }

        $expression = is_object($row) ? ($row->expression ?? null) : ($row['expression'] ?? null);

        if (!is_string($expression) || trim($expression) === '') {
            return null;
        }

        return $expression;
    }

    /**
     * Extract the language token from to_tsvector('<language>', ...).
     */
    private function extractTsVectorLanguage(string $expression): ?string
    {
        if (!preg_match("/to_tsvector\\('([^']+)'(?:\\:\\:regconfig)?\\s*,/i", $expression, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }
}
