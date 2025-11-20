<script setup lang="ts">
import { ref, computed } from 'vue';
import BlogCard from './BlogCard.vue';
import { ChevronDown, Loader2 } from 'lucide-vue-next';

interface BlogPost {
  id: number;
  title: string;
  excerpt: string;
  slug: string;
  featured_image?: string;
  author: {
    name: string;
    avatar?: string;
  };
  published_at: string;
  reading_time: number;
  tags: string[];
}

interface Props {
  posts: BlogPost[];
  loading?: boolean;
  hasMore?: boolean;
}

interface Emits {
  (e: 'load-more'): void;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  hasMore: true,
});

const emit = defineEmits<Emits>();

// Reactive state
const displayCount = ref(6);
const isLoadingMore = ref(false);

// Computed properties
const displayedPosts = computed(() => 
  props.posts.slice(0, displayCount.value)
);

const hasMorePosts = computed(() => 
  displayCount.value < props.posts.length || props.hasMore
);

// Methods
const loadMore = async () => {
  if (isLoadingMore.value || props.loading) return;
  
  isLoadingMore.value = true;
  
  try {
    // If we have more posts in the current array, show them
    if (displayCount.value < props.posts.length) {
      displayCount.value = Math.min(displayCount.value + 6, props.posts.length);
    } else {
      // Emit event to load more posts from the server
      emit('load-more');
    }
  } finally {
    // Simulate loading delay for better UX
    setTimeout(() => {
      isLoadingMore.value = false;
    }, 500);
  }
};

// Filter functionality (for future enhancement)
const selectedTag = ref<string | null>(null);
const allTags = computed(() => {
  const tags = new Set<string>();
  props.posts.forEach(post => {
    post.tags.forEach(tag => tags.add(tag));
  });
  return Array.from(tags).sort();
});
</script>

<template>
  <div class="space-y-8">
    <!-- Filter Bar (optional - for future enhancement) -->
    <div class="flex flex-wrap items-center gap-4 pb-6 border-b border-slate-200 dark:border-slate-700">
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedTag === null
              ? 'bg-blue-600 text-white'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
          ]"
          @click="selectedTag = null"
        >
          All Posts
        </button>
        
        <button
          v-for="tag in allTags.slice(0, 6)"
          :key="tag"
          type="button"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
            selectedTag === tag
              ? 'bg-blue-600 text-white'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
          ]"
          @click="selectedTag = selectedTag === tag ? null : tag"
        >
          {{ tag }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="props.loading && displayedPosts.length === 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div 
        v-for="i in 6" 
        :key="i"
        class="animate-pulse"
      >
        <div class="bg-slate-200 dark:bg-slate-700 rounded-xl h-48 mb-4"></div>
        <div class="space-y-3">
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4"></div>
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
          <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-5/6"></div>
        </div>
      </div>
    </div>

    <!-- Posts Grid -->
    <div 
      v-else-if="displayedPosts.length > 0"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
    >
      <BlogCard
        v-for="post in displayedPosts"
        :key="post.id"
        :post="post"
        size="default"
      />
    </div>

    <!-- Empty State -->
    <div 
      v-else
      class="text-center py-16"
    >
      <div class="w-24 h-24 mx-auto mb-6 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
        <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">
        No articles found
      </h3>
      <p class="text-slate-600 dark:text-slate-400">
        We couldn't find any articles matching your criteria. Check back later for new content!
      </p>
    </div>

    <!-- Load More Button -->
    <div 
      v-if="hasMorePosts && displayedPosts.length > 0"
      class="text-center pt-8"
    >
      <button
        type="button"
        :disabled="isLoadingMore || props.loading"
        @click="loadMore"
        class="inline-flex items-center px-8 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-semibold rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        <Loader2 
          v-if="isLoadingMore || props.loading"
          class="w-5 h-5 mr-2 animate-spin"
        />
        <ChevronDown 
          v-else
          class="w-5 h-5 mr-2"
        />
        {{ isLoadingMore || props.loading ? 'Loading...' : 'Load More Articles' }}
      </button>
    </div>

    <!-- End of Posts Indicator -->
    <div 
      v-else-if="displayedPosts.length > 0 && !hasMorePosts"
      class="text-center pt-8 border-t border-slate-200 dark:border-slate-700"
    >
      <p class="text-slate-500 dark:text-slate-400">
        You've reached the end of our articles. 
        <br>
        <span class="text-sm">Subscribe to our newsletter to get notified when we publish new content!</span>
      </p>
    </div>
  </div>
</template>

<style scoped>
/* Staggered animation delays - using shared fadeInUp from app.css */
.grid > * {
  animation: fadeInUp 0.6s ease-out both;
}

.grid > *:nth-child(1) { animation-delay: 0.1s; }
.grid > *:nth-child(2) { animation-delay: 0.2s; }
.grid > *:nth-child(3) { animation-delay: 0.3s; }
.grid > *:nth-child(4) { animation-delay: 0.4s; }
.grid > *:nth-child(5) { animation-delay: 0.5s; }
.grid > *:nth-child(6) { animation-delay: 0.6s; }
</style>
