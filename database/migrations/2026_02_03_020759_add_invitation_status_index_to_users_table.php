<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Index for fast token lookup during invitation acceptance
            $table->index('invitation_token', 'idx_invitation_token');
            
            // Composite index for cleanup queries: sent_at first (range condition), then accepted_at
            $table->index(['invitation_sent_at', 'invitation_accepted_at'], 'idx_invitation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_invitation_status');
        });
    }
};
