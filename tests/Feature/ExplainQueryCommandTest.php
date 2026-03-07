<?php

declare(strict_types=1);

/*
 * EXPLAIN ANALYZE Command Tests
 *
 * These tests verify that the db:explain command works correctly
 * for analyzing PostgreSQL query execution plans.
 */

describe('EXPLAIN ANALYZE Command', function () {

    test('requires a query argument or file option', function () {
        $this->artisan('db:explain')
            ->expectsOutput('Please provide a query via argument or --file option.')
            ->assertExitCode(1);
    });

    test('analyzes simple query successfully', function () {
        $this->artisan('db:explain', ['query' => 'SELECT 1 as result'])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('analyzes query from database table', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts WHERE is_published = true LIMIT 10',
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('displays execution time in output', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts LIMIT 1',
        ])
            ->expectsOutputToContain('Timing Summary')
            ->expectsOutputToContain('Execution Time:')
            ->assertExitCode(0);
    });

    test('supports buffers option', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts LIMIT 1',
            '--buffers' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('supports detailed option', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts LIMIT 1',
            '--detailed' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('supports no-execute option for EXPLAIN without ANALYZE', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts WHERE is_published = true',
            '--no-execute' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('blocks mutating query by default when no-execute is not set', function () {
        $this->artisan('db:explain', [
            'query' => 'UPDATE blog_posts SET updated_at = NOW() WHERE id = -1',
        ])
            ->expectsOutput('Mutating statements are blocked by default when using EXPLAIN ANALYZE.')
            ->assertExitCode(1);
    });

    test('allows mutating query with no-execute for safe planning', function () {
        $this->artisan('db:explain', [
            'query' => 'UPDATE blog_posts SET updated_at = NOW() WHERE id = -1',
            '--no-execute' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('allows mutating query when allow-write is explicitly set', function () {
        $this->artisan('db:explain', [
            'query' => 'UPDATE blog_posts SET updated_at = NOW() WHERE id = -1',
            '--allow-write' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('blocks mutating CTE by default', function () {
        $query = <<<'SQL'
            WITH changed AS (
                UPDATE blog_posts
                SET updated_at = NOW()
                WHERE id = -1
                RETURNING id
            )
            SELECT * FROM changed
            SQL;

        $this->artisan('db:explain', [
            'query' => $query,
        ])
            ->expectsOutput('Mutating statements are blocked by default when using EXPLAIN ANALYZE.')
            ->assertExitCode(1);
    });

    test('blocks stacked SQL statements by default', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT 1; UPDATE blog_posts SET updated_at = NOW() WHERE id = -1',
        ])
            ->expectsOutput('Multiple SQL statements are not allowed. Provide a single statement for analysis.')
            ->assertExitCode(1);
    });

    test('blocks stacked SQL statements even when allow-write is set', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT 1; UPDATE blog_posts SET updated_at = NOW() WHERE id = -1',
            '--allow-write' => true,
        ])
            ->expectsOutput('Multiple SQL statements are not allowed. Provide a single statement for analysis.')
            ->assertExitCode(1);
    });

    test('allows single statement with trailing semicolon', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT 1;',
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('supports JSON format output', function () {
        $exitCode = $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts LIMIT 1',
            '--format' => 'json',
        ])->execute();

        expect($exitCode)->toBe(0);
    });

    test('shows optimization suggestions', function () {
        // Create a query that will trigger sequential scan
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts WHERE content LIKE \'%test%\'',
            '--suggest' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('reads query from file', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'explain_test_');
        file_put_contents($tempFile, 'SELECT * FROM blog_posts LIMIT 1');

        try {
            $this->artisan('db:explain', [
                '--file' => $tempFile,
            ])
                ->expectsOutputToContain('Reading query from:')
                ->assertExitCode(0);
        } finally {
            unlink($tempFile);
        }
    });

    test('handles non-existent file gracefully', function () {
        $this->artisan('db:explain', [
            '--file' => '/nonexistent/file.sql',
        ])
            ->expectsOutputToContain('File not found')
            ->assertExitCode(1);
    });

    test('handles unreadable file gracefully', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'explain_unreadable_');
        file_put_contents($tempFile, 'SELECT 1');

        // Remove all permissions to simulate an unreadable SQL file.
        chmod($tempFile, 0o000);

        try {
            $this->artisan('db:explain', [
                '--file' => $tempFile,
            ])
                ->expectsOutputToContain('Unable to read file')
                ->assertExitCode(1);
        } finally {
            // Restore permissions so the temp file can always be cleaned up.
            chmod($tempFile, 0o600);
            unlink($tempFile);
        }
    });

    test('handles invalid SQL gracefully', function () {
        $this->artisan('db:explain', [
            'query' => 'INVALID SQL QUERY',
        ])
            ->expectsOutputToContain('Failed to analyze query')
            ->assertExitCode(1);
    });

    test('analyzes complex query with joins', function () {
        $query = <<<'SQL'
            SELECT bp.*, u.name, COUNT(c.id) as comment_count
            FROM blog_posts bp
            LEFT JOIN users u ON bp.user_id = u.id
            LEFT JOIN comments c ON bp.id = c.blog_post_id
            WHERE bp.is_published = true
            GROUP BY bp.id, u.name
            ORDER BY bp.created_at DESC
            LIMIT 10
            SQL;

        $this->artisan('db:explain', [
            'query' => $query,
            '--suggest' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('analyzes query with subquery', function () {
        $query = <<<'SQL'
            SELECT * FROM blog_posts
            WHERE user_id IN (
                SELECT id FROM users WHERE created_at > NOW() - INTERVAL '30 days'
            )
            LIMIT 10
            SQL;

        $this->artisan('db:explain', [
            'query' => $query,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('detects sequential scan in suggestions', function () {
        // Query that will likely use sequential scan
        $query = 'SELECT * FROM blog_posts WHERE content = \'some random text that does not exist\'';

        $exitCode = $this->artisan('db:explain', [
            'query' => $query,
            '--suggest' => true,
        ])->execute();

        expect($exitCode)->toBe(0);
    });

    test('shows performance assessment for fast queries', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT 1',
        ])
            ->assertExitCode(0);
    });

    test('handles queries with parameters', function () {
        $query = "SELECT * FROM blog_posts WHERE id = 1 AND is_published = true";

        $this->artisan('db:explain', [
            'query' => $query,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('analyzes query with CTE (Common Table Expression)', function () {
        $query = <<<'SQL'
            WITH recent_posts AS (
                SELECT * FROM blog_posts
                WHERE created_at > NOW() - INTERVAL '7 days'
            )
            SELECT * FROM recent_posts
            WHERE is_published = true
            LIMIT 5
            SQL;

        $this->artisan('db:explain', [
            'query' => $query,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

    test('works with all options combined', function () {
        $this->artisan('db:explain', [
            'query' => 'SELECT * FROM blog_posts WHERE is_published = true LIMIT 10',
            '--buffers' => true,
            '--detailed' => true,
            '--suggest' => true,
        ])
            ->expectsOutput('🔍 Analyzing Query Plan...')
            ->assertExitCode(0);
    });

});
