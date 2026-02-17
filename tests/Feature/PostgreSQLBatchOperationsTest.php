<?php

declare(strict_types=1);

use App\Database\Concerns\HasBatchOperations;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * PostgreSQL Batch Operations Tests
 *
 * These tests verify the batch operation optimizations that leverage
 * PostgreSQL-specific features for improved performance.
 */

describe('PostgreSQL Batch Operations', function () {

    beforeEach(function () {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('These tests require PostgreSQL');
        }
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

        it('handles multiple unique columns', function () {
            $user = User::factory()->create();
            $post = BlogPost::factory()->create(['user_id' => $user->id]);

            $comments = [
                [
                    'blog_post_id' => $post->id,
                    'user_id' => $user->id,
                    'content' => 'First comment',
                ],
            ];

            // Skip this test - Comments table doesn't have a unique constraint on (blog_post_id, user_id)
            // In production, you would add: UNIQUE INDEX comments_blog_post_user_unique ON comments(blog_post_id, user_id)
            $this->markTestSkipped('Requires unique constraint on (blog_post_id, user_id)');

            $affected = Comment::upsertBatch($comments, ['blog_post_id', 'user_id']);

            expect($affected)->toBeGreaterThanOrEqual(1);
            expect(Comment::where('content', 'First comment')->exists())->toBeTrue();
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

        it('returns zero for empty updates', function () {
            $affected = BlogPost::bulkUpdate([]);
            expect($affected)->toBe(0);
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
        });

        it('rolls back on exception', function () {
            $user = User::factory()->create();
            BlogPost::factory(15)->create(['user_id' => $user->id]);

            $result = BlogPost::processBatch(5, function ($posts) {
                if ($posts->count() > 0) {
                    throw new \Exception('Test exception');
                }
            });

            expect($result)->toBeFalse();
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
            
            $this->comment("Batch upsert: {$batchTime}s, Individual inserts: {$individualTime}s");
            $this->info("Batch is " . round($individualTime / $batchTime, 2) . "x faster");
        })->skip(env('SKIP_PERFORMANCE_TESTS', true), 'Performance test - enable with SKIP_PERFORMANCE_TESTS=false');

    });

});

// Test model to verify trait works with different models
class TestModelWithBatchOperations extends \Illuminate\Database\Eloquent\Model
{
    use HasBatchOperations;

    protected $table = 'blog_posts';
    protected $guarded = [];
}

describe('HasBatchOperations Trait', function () {

    it('can be used by any model', function () {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL');
        }

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
