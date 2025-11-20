<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { AppPageProps } from '@/types';
import SearchSidebar from '@/components/SearchSidebar.vue';

// Interface for blog post data
interface BlogPost {
  id: number;
  title: string;
  excerpt: string;
  content: string;
  slug: string;
  featured_image?: string;
  author: {
    id: number;
    name: string;
    avatar?: string;
  };
  published_at: string;
  reading_time: number;
  tags: string[];
  is_featured?: boolean;
}

interface Author {
  id: number;
  name: string;
}

interface Filters {
  search?: string | null;
  tag?: string | null;
  author?: string | null;
}

interface BlogPageProps extends AppPageProps {
  posts: BlogPost[];
  featured_posts: BlogPost[];
  filters?: Filters;
  availableTags?: string[];
  availableAuthors?: Author[];
}

// Get page props with type safety
const page = usePage<BlogPageProps>();
const { posts, featured_posts, filters = {}, availableTags = [], availableAuthors = [] } = page.props;

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

// Search sidebar state
const isSearchOpen = ref(false);

const openSearch = () => {
  isSearchOpen.value = true;
};

const closeSearch = () => {
  isSearchOpen.value = false;
};
</script>

<template>
  <Head :title="pageTitle" />
  
  <div class="min-h-screen bg-background">
    <!-- Simple Header -->
    <header class="border-b border-border bg-background">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-foreground">Blog</h1>
          
          <!-- Search Icon Button -->
          <button
            @click="openSearch"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded border border-transparent hover:border-border transition-colors"
            aria-label="Open search"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span class="hidden sm:inline">Search</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 bg-muted/50">
      <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-foreground mb-4">
          Curiously Testing
        </h2>
        <p class="text-lg text-muted-foreground max-w-2xl mx-auto mb-8">
          Discover insights, tutorials, and stories from my journey as a quality engineer.
        </p>
      </div>
    </section>

    <!-- Featured Posts -->
    <section v-if="featured_posts.length > 0" class="py-16">
      <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold mb-8">
          Featured Posts
        </h3>
        
        <div class="grid lg:grid-cols-2 gap-8">
          <article 
            v-for="post in featured_posts" 
            :key="post.id"
            class="group cursor-pointer"
          >
            <a :href="`/blog/${post.slug}`" class="block">
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="aspect-video relative overflow-hidden">
                  <img 
                    v-if="post.featured_image"
                    :src="post.featured_image" 
                    :alt="post.title"
                    class="w-full h-full object-cover transition-transform group-hover:scale-105"
                    loading="lazy"
                    @error="($event) => ($event.target as HTMLElement).style.display = 'none'"
                  />
                  <div 
                    v-if="!post.featured_image"
                    class="w-full h-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center"
                  >
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                </div>
                <div class="p-6">
                  <div class="flex items-center gap-2 mb-3">
                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                      Featured
                    </span>
                  </div>
                  <h4 class="text-xl font-semibold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ post.title }}
                  </h4>
                  <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                    {{ post.excerpt }}
                  </p>
                  <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>By {{ post.author.name }}</span>
                    <div class="flex items-center gap-4">
                      <span>{{ post.reading_time }} min read</span>
                      <span>{{ formatDate(post.published_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </article>
        </div>
      </div>
    </section>

    <!-- Latest Posts -->
    <section class="py-16">
      <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold mb-8">
          Recent Posts
        </h3>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article 
            v-for="post in regularPosts" 
            :key="post.id"
            class="group cursor-pointer"
          >
            <a :href="`/blog/${post.slug}`" class="block">
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div class="aspect-video relative overflow-hidden">
                  <img 
                    v-if="post.featured_image"
                    :src="post.featured_image" 
                    :alt="post.title"
                    class="w-full h-full object-cover transition-transform group-hover:scale-105"
                    loading="lazy"
                    @error="($event) => ($event.target as HTMLElement).style.display = 'none'"
                  />
                  <div 
                    v-if="!post.featured_image"
                    class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center"
                  >
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                </div>
                <div class="p-4">
                  <h4 class="font-semibold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
                    {{ post.title }}
                  </h4>
                  <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-2">
                    {{ post.excerpt }}
                  </p>
                  <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ post.author.name }}</span>
                    <div class="flex items-center gap-2">
                      <span>{{ post.reading_time }} min</span>
                      <span>{{ formatDate(post.published_at) }}</span>
                    </div>
                  </div>
                  <!-- Tags -->
                  <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-1 mt-3">
                    <span
                      v-for="tag in post.tags.slice(0, 3)"
                      :key="tag"
                      class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>
            </a>
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

    <!-- Search Sidebar -->
    <SearchSidebar
      :is-open="isSearchOpen"
      :filters="filters"
      :available-tags="availableTags"
      :available-authors="availableAuthors"
      current-route="/blog"
      @close="closeSearch"
    />
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