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
        Schema::table('comments', function (Blueprint $table) {
            // Add missing index on user_id foreign key
            // This index improves performance for:
            // - Finding all comments by a user
            // - Cascading deletes when a user is removed
            // - JOIN operations between users and comments
            $table->index('user_id', 'comments_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_user_id_index');
        });
    }
};
