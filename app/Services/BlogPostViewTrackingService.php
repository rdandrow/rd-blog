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

        $sessionId = $request->session()->getId();
        $ipHash = $this->hashValue($request->ip());
        $userAgentHash = $this->hashValue($request->userAgent());

        $recentViewQuery = BlogPostView::query()
            ->where('blog_post_id', $post->id)
            ->where('viewed_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->where(function ($query) use ($sessionId, $ipHash, $userAgentHash) {
                $hasSession = $sessionId !== null && $sessionId !== '';
                $hasFingerprint = $ipHash !== null && $userAgentHash !== null;

                if ($hasSession) {
                    $query->where('session_id', $sessionId);
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
            });

        if ($recentViewQuery->exists()) {
            return;
        }

        BlogPostView::create([
            'blog_post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'session_id' => $sessionId,
            'ip_hash' => $ipHash,
            'user_agent_hash' => $userAgentHash,
            'viewed_at' => now(),
        ]);
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