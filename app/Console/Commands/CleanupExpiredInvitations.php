<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:cleanup
                          {--dry-run : Display what would be cleaned up without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired invitations that have not been accepted (48+ hours old)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find users with expired invitations (48+ hours old, not accepted)
        $expiredUsers = User::whereNotNull('invitation_token')
            ->whereNull('invitation_accepted_at')
            ->whereNotNull('invitation_sent_at')
            ->where('invitation_sent_at', '<=', now()->subHours(48))
            ->get();

        $count = $expiredUsers->count();

        if ($count === 0) {
            $this->info('No expired invitations found.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would clean up {$count} expired invitations:");
            $expiredUsers->each(function ($user) {
                $expiredHours = now()->diffInHours($user->invitation_sent_at);
                $this->line("  - {$user->email} ({$user->role}) - Expired {$expiredHours}h ago");
            });
            return self::SUCCESS;
        }

        // Confirm cleanup
        if (!$this->confirm("Found {$count} expired invitations. Proceed with cleanup?", true)) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // Clean up expired invitations
        $cleaned = 0;
        foreach ($expiredUsers as $user) {
            $user->update([
                'invitation_token' => null,
                'invitation_sent_at' => null,
            ]);
            $cleaned++;

            Log::info('Expired invitation cleaned up', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'invitation_expired_at' => $user->invitation_sent_at?->toISOString(),
            ]);
        }

        $this->info("Successfully cleaned up {$cleaned} expired invitations.");
        
        return self::SUCCESS;
    }
}
