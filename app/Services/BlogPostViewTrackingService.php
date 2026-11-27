<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BlogPostViewTrackingService
{
    private const DEDUPE_MINUTES = 30;

    /**
     * Case-insensitive User-Agent fragments for crawlers, link unfurlers and scripted clients
     * whose requests should not count as reader views. Requests without a User-Agent are kept.
     */
    private const NON_VIEWER_USER_AGENT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|headless|curl\/|wget\/|python-requests|uptime/i';

    private static ?bool $viewsTableExists = null;

    /**
     * Record a view for the post. Tracking is best-effort: a failure here is logged
     * and must never break the public page being rendered.
     */
    public function trackView(BlogPost $post, Request $request): void
    {
        if (! $this->shouldTrack($request) || ! $this->hasViewsTable()) {
            return;
        }

        try {
            $this->recordView($post, $request);
        } catch (\Throwable $exception) {
            Log::warning('Failed to record blog post view; continuing without tracking.', [
                'blog_post_id' => $post->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function shouldTrack(Request $request): bool
    {
        // HEAD requests and browser/Inertia prefetches are not reader views.
        if (! $request->isMethod('GET')) {
            return false;
        }

        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose') ?? $request->header('X-Purpose')));

        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        return ! preg_match(self::NON_VIEWER_USER_AGENT_PATTERN, (string) $request->userAgent());
    }

    private function recordView(BlogPost $post, Request $request): void
    {
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
