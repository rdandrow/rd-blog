<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding dashboard metrics data...');

        $this->seedUserActivity();
        $this->seedTwoFactorAdoption();
        $this->seedInvitationFunnel();
        $this->seedFollowerTimestamps();
        $this->seedBlogPostViews();

        $this->command->info('✅ Dashboard metrics seeding completed!');
    }

    /**
     * Set last_active_at on users to populate User Trends metrics.
     *
     * Distribution (~17 users):
     *   - 25% DAU  — active within last 24 hours
     *   - 20% WAU  — active within last 7 days (but not DAU)
     *   - 20% MAU  — active within last 30 days (but not WAU)
     *   - 15% Stale — active 31–90 days ago (counted as inactive)
     *   - 20% NULL  — never set (counted as inactive)
     */
    private function seedUserActivity(): void
    {
        $this->command->info('  Setting user last_active_at timestamps...');

        $users = User::all()->shuffle();
        $total = $users->count();

        $dauCount = max(1, (int) round($total * 0.25));
        $wauCount = max(1, (int) round($total * 0.20));
        $mauCount = max(1, (int) round($total * 0.20));
        $staleCount = max(1, (int) round($total * 0.15));

        $offset = 0;

        // DAU — within last 24 hours
        foreach ($users->slice($offset, $dauCount) as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_active_at' => now()->subMinutes(fake()->numberBetween(1, 1439))]);
        }
        $offset += $dauCount;

        // WAU — 25 hours to 7 days ago
        foreach ($users->slice($offset, $wauCount) as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_active_at' => now()->subHours(fake()->numberBetween(25, 167))]);
        }
        $offset += $wauCount;

        // MAU — 8 to 30 days ago
        foreach ($users->slice($offset, $mauCount) as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_active_at' => now()->subDays(fake()->numberBetween(8, 30))]);
        }
        $offset += $mauCount;

        // Stale — 31 to 90 days ago (contributes to total_inactive_users)
        foreach ($users->slice($offset, $staleCount) as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_active_at' => now()->subDays(fake()->numberBetween(31, 90))]);
        }

        // Remaining users keep last_active_at = NULL (never active, also inactive)
    }

    /**
     * Enable 2FA for ~45% of users to produce a meaningful adoption rate.
     */
    private function seedTwoFactorAdoption(): void
    {
        $this->command->info('  Setting 2FA adoption...');

        $users = User::all()->shuffle();
        $twoFactorCount = max(1, (int) round($users->count() * 0.45));

        foreach ($users->take($twoFactorCount) as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'two_factor_secret' => Str::random(32),
                    'two_factor_recovery_codes' => json_encode(
                        array_map(
                            fn () => Str::random(10).'-'.Str::random(10),
                            range(1, 8)
                        )
                    ),
                    'two_factor_confirmed_at' => now()->subDays(fake()->numberBetween(1, 180)),
                ]);
        }
    }

    /**
     * Create invitation funnel data:
     *   - Pending  (4): invitation_token set, sent < 48h ago, not accepted
     *   - Accepted (5): update existing members with accepted invitation data
     *   - Expired  (3): invitation_token set, sent > 48h ago, not accepted
     */
    private function seedInvitationFunnel(): void
    {
        $this->command->info('  Creating invitation funnel data...');

        // Pending invitations — sent within last 48 hours
        for ($i = 0; $i < 4; $i++) {
            User::factory()->create([
                'role' => 'member',
                'email_verified_at' => null,
                'invitation_token' => Str::random(32),
                'invitation_sent_at' => now()->subHours(fake()->numberBetween(1, 47)),
                'invitation_accepted_at' => null,
            ]);
        }

        // Accepted invitations — mark some existing members as having accepted an invite
        User::where('role', 'member')
            ->whereNull('invitation_token')
            ->limit(5)
            ->get()
            ->each(function (User $user): void {
                $sentAt = now()->subDays(fake()->numberBetween(5, 60));
                $acceptedAt = $sentAt->copy()->addDays(fake()->numberBetween(1, 3));
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'invitation_token' => Str::random(32),
                        'invitation_sent_at' => $sentAt,
                        'invitation_accepted_at' => $acceptedAt,
                    ]);
            });

        // Expired invitations — sent > 48 hours ago, never accepted
        for ($i = 0; $i < 3; $i++) {
            User::factory()->create([
                'role' => 'member',
                'email_verified_at' => null,
                'invitation_token' => Str::random(32),
                'invitation_sent_at' => now()->subDays(fake()->numberBetween(3, 14)),
                'invitation_accepted_at' => null,
            ]);
        }
    }

    /**
     * Spread user_follows created_at timestamps across the last 30 days
     * so the 30-Day Follower Growth chart shows variance rather than a single spike.
     */
    private function seedFollowerTimestamps(): void
    {
        $this->command->info('  Spreading follower timestamps over last 30 days...');

        $follows = DB::table('user_follows')->get();
        $start = now()->subDays(29);

        foreach ($follows as $follow) {
            DB::table('user_follows')
                ->where('id', $follow->id)
                ->update([
                    'created_at' => fake()->dateTimeBetween($start, now()),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Seed blog_post_views for all published posts spread across the last 30 days.
     * Produces both the total view count cards and the 30-Day View Trend chart data.
     */
    private function seedBlogPostViews(): void
    {
        $this->command->info('  Creating blog post views...');

        $publishedPosts = BlogPost::published()->get();
        $userIds = User::pluck('id')->all();
        $adminIdLookup = array_fill_keys(
            User::whereIn('role', ['master_admin', 'admin'])->pluck('id')->all(),
            true
        );
        $windowStart = now()->subDays(29)->startOfDay();
        $pendingRecords = [];
        $insertedCount = 0;

        foreach ($publishedPosts as $post) {
            // Featured posts and posts by admins/master admin get more views
            $isHighTraffic = $post->is_featured || isset($adminIdLookup[$post->user_id]);
            $viewCount = $isHighTraffic
                ? fake()->numberBetween(80, 200)
                : fake()->numberBetween(20, 80);

            // Views cannot predate publication
            $postPublishedAt = $post->published_at instanceof \Carbon\CarbonInterface
                ? $post->published_at
                : \Carbon\Carbon::parse($post->published_at);
            $viewStart = $postPublishedAt->gt($windowStart) ? $postPublishedAt : $windowStart;

            for ($i = 0; $i < $viewCount; $i++) {
                $viewedAt = fake()->dateTimeBetween($viewStart, now());

                // 55% authenticated, 45% anonymous
                $isAuthenticated = fake()->boolean(55);
                $userId = $isAuthenticated ? $userIds[array_rand($userIds)] : null;

                $pendingRecords[] = [
                    'blog_post_id' => $post->id,
                    'user_id' => $userId,
                    'session_id' => $isAuthenticated ? null : Str::random(40),
                    'ip_hash' => hash('sha256', fake()->ipv4()),
                    'user_agent_hash' => hash('sha256', fake()->userAgent()),
                    'viewed_at' => $viewedAt,
                    'created_at' => $viewedAt,
                    'updated_at' => $viewedAt,
                ];

                if (count($pendingRecords) === 500) {
                    DB::table('blog_post_views')->insert($pendingRecords);
                    $insertedCount += count($pendingRecords);
                    $pendingRecords = [];
                }
            }
        }

        if ($pendingRecords !== []) {
            DB::table('blog_post_views')->insert($pendingRecords);
            $insertedCount += count($pendingRecords);
        }

        $this->command->info('  Inserted '.number_format($insertedCount).' blog post views across '.$publishedPosts->count().' posts.');
    }
}
