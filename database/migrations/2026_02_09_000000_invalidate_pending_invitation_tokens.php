<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('invitation_token')
            ->whereNull('invitation_accepted_at')
            ->update([
                'invitation_token' => null,
                'invitation_sent_at' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: invalidated tokens cannot be restored.
    }
};
