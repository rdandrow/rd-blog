<?php

declare(strict_types=1);

describe('PostgreSQL migration safety', function () {

    it('uses non-transactional migrations for concurrent index operations', function () {
        $files = [
            base_path('database/migrations/2026_02_16_000000_add_postgresql_optimized_indexes.php'),
            base_path('database/migrations/2026_02_17_230335_add_postgresql_covering_indexes.php'),
            base_path('database/migrations/2026_02_17_232335_add_postgresql_partial_tag_indexes.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            expect($content)->not->toBeFalse()
                ->and($content)->toContain('$withinTransaction = false')
                ->and($content)->toContain('CONCURRENTLY');
        }
    });

    it('handles pg_stat_statements preload/privilege setup failures with actionable guidance', function () {
        $file = base_path('database/migrations/2026_02_17_155710_enable_pg_stat_statements_extension.php');
        $content = file_get_contents($file);

        expect($content)->not->toBeFalse()
            ->and($content)->toContain('shared_preload_libraries')
            ->and($content)->toContain('isPgStatStatementsRecoverableSetupIssue')
            ->and($content)->toContain('SHOW shared_preload_libraries')
            ->and($content)->toContain("app()->environment(['testing', 'local'])");
    });

});
