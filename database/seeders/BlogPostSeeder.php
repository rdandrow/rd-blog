<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample published blog posts
        \App\Models\BlogPost::factory(15)->published()->create();
        
        // Create some featured posts
        \App\Models\BlogPost::factory(3)->featured()->published()->create();
        
        // Create some draft posts
        \App\Models\BlogPost::factory(5)->draft()->create();
    }
}
