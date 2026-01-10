<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing role column
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Re-add role column with master_admin option
            $table->enum('role', ['master_admin', 'admin', 'member'])->default('member')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, migrate any master_admin users to admin role
        DB::table('users')
            ->where('role', 'master_admin')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Restore original enum values
            $table->enum('role', ['admin', 'member'])->default('member')->after('email');
        });
    }
};
