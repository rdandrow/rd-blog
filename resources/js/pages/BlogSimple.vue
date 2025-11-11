<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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

// Computed properties
const regularPosts = computed(() => 
  posts.filter(post => !post.is_featured)
);

const pageTitle = computed(() => 'Blog - Latest Articles & Insights');

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <Head :title="pageTitle" />
  
  <div class="min-h-screen bg-background">
    <!-- Simple Header -->
    <header class="border-b border-border bg-background">
      <div class="container mx-auto px-4 py-4">
        <h1 class="text-2xl font-bold text-foreground">Blog</h1>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 bg-muted/50">
      <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-foreground mb-4">
          Welcome to Our Blog
        </h2>
        <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
          Discover insights, tutorials, and stories that matter in web development and technology.
        </p>
      </div>
    </section>

    <!-- Featured Posts -->
    <section v-if="featured_posts.length > 0" class="py-16">
      <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold text-foreground mb-8 text-center">
          Featured Articles
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article 
            v-for="post in featured_posts" 
            :key="post.id"
            class="bg-card border border-border rounded-lg p-6 hover:shadow-lg transition-shadow"
          >
            <div class="mb-4">
              <div class="flex flex-wrap gap-2 mb-3">
                <span 
                  v-for="tag in post.tags.slice(0, 2)" 
                  :key="tag"
                  class="px-2 py-1 bg-muted text-muted-foreground text-xs rounded"
                >
                  {{ tag }}
                </span>
              </div>
              
              <h4 class="text-xl font-semibold text-foreground mb-2 line-clamp-2">
                {{ post.title }}
              </h4>
              
              <p class="text-muted-foreground text-sm mb-4 line-clamp-3">
                {{ post.excerpt }}
              </p>
              
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>{{ post.author.name }}</span>
                <span>{{ formatDate(post.published_at) }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Latest Posts -->
    <section class="py-16">
      <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold text-foreground mb-8 text-center">
          Latest Articles
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article 
            v-for="post in regularPosts" 
            :key="post.id"
            class="bg-card border border-border rounded-lg p-6 hover:shadow-lg transition-shadow"
          >
            <div class="mb-4">
              <div class="flex flex-wrap gap-2 mb-3">
                <span 
                  v-for="tag in post.tags.slice(0, 2)" 
                  :key="tag"
                  class="px-2 py-1 bg-muted text-muted-foreground text-xs rounded"
                >
                  {{ tag }}
                </span>
              </div>
              
              <h4 class="text-xl font-semibold text-foreground mb-2 line-clamp-2">
                {{ post.title }}
              </h4>
              
              <p class="text-muted-foreground text-sm mb-4 line-clamp-3">
                {{ post.excerpt }}
              </p>
              
              <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span>{{ post.author.name }}</span>
                <span>{{ formatDate(post.published_at) }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 bg-muted/50">
      <div class="container mx-auto px-4 text-center">
        <h3 class="text-2xl font-bold text-foreground mb-4">
          Stay Updated
        </h3>
        <p class="text-muted-foreground mb-8 max-w-md mx-auto">
          Subscribe to our newsletter for the latest articles and insights.
        </p>
        
        <form class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
          <input
            type="email"
            placeholder="Enter your email"
            class="flex-1 px-4 py-2 border border-border rounded bg-background text-foreground"
          />
          <button
            type="submit"
            class="px-6 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
          >
            Subscribe
          </button>
        </form>
      </div>
    </section>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>