<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BlogLayout from '@/layouts/BlogLayout.vue';
import BlogHero from '@/components/blog/BlogHero.vue';
import BlogGrid from '@/components/blog/BlogGrid.vue';
import BlogFeatured from '@/components/blog/BlogFeatured.vue';
import type { AppPageProps } from '@/types';

// Interface for blog post data
interface BlogPost {
  id: number;
  title: string;
  excerpt: string;
  content: string;
  slug: string;
  featured_image?: string;
  author: {
    name: string;
    avatar?: string;
  };
  published_at: string;
  reading_time: number;
  tags: string[];
  is_featured?: boolean;
}

interface BlogPageProps extends AppPageProps {
  posts: BlogPost[];
  featured_posts: BlogPost[];
}

// Get page props with type safety
const page = usePage<BlogPageProps>();
const { posts, featured_posts } = page.props;

// Computed properties following Vue 3 composition API best practices
const regularPosts = computed(() => 
  posts.filter(post => !post.is_featured)
);

const pageTitle = computed(() => 'Blog - Latest Articles & Insights');
const pageDescription = computed(() => 
  'Discover the latest articles, tutorials, and insights on web development, programming, and technology trends.'
);
</script>

<template>
  <Head :title="pageTitle" />
  
  <BlogLayout>
    <!-- Hero Section -->
    <BlogHero 
      title="Welcome to Our Blog"
      subtitle="Discover insights, tutorials, and stories that matter"
      :description="pageDescription"
    />

    <!-- Featured Posts Section -->
    <section 
      v-if="featured_posts.length > 0"
      class="py-16 bg-muted/50"
      aria-labelledby="featured-heading"
    >
      <div class="container mx-auto px-4 max-w-6xl">
        <div class="w-full">
          <header class="text-center mb-12">
            <h2 
              id="featured-heading"
              class="text-3xl font-bold text-foreground mb-4"
            >
              Featured Articles
            </h2>
            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
              Hand-picked articles that showcase the best of our content
            </p>
          </header>
          
          <BlogFeatured :posts="featured_posts" />
        </div>
      </div>
    </section>

    <!-- Latest Posts Section -->
    <section 
      class="py-16"
      aria-labelledby="latest-heading"
    >
      <div class="container mx-auto px-4 max-w-6xl">
        <div class="w-full">
          <header class="text-center mb-12">
            <h2 
              id="latest-heading"
              class="text-3xl font-bold text-foreground mb-4"
            >
              Latest Articles
            </h2>
            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
              Stay up to date with our newest content and insights
            </p>
          </header>
          
          <BlogGrid 
            :posts="regularPosts" 
            :loading="false"
            @load-more="() => {}"
          />
        </div>
      </div>
    </section>

    <!-- Newsletter Signup Section -->
    <section 
      class="py-20 bg-card border-t border-border"
      aria-labelledby="newsletter-heading"
    >
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
          <h2 
            id="newsletter-heading"
            class="text-3xl md:text-4xl font-bold text-foreground mb-6"
          >
            Stay in the Loop
          </h2>
          <p class="text-xl text-muted-foreground mb-8 max-w-2xl mx-auto">
            Subscribe to our newsletter and never miss our latest articles, tutorials, and insights.
          </p>
          
          <form 
            class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto"
            @submit.prevent="() => {}"
          >
            <label class="sr-only" for="email-newsletter">
              Email address
            </label>
            <input
              id="email-newsletter"
              type="email"
              placeholder="Enter your email"
              required
              class="flex-1 px-6 py-3 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <button
              type="submit"
              class="px-8 py-3 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-ring"
            >
              Subscribe
            </button>
          </form>
        </div>
      </div>
    </section>
  </BlogLayout>
</template>

<style scoped>
/* Component-scoped styles following Vue.js best practices */

/* Smooth transitions for interactive elements */
button {
  transition: all 0.2s ease-in-out;
}

/* Focus styles for accessibility */
input:focus,
button:focus {
  outline-offset: 2px;
}
</style>

<!-- Animations moved to global app.css for reusability -->
