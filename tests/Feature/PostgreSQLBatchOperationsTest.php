<?php

declare(strict_types=1);

use App\Database\Concerns\HasBatchOperations;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * PostgreSQL Batch Operations Tests
 *
 * These tests verify the batch operation optimizations that leverage
 * PostgreSQL-specific features for improved performance.
 */

describe('PostgreSQL Batch Operations', function () {

    describe('bulkInsert', function () {

        it('inserts records and returns the correct count', function () {
            $user = User::factory()->create();

            $records = [
                [
                    'title' => 'Bulk Insert 1',
                    'slug' => 'bulk-insert-1',
                    'excerpt' => 'Excerpt 1',
                    'content' => 'Content 1',
                    'user_id' => $user->id,
                    'is_published' => true,
                ],
                [
                    'title' => 'Bulk Insert 2',
                    'slug' => 'bulk-insert-2',
                    'excerpt' => 'Excerpt 2',
                    'content' => 'Content 2',
                    'user_id' => $user->id,
                    'is_published' => false,
                ],
            ];

            $count = BlogPost::bulkInsert($records);

            expect($count)->toBe(2);
            expect(BlogPost::where('slug', 'bulk-insert-1')->exists())->toBeTrue();
            expect(BlogPost::where('slug', 'bulk-insert-2')->exists())->toBeTrue();
        });

        it('handles JSON/array columns', function () {
            $user = User::factory()->create();

            $records = [
                [
                    'title' => 'Tagged Post',
                    'slug' => 'tagged-post-bulk',
                    'excerpt' => 'Excerpt',
                    'content' => 'Content',
                    'user_id' => $user->id,
                    'tags' => ['laravel', 'php'],
                ],
            ];

            BlogPost::bulkInsert($records);

            $post = BlogPost::where('slug', 'tagged-post-bulk')->first();
            expect($post->tags)->toBe(['laravel', 'php']);
        });

        it('returns 0 for empty input', function () {
            $count = BlogPost::bulkInsert([]);
            expect($count)->toBe(0);

            $user = User::factory()->create();
            $records = [[
                'title' => 'Invalid Chunk',
                'slug' => 'invalid-chunk-bulk-insert',
                'excerpt' => 'Excerpt',
                'content' => 'Content',
                'user_id' => $user->id,
            ]];

            expect(fn () => BlogPost::bulkInsert($records, chunkSize: 0))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('processes records across chunk boundaries', function () {
            $user = User::factory()->create();

            // 5 records with a chunkSize of 2 forces 3 separate INSERT queries
            $records = [];
            for ($i = 1; $i <= 5; $i++) {
                $records[] = [
                    'title' => "Chunk Post {$i}",
                    'slug' => "chunk-post-{$i}",
                    'excerpt' => 'Excerpt',
                    'content' => 'Content',
                    'user_id' => $user->id,
                ];
            }

            $count = BlogPost::bulkInsert($records, chunkSize: 2);

            expect($count)->toBe(5);
            expect(BlogPost::where('slug', 'like', 'chunk-post-%')->count())->toBe(5);
        });

    });

    describe('upsertBatch', function () {

        it('inserts new records when they do not exist', function () {
            $user = User::factory()->create();

            $posts = [
                [
                    'title' => 'Test Post 1',
                    'slug' => 'test-post-1',
                    'excerpt' => 'Excerpt 1',
                    'content' => 'Content 1',
                    'user_id' => $user->id,
                    'is_published' => true,
                ],
                [
                    'title' => 'Test Post 2',
                    'slug' => 'test-post-2',
                    'excerpt' => 'Excerpt 2',
                    'content' => 'Content 2',
                    'user_id' => $user->id,
                    'is_published' => false,
                ],
            ];

            $affected = BlogPost::upsertBatch($posts, 'slug');

            expect($affected)->toBe(2);
            expect(BlogPost::where('slug', 'test-post-1')->exists())->toBeTrue();
            expect(BlogPost::where('slug', 'test-post-2')->exists())->toBeTrue();
        });

        it('updates existing records on conflict', function () {
            $user = User::factory()->create();
            $existingPost = BlogPost::factory()->create([
                'slug' => 'existing-post',
                'title' => 'Original Title',
                'user_id' => $user->id,
            ]);

            $posts = [
                [
                    'title' => 'Updated Title',
                    'slug' => 'existing-post',
                    'excerpt' => $existingPost->excerpt,
                    'content' => $existingPost->content,
                    'user_id' => $user->id,
                    'is_published' => true,
                ],
            ];

            BlogPost::upsertBatch($posts, 'slug', ['title', 'is_published']);

            $existingPost->refresh();
            expect($existingPost->title)->toBe('Updated Title');
            expect($existingPost->is_published)->toBeTrue();
        });

    });

    describe('bulkUpdate', function () {

        it('updates multiple records with different values', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(3)->create(['user_id' => $user->id]);

            $updates = [
                $posts[0]->id => ['title' => 'Updated Title 1', 'is_featured' => true],
                $posts[1]->id => ['title' => 'Updated Title 2', 'is_featured' => false],
                $posts[2]->id => ['title' => 'Updated Title 3', 'is_featured' => true],
            ];

            $affected = BlogPost::bulkUpdate($updates);

            expect($affected)->toBe(3);

            $posts[0]->refresh();
            $posts[1]->refresh();
            $posts[2]->refresh();

            expect($posts[0]->title)->toBe('Updated Title 1');
            expect($posts[0]->is_featured)->toBeTrue();
            expect($posts[1]->title)->toBe('Updated Title 2');
            expect($posts[1]->is_featured)->toBeFalse();
            expect($posts[2]->title)->toBe('Updated Title 3');
        });

        it('handles null values correctly', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(2)->create([
                'user_id' => $user->id,
                'featured_image' => 'original.jpg',
            ]);

            $updates = [
                $posts[0]->id => ['featured_image' => null],
                $posts[1]->id => ['featured_image' => 'new-image.jpg'],
            ];

            BlogPost::bulkUpdate($updates);

            $posts[0]->refresh();
            $posts[1]->refresh();

            expect($posts[0]->featured_image)->toBeNull();
            expect($posts[1]->featured_image)->toBe('new-image.jpg');
        });

        it('handles JSON data correctly', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(2)->create(['user_id' => $user->id]);

            $updates = [
                $posts[0]->id => ['tags' => ['php', 'laravel']],
                $posts[1]->id => ['tags' => ['javascript', 'vue']],
            ];

            BlogPost::bulkUpdate($updates);

            $posts[0]->refresh();
            $posts[1]->refresh();

            expect($posts[0]->tags)->toBe(['php', 'laravel']);
            expect($posts[1]->tags)->toBe(['javascript', 'vue']);
        });

        it('handles boolean values correctly', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(2)->create([
                'user_id' => $user->id,
                'is_featured' => false,
                'is_published' => true,
            ]);

            $updates = [
                $posts[0]->id => ['is_featured' => true,  'is_published' => false],
                $posts[1]->id => ['is_featured' => false, 'is_published' => true],
            ];

            BlogPost::bulkUpdate($updates);

            $posts[0]->refresh();
            $posts[1]->refresh();

            expect($posts[0]->is_featured)->toBeTrue();
            expect($posts[0]->is_published)->toBeFalse();
            expect($posts[1]->is_featured)->toBeFalse();
            expect($posts[1]->is_published)->toBeTrue();
        });

        it('processes records across chunk boundaries', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(5)->create(['user_id' => $user->id]);

            // chunkSize of 2 with 5 records forces 3 query chunks
            $updates = collect($posts)->mapWithKeys(fn ($p, $i) => [
                $p->id => ['title' => "Chunked Title {$i}"],
            ])->all();

            $affected = BlogPost::bulkUpdate($updates, chunkSize: 2);

            expect($affected)->toBe(5);
            foreach ($posts as $i => $post) {
                expect($post->fresh()->title)->toBe("Chunked Title {$i}");
            }
        });

        it('silently ignores columns not present in the schema', function () {
            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id, 'title' => 'Original']);

            // 'nonexistent_column' is not a real column — it must be silently dropped
            $updates = [
                $post->id => ['title' => 'Updated', 'nonexistent_column' => 'bad value'],
            ];

            $affected = BlogPost::bulkUpdate($updates);

            expect($affected)->toBe(1);
            expect($post->fresh()->title)->toBe('Updated');
        });

        it('returns 0 when all supplied columns are invalid', function () {
            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id]);

            $updates = [
                $post->id => ['totally_fake' => 'value', 'also_fake' => 'value'],
            ];

            $affected = BlogPost::bulkUpdate($updates);
            expect($affected)->toBe(0);
        });

        it('throws for an invalid keyColumn', function () {
            $user  = User::factory()->create();
            $post  = BlogPost::factory()->create(['user_id' => $user->id]);

            expect(fn () => BlogPost::bulkUpdate(
                [$post->id => ['title' => 'X']],
                keyColumn: 'nonexistent_key'
            ))->toThrow(\InvalidArgumentException::class);
        });

        it('generates wrapped identifiers in bulkUpdate SQL', function () {
            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id, 'title' => 'Original']);

            DB::flushQueryLog();
            DB::enableQueryLog();

            BlogPost::bulkUpdate([
                $post->id => ['title' => 'Wrapped'],
            ]);

            $query = collect(DB::getQueryLog())
                ->pluck('query')
                ->first(fn (string $sql) => str_starts_with($sql, 'UPDATE'));

            DB::disableQueryLog();

            expect($query)->not->toBeNull()
                ->and($query)->toContain('UPDATE "blog_posts"')
                ->and($query)->toContain('"title" = CASE')
                ->and($query)->toContain('"id" IN');
        });

        it('returns zero for empty updates', function () {
            $affected = BlogPost::bulkUpdate([]);
            expect($affected)->toBe(0);

            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id]);

            expect(fn () => BlogPost::bulkUpdate([
                $post->id => ['title' => 'Invalid chunk size'],
            ], chunkSize: 0))->toThrow(\InvalidArgumentException::class);
        });

    });

    describe('bulkDelete', function () {

        it('deletes multiple records in one query', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(5)->create(['user_id' => $user->id]);

            $idsToDelete = [$posts[0]->id, $posts[2]->id, $posts[4]->id];

            $affected = BlogPost::bulkDelete($idsToDelete);

            expect($affected)->toBe(3);
            expect(BlogPost::whereIn('id', $idsToDelete)->count())->toBe(0);
            expect(BlogPost::count())->toBe(2);
        });

        it('returns zero for empty array', function () {
            $affected = BlogPost::bulkDelete([]);
            expect($affected)->toBe(0);
        });

        it('works with custom key column', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(3)->create(['user_id' => $user->id]);

            $slugsToDelete = [$posts[0]->slug, $posts[2]->slug];

            $affected = BlogPost::bulkDelete($slugsToDelete, 'slug');

            expect($affected)->toBe(2);
            expect(BlogPost::whereIn('slug', $slugsToDelete)->count())->toBe(0);
        });

        it('returns 0 when none of the IDs exist', function () {
            $affected = BlogPost::bulkDelete([999999, 999998]);
            expect($affected)->toBe(0);
        });

        it('deletes only existing records when IDs are mixed', function () {
            $user  = User::factory()->create();
            $posts = BlogPost::factory(2)->create(['user_id' => $user->id]);

            $ids = [$posts[0]->id, $posts[1]->id, 999999];

            $affected = BlogPost::bulkDelete($ids);

            expect($affected)->toBe(2);
        });

        it('throws for an invalid keyColumn', function () {
            expect(fn () => BlogPost::bulkDelete([1], 'nonexistent_key'))
                ->toThrow(\InvalidArgumentException::class);
        });

    });

    describe('insertReturning', function () {

        it('inserts records and returns IDs', function () {
            $user = User::factory()->create();

            $posts = [
                [
                    'title' => 'Post 1',
                    'slug' => 'post-1',
                    'excerpt' => 'Excerpt 1',
                    'content' => 'Content 1',
                    'user_id' => $user->id,
                    'is_published' => true,
                ],
                [
                    'title' => 'Post 2',
                    'slug' => 'post-2',
                    'excerpt' => 'Excerpt 2',
                    'content' => 'Content 2',
                    'user_id' => $user->id,
                    'is_published' => false,
                ],
            ];

            $ids = BlogPost::insertReturning($posts);

            expect($ids)->toHaveCount(2);
            expect($ids[0])->toBeInt();
            expect($ids[1])->toBeInt();
            expect(BlogPost::whereIn('id', $ids)->count())->toBe(2);
        });

        it('can return different columns', function () {
            $user = User::factory()->create();

            $posts = [
                [
                    'title' => 'Post 1',
                    'slug' => 'unique-slug-1',
                    'excerpt' => 'Excerpt',
                    'content' => 'Content',
                    'user_id' => $user->id,
                ],
            ];

            $slugs = BlogPost::insertReturning($posts, 'slug');

            expect($slugs)->toHaveCount(1);
            expect($slugs[0])->toBe('unique-slug-1');
        });

        it('handles JSON columns', function () {
            $user = User::factory()->create();

            $posts = [
                [
                    'title' => 'Post with Tags',
                    'slug' => 'post-with-tags',
                    'excerpt' => 'Excerpt',
                    'content' => 'Content',
                    'user_id' => $user->id,
                    'tags' => ['laravel', 'php'],
                ],
            ];

            $ids = BlogPost::insertReturning($posts);

            expect($ids)->toHaveCount(1);
            
            $post = BlogPost::find($ids[0]);
            expect($post->tags)->toBe(['laravel', 'php']);
        });

        it('returns empty array for empty input', function () {
            $ids = BlogPost::insertReturning([]);
            expect($ids)->toBe([]);
        });

        it('throws for an invalid returningColumn', function () {
            $user = User::factory()->create();

            $records = [[
                'title' => 'Post',
                'slug' => 'throw-test-returning',
                'excerpt' => 'E',
                'content' => 'C',
                'user_id' => $user->id,
            ]];

            expect(fn () => BlogPost::insertReturning($records, 'nonexistent_col'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws when no valid insert columns are provided', function () {
            expect(fn () => BlogPost::insertReturning([['totally_fake' => 'val']]))
                ->toThrow(\InvalidArgumentException::class);
        });

    });

    describe('bulkIncrement', function () {

        it('increments values for multiple records', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(3)->create(['user_id' => $user->id]);

            // Manually set reading_time (bypasses model events)
            DB::table('blog_posts')->whereIn('id', [$posts[0]->id, $posts[1]->id, $posts[2]->id])
                ->update(['reading_time' => 5]);
            
            $posts[0]->refresh();
            $posts[1]->refresh();
            $posts[2]->refresh();

            $increments = [
                $posts[0]->id => 2,
                $posts[1]->id => 3,
                $posts[2]->id => 1,
            ];

            $affected = BlogPost::bulkIncrement($increments, 'reading_time');

            expect($affected)->toBe(3);

            $posts[0]->refresh();
            $posts[1]->refresh();
            $posts[2]->refresh();

            expect($posts[0]->reading_time)->toBe(7);
            expect($posts[1]->reading_time)->toBe(8);
            expect($posts[2]->reading_time)->toBe(6);
        });

        it('handles negative increments (decrement)', function () {
            $user = User::factory()->create();
            $posts = BlogPost::factory(2)->create(['user_id' => $user->id]);

            // Manually set reading_time
            DB::table('blog_posts')->whereIn('id', [$posts[0]->id, $posts[1]->id])
                ->update(['reading_time' => 10]);
            
            $posts[0]->refresh();
            $posts[1]->refresh();

            $increments = [
                $posts[0]->id => -3,
                $posts[1]->id => -5,
            ];

            BlogPost::bulkIncrement($increments, 'reading_time');

            $posts[0]->refresh();
            $posts[1]->refresh();

            expect($posts[0]->reading_time)->toBe(7);
            expect($posts[1]->reading_time)->toBe(5);
        });

        it('returns zero for empty increments', function () {
            $affected = BlogPost::bulkIncrement([], 'reading_time');
            expect($affected)->toBe(0);

            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id]);

            expect(fn () => BlogPost::bulkIncrement([
                $post->id => 1,
            ], 'reading_time', chunkSize: 0))->toThrow(\InvalidArgumentException::class);
        });

        it('processes increments across chunk boundaries', function () {
            $user  = User::factory()->create();
            $posts = BlogPost::factory(5)->create(['user_id' => $user->id]);

            DB::table('blog_posts')
                ->whereIn('id', $posts->pluck('id'))
                ->update(['reading_time' => 10]);

            // chunkSize of 2 with 5 records forces 3 query chunks
            $increments = $posts->mapWithKeys(fn ($p) => [$p->id => 1])->all();

            $affected = BlogPost::bulkIncrement($increments, 'reading_time', chunkSize: 2);

            expect($affected)->toBe(5);
            foreach ($posts as $post) {
                expect($post->fresh()->reading_time)->toBe(11);
            }
        });

        it('throws for an invalid increment column', function () {
            expect(fn () => BlogPost::bulkIncrement([1 => 1], 'nonexistent_col'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('throws for an invalid keyColumn', function () {
            expect(fn () => BlogPost::bulkIncrement([1 => 1], 'reading_time', keyColumn: 'nonexistent_key'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('generates wrapped identifiers in bulkIncrement SQL', function () {
            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id, 'reading_time' => 3]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            BlogPost::bulkIncrement([$post->id => 2], 'reading_time');

            $query = collect(DB::getQueryLog())
                ->pluck('query')
                ->first(fn (string $sql) => str_starts_with($sql, 'UPDATE'));

            DB::disableQueryLog();

            expect($query)->not->toBeNull()
                ->and($query)->toContain('UPDATE "blog_posts"')
                ->and($query)->toContain('SET "reading_time" = CASE')
                ->and($query)->toContain('WHEN "id" = ? THEN "reading_time" + ?');
        });

    });

    describe('processBatch', function () {

        it('processes records in chunks within a transaction', function () {
            $user = User::factory()->create();
            BlogPost::factory(25)->create(['user_id' => $user->id]);

            $processedCount = 0;

            $result = BlogPost::processBatch(10, function ($posts) use (&$processedCount) {
                $processedCount += $posts->count();
            });

            expect($result)->toBeTrue();
            expect($processedCount)->toBe(25);

            expect(fn () => BlogPost::processBatch(0, function () {
                // no-op
            }))->toThrow(\InvalidArgumentException::class);
        });

        it('bubbles exceptions from callback and preserves rollback behavior', function () {
            $user = User::factory()->create();
            BlogPost::factory(15)->create(['user_id' => $user->id]);

            expect(fn () => BlogPost::processBatch(5, function ($posts) {
                if ($posts->count() > 0) {
                    throw new \RuntimeException('Test exception');
                }
            }))->toThrow(\RuntimeException::class, 'Test exception');
        });

    });

    describe('Performance Comparison', function () {

        it('upsert is faster than individual inserts', function () {
            $user = User::factory()->create();

            // Prepare test data
            $posts = [];
            for ($i = 0; $i < 50; $i++) {
                $posts[] = [
                    'title' => "Performance Test Post {$i}",
                    'slug' => "performance-test-post-{$i}",
                    'excerpt' => 'Test excerpt',
                    'content' => 'Test content',
                    'user_id' => $user->id,
                    'is_published' => true,
                ];
            }

            // Test batch upsert
            $startBatch = microtime(true);
            BlogPost::upsertBatch($posts, 'slug');
            $batchTime = microtime(true) - $startBatch;

            // Clean up
            BlogPost::whereIn('slug', array_column($posts, 'slug'))->delete();

            // Test individual inserts
            $startIndividual = microtime(true);
            foreach ($posts as $post) {
                BlogPost::create($post);
            }
            $individualTime = microtime(true) - $startIndividual;

            // Batch should be significantly faster
            expect($batchTime)->toBeLessThan($individualTime);
        });

    });

});

// Test model to verify trait works with different models
class TestModelWithBatchOperations extends \Illuminate\Database\Eloquent\Model
{
    use HasBatchOperations;

    protected $table = 'blog_posts';
    protected $guarded = [];
}

class TestPgsqlModelWithBatchOperations extends \Illuminate\Database\Eloquent\Model
{
    use HasBatchOperations;

    protected $connection = 'pgsql';
    protected $table = 'blog_posts';
    protected $guarded = [];
}

describe('HasBatchOperations Trait', function () {

    it('can be used by any model', function () {
        $user = User::factory()->create();

        $posts = [
            [
                'title' => 'Trait Test',
                'slug' => 'trait-test',
                'excerpt' => 'Test',
                'content' => 'Test',
                'user_id' => $user->id,
            ],
        ];

        $affected = TestModelWithBatchOperations::upsertBatch($posts, 'slug');
        expect($affected)->toBe(1);
    });

});

describe('Column Cache', function () {

    beforeEach(function () {
        BlogPost::clearColumnCache();
    });

    it('clears the entire column cache', function () {
        // Populate cache with two models
        $model = new BlogPost;
        $cacheProperty = (new \ReflectionClass(BlogPost::class))->getProperty('columnCache');
        $cacheProperty->setAccessible(true);

        // Trigger cache population with a non-empty call (ID -1 won't match any row)
        BlogPost::bulkDelete([-1]);
        expect($cacheProperty->getValue())->not->toBeEmpty();

        BlogPost::clearColumnCache();
        expect($cacheProperty->getValue())->toBe([]);
    });

    it('clears cache only for the specified connection', function () {
        $cacheProperty = (new \ReflectionClass(BlogPost::class))->getProperty('columnCache');
        $cacheProperty->setAccessible(true);

        // Seed the static cache directly with two fake connection entries
        $cacheProperty->setValue(null, [
            'pgsql.blog_posts'   => ['id', 'title'],
            'sqlite.blog_posts'  => ['id', 'title'],
        ]);

        BlogPost::clearColumnCache('pgsql');

        $remaining = $cacheProperty->getValue();
        expect($remaining)->toHaveKey('sqlite.blog_posts')
            ->and($remaining)->not->toHaveKey('pgsql.blog_posts');
    });

    it('repopulates cache automatically after clearing', function () {
        $user = User::factory()->create();
        BlogPost::factory()->create(['user_id' => $user->id]);

        // First access populates cache
        BlogPost::bulkDelete([]);

        BlogPost::clearColumnCache();

        // Access after clear should re-query and repopulate
        $user2 = User::factory()->create();
        $post  = BlogPost::factory()->create(['user_id' => $user2->id, 'title' => 'Cache Repop']);

        $affected = BlogPost::bulkUpdate([$post->id => ['title' => 'Updated After Clear']]);

        expect($affected)->toBe(1);
        expect($post->fresh()->title)->toBe('Updated After Clear');
    });

    it('keys cache by runtime connection name when default config differs', function () {
        $cacheProperty = (new \ReflectionClass(TestPgsqlModelWithBatchOperations::class))->getProperty('columnCache');
        $cacheProperty->setAccessible(true);

        TestPgsqlModelWithBatchOperations::bulkDelete([-1]);

        $keys = array_keys($cacheProperty->getValue());
        $runtimeConnectionName = (new TestPgsqlModelWithBatchOperations)->getConnection()->getName();

        expect($keys)->not->toBeEmpty();
        expect(collect($keys)->contains(fn ($key) => str_starts_with($key, $runtimeConnectionName . '.')))->toBeTrue();
    });

});
