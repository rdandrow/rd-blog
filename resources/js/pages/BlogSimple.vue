<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, toRefs } from 'vue';
import type { AppPageProps, BlogPost, Author, SearchFilters } from '@/types';
import SearchSidebar from '@/components/SearchSidebar.vue';
import { dashboard, login, register } from '@/routes';
import { formatDate } from '@/composables/useBlogUtils';
import { useSearchState } from '@/composables/useSearchState';

interface BlogPageProps extends AppPageProps {
  posts: BlogPost[];
  featured_posts: BlogPost[];
  filters?: SearchFilters;
  availableTags?: string[];
  availableAuthors?: Author[];
  canRegister?: boolean;
}

// Get page props with type safety and reactive destructuring
const page = usePage<BlogPageProps>();
const { posts, featured_posts } = toRefs(page.props);

// Computed properties with defaults for optional values
const filters = computed(() => page.props.filters ?? {});
const availableTags = computed(() => page.props.availableTags ?? []);
const availableAuthors = computed(() => page.props.availableAuthors ?? []);
const canRegister = computed(() => page.props.canRegister ?? true);

const regularPosts = computed(() => 
  posts.value.filter(post => !post.is_featured)
);

const pageTitle = computed(() => 'Blog - Latest Articles & Insights');

// Use search state composable
const { isSearchOpen, openSearch, closeSearch } = useSearchState();
</script>

<template>
  <Head :title="pageTitle" />
  
  <div class="min-h-screen bg-background">
    <!-- Header -->
    <header class="border-b border-[#19140035] dark:border-[#3E3E3A]">
      <div class="max-w-7xl mx-auto px-6 py-4">
        <nav class="flex items-center justify-between">
          <div class="flex items-center gap-8">
            <h1 class="text-2xl font-bold">Blog</h1>
            <Link href="/" class="text-sm hover:text-gray-600 dark:hover:text-gray-300">
              Home
            </Link>
          </div>
          <div class="flex items-center gap-4">
            <!-- Search Icon Button -->
            <button
              @click="openSearch"
              class="inline-flex items-center gap-2 rounded-sm border border-transparent px-3 py-1.5 text-sm leading-normal hover:border-[#19140035] dark:hover:border-[#3E3E3A] transition-colors"
              aria-label="Open search"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <span class="hidden sm:inline">Search</span>
            </button>
            
            <Link
              v-if="$page.props.auth.user"
              :href="dashboard()"
              class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
            >
              Dashboard
            </Link>
            <template v-else>
              <Link
                :href="login()"
                class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal hover:border-[#19140035] dark:hover:border-[#3E3E3A]"
              >
                Log in
              </Link>
              <Link
                v-if="canRegister"
                :href="register()"
                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
              >
                Register
              </Link>
            </template>
          </div>
        </nav>
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
            v-memo="[post.id, post.title, post.featured_image]"
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
                  <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2 min-h-[3rem]">
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
            v-memo="[post.id, post.title, post.featured_image]"
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
                  <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-2 min-h-[2.5rem]">
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
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>