<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Clock, User } from 'lucide-vue-next';

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
  post: BlogPost;
  size?: 'default' | 'large' | 'small';
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default',
});

// Computed properties
const cardClasses = computed(() => {
  const baseClasses = 'group block bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-200 dark:border-slate-700';
  
  switch (props.size) {
    case 'large':
      return `${baseClasses} hover:scale-[1.02]`;
    case 'small':
      return `${baseClasses} hover:scale-[1.01]`;
    default:
      return `${baseClasses} hover:scale-[1.015]`;
  }
});

const imageHeight = computed(() => {
  switch (props.size) {
    case 'large':
      return 'h-64';
    case 'small':
      return 'h-40';
    default:
      return 'h-48';
  }
});

const titleSize = computed(() => {
  switch (props.size) {
    case 'large':
      return 'text-2xl';
    case 'small':
      return 'text-lg';
    default:
      return 'text-xl';
  }
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase();
};
</script>

<template>
  <article :class="cardClasses">
    <Link :href="`/blog/${post.slug}`">
      <!-- Featured Image -->
      <div :class="['relative overflow-hidden bg-slate-100 dark:bg-slate-700', imageHeight]">
        <img
          v-if="post.featured_image"
          :src="post.featured_image"
          :alt="post.title"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
        <div 
          v-else
          class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-300 to-slate-500 dark:from-slate-600 dark:to-slate-800"
        >
          <div class="text-white text-4xl font-bold opacity-30">
            {{ post.title.charAt(0) }}
          </div>
        </div>

        <!-- Reading time overlay -->
        <div class="absolute top-3 right-3">
          <div class="flex items-center space-x-1 px-2 py-1 bg-black/70 text-white text-xs rounded-full">
            <Clock class="w-3 h-3" />
            <span>{{ post.reading_time }}m</span>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- Tags -->
        <div class="flex flex-wrap gap-2 mb-3">
          <span
            v-for="tag in post.tags.slice(0, 3)"
            :key="tag"
            class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-full"
          >
            {{ tag }}
          </span>
        </div>

        <!-- Title -->
        <h3 :class="['font-bold text-slate-900 dark:text-white mb-3 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2', titleSize]">
          {{ post.title }}
        </h3>

        <!-- Excerpt -->
        <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4 line-clamp-3">
          {{ post.excerpt }}
        </p>

        <!-- Author and Date -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <!-- Avatar -->
            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center flex-shrink-0">
              <img
                v-if="post.author.avatar"
                :src="post.author.avatar"
                :alt="post.author.name"
                class="w-full h-full rounded-full object-cover"
              />
              <span v-else class="text-white text-xs font-semibold">
                {{ getInitials(post.author.name) }}
              </span>
            </div>

            <!-- Author Info -->
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                {{ post.author.name }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatDate(post.published_at) }}
              </p>
            </div>
          </div>

          <!-- Read More Indicator -->
          <div class="flex items-center text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
            <span class="text-sm font-medium">Read more</span>
            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </div>
    </Link>
  </article>
</template>

<style scoped>
/* Line clamp utilities */
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

/* Smooth hover animations */
article {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus styles for accessibility */
article:focus-within {
  outline: 2px solid var(--color-ring);
  outline-offset: 2px;
}

/* Image loading states */
img {
  transition: opacity 0.3s ease;
}

img[src=""] {
  opacity: 0;
}
</style>