<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('Blog Post Views Migration', function () {
    test('creates blog_post_views table with expected columns', function () {
        expect(Schema::hasTable('blog_post_views'))->toBeTrue();

        expect(Schema::hasColumns('blog_post_views', [
            'id',
            'blog_post_id',
            'user_id',
            'session_id',
            'ip_hash',
            'user_agent_hash',
            'viewed_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
    });

    test('creates expected indexes for blog_post_views on pgsql', function () {
        if (DB::getDriverName() !== 'pgsql') {
            $this->assertTrue(true);

            return;
        }

        $indexes = collect(DB::select("select indexname from pg_indexes where schemaname = 'public' and tablename = 'blog_post_views'"))
            ->map(fn ($row) => $row->indexname)
            ->values()
            ->all();

        expect($indexes)->toContain('blog_post_views_viewed_at_index')
            ->and($indexes)->toContain('blog_post_views_blog_post_id_viewed_at_index')
            ->and($indexes)->toContain('blog_post_views_session_id_blog_post_id_viewed_at_index')
            ->and(collect($indexes)->contains(fn (string $indexName) => str_contains($indexName, 'ip_hash')
                && str_contains($indexName, 'user_agent_hash')
                && str_contains($indexName, 'blog_post_id')
                && str_contains($indexName, 'viewed_at')))->toBeTrue();
    });
});
