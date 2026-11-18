<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BlogPostViewTrackingService
{
    private const DEDUPE_MINUTES = 30;

    private static ?bool $viewsTableExists = null;

    public function trackView(BlogPost $post, Request $request): void
    {
        if (! $this->hasViewsTable()) {
            return;
        }

        // Hashed like the other identifiers so a raw session id is never persisted.
        $sessionHash = $this->hashValue($request->session()->getId());
        $ipHash = $this->hashValue($request->ip());
        $userAgentHash = $this->hashValue($request->userAgent());

        $hasSession = $sessionHash !== null;
        $hasFingerprint = $ipHash !== null && $userAgentHash !== null;

        // Without any identifier the dedupe query would have no predicates and match
        // every recent view of the post, so only dedupe when there is something to match on.
        if (($hasSession || $hasFingerprint) && $this->hasRecentView($post, $sessionHash, $ipHash, $userAgentHash)) {
            return;
        }

        BlogPostView::create([
            'blog_post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'session_id' => $sessionHash,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'viewed_at' => now(),
        ]);
    }

    private function hasRecentView(BlogPost $post, ?string $sessionHash, ?string $ipHash, ?string $userAgentHash): bool
    {
        return BlogPostView::query()
            ->where('blog_post_id', $post->id)
            ->where('viewed_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->where(function ($query) use ($sessionHash, $ipHash, $userAgentHash) {
                $hasSession = $sessionHash !== null;
                $hasFingerprint = $ipHash !== null && $userAgentHash !== null;

                if ($hasSession) {
                    $query->where('session_id', $sessionHash);
                }

                if ($hasFingerprint) {
                    $fingerprintMatcher = fn ($fingerprintQuery) => $fingerprintQuery
                        ->where('ip_hash', $ipHash)
                        ->where('user_agent_hash', $userAgentHash);

                    if ($hasSession) {
                        $query->orWhere($fingerprintMatcher);
                    } else {
                        $query->where($fingerprintMatcher);
                    }
                }
            })
            ->exists();
    }

    private function hashValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, config('app.key'));
    }

    private function hasViewsTable(): bool
    {
        if (self::$viewsTableExists !== null) {
            return self::$viewsTableExists;
        }

        self::$viewsTableExists = Schema::hasTable('blog_post_views');

        return self::$viewsTableExists;
    }
}