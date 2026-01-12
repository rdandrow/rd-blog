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
            // Add indexes for performance optimization
            $table->index('blog_post_id', 'comments_blog_post_id_index');
            $table->index('parent_id', 'comments_parent_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_blog_post_id_index');
            $table->dropIndex('comments_parent_id_index');
        });
    }
};
