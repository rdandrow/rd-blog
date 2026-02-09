<?php

/**
 * Cleanup Expired Invitations Command Test Suite
 *
 * Tests the automated cleanup of expired invitation tokens.
 */

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

describe('Cleanup Command Execution', function () {
    it('cleans up expired invitations with --force flag without prompting', function () {
        // Create users with expired invitations (> 48 hours old)
        $expiredUser1 = User::factory()->create([
            'invitation_token' => hash('sha256', 'expired_token_1'),
            'invitation_sent_at' => now()->subHours(49),
            'invitation_accepted_at' => null,
        ]);

        $expiredUser2 = User::factory()->create([
            'invitation_token' => hash('sha256', 'expired_token_2'),
            'invitation_sent_at' => now()->subHours(50),
            'invitation_accepted_at' => null,
        ]);

        // Run command with --force flag (should not prompt)
        $exitCode = Artisan::call('invitations:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and($expiredUser1->fresh()->invitation_token)->toBeNull()
            ->and($expiredUser1->fresh()->invitation_sent_at)->toBeNull()
            ->and($expiredUser2->fresh()->invitation_token)->toBeNull()
            ->and($expiredUser2->fresh()->invitation_sent_at)->toBeNull();
    })->group('cleanup-command', 'force-flag');

    it('skips non-expired invitations', function () {
        // Create user with recent invitation (< 48 hours)
        $recentUser = User::factory()->create([
            'invitation_token' => hash('sha256', 'recent_token'),
            'invitation_sent_at' => now()->subHours(24),
            'invitation_accepted_at' => null,
        ]);

        $exitCode = Artisan::call('invitations:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and($recentUser->fresh()->invitation_token)->toBe(hash('sha256', 'recent_token'))
            ->and($recentUser->fresh()->invitation_sent_at)->not->toBeNull();
    })->group('cleanup-command', 'filtering');

    it('skips accepted invitations', function () {
        // Create user with expired but accepted invitation
        $acceptedUser = User::factory()->create([
            'invitation_token' => null, // Token cleared on acceptance
            'invitation_sent_at' => now()->subHours(50),
            'invitation_accepted_at' => now()->subHours(48),
        ]);

        $exitCode = Artisan::call('invitations:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and($acceptedUser->fresh()->invitation_sent_at)->not->toBeNull()
            ->and($acceptedUser->fresh()->invitation_accepted_at)->not->toBeNull();
    })->group('cleanup-command', 'filtering');

    it('shows dry-run output without making changes', function () {
        $expiredUser = User::factory()->create([
            'email' => 'expired@test.com',
            'role' => 'member',
            'invitation_token' => hash('sha256', 'expired_token'),
            'invitation_sent_at' => now()->subHours(50),
            'invitation_accepted_at' => null,
        ]);

        $exitCode = Artisan::call('invitations:cleanup', ['--dry-run' => true]);

        expect($exitCode)->toBe(0)
            ->and($expiredUser->fresh()->invitation_token)->toBe(hash('sha256', 'expired_token'))
            ->and($expiredUser->fresh()->invitation_sent_at)->not->toBeNull();

        $output = Artisan::output();
        expect($output)->toContain('DRY RUN')
            ->and($output)->toContain('expired@test.com');
    })->group('cleanup-command', 'dry-run');

    it('handles no expired invitations gracefully', function () {
        // Create only recent invitations
        User::factory()->create([
            'invitation_token' => hash('sha256', 'recent_token'),
            'invitation_sent_at' => now()->subHours(24),
            'invitation_accepted_at' => null,
        ]);

        $exitCode = Artisan::call('invitations:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0);
        
        $output = Artisan::output();
        expect($output)->toContain('No expired invitations found');
    })->group('cleanup-command', 'edge-cases');

    it('cleans up exactly at 48-hour threshold', function () {
        // Create user with invitation exactly 48 hours old (should be cleaned)
        $exactlyExpired = User::factory()->create([
            'invitation_token' => hash('sha256', 'exact_token'),
            'invitation_sent_at' => now()->subHours(48),
            'invitation_accepted_at' => null,
        ]);

        $exitCode = Artisan::call('invitations:cleanup', ['--force' => true]);

        expect($exitCode)->toBe(0)
            ->and($exactlyExpired->fresh()->invitation_token)->toBeNull()
            ->and($exactlyExpired->fresh()->invitation_sent_at)->toBeNull();
    })->group('cleanup-command', 'edge-cases', 'threshold');
});
