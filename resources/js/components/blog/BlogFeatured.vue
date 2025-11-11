<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BlogCard from './BlogCard.vue';

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
}

const props = defineProps<Props>();

// Computed properties
const primaryFeatured = computed(() => props.posts[0]);
const secondaryFeatured = computed(() => props.posts.slice(1, 3));

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Primary Featured Post -->
    <article 
      v-if="primaryFeatured"
      class="lg:col-span-1 group cursor-pointer"
    >
      <Link :href="`/blog/${primaryFeatured.slug}`" class="block">
        <div class="relative overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800 aspect-[4/3] mb-6 group-hover:scale-[1.02] transition-transform duration-300">
          <img
            v-if="primaryFeatured.featured_image"
            :src="primaryFeatured.featured_image"
            :alt="primaryFeatured.title"
            class="w-full h-full object-cover"
          />
          <div 
            v-else
            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600"
          >
            <div class="text-white text-6xl font-bold opacity-20">
              {{ primaryFeatured.title.charAt(0) }}
            </div>
          </div>
          
          <!-- Featured Badge -->
          <div class="absolute top-4 left-4">
            <span class="px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded-full">
              Featured
            </span>
          </div>
        </div>

        <div class="space-y-4">
          <!-- Tags -->
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in primaryFeatured.tags"
              :key="tag"
              class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm rounded-full"
            >
              {{ tag }}
            </span>
          </div>

          <!-- Title -->
          <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
            {{ primaryFeatured.title }}
          </h3>

          <!-- Excerpt -->
          <p class="text-slate-600 dark:text-slate-400 text-lg leading-relaxed line-clamp-3">
            {{ primaryFeatured.excerpt }}
          </p>

          <!-- Meta -->
          <div class="flex items-center justify-between pt-4">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center">
                <span class="text-white font-semibold">
                  {{ primaryFeatured.author.name.charAt(0) }}
                </span>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">
                  {{ primaryFeatured.author.name }}
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  {{ formatDate(primaryFeatured.published_at) }}
                </p>
              </div>
            </div>
            <div class="text-sm text-slate-500 dark:text-slate-400">
              {{ primaryFeatured.reading_time }} min read
            </div>
          </div>
        </div>
      </Link>
    </article>

    <!-- Secondary Featured Posts -->
    <div class="lg:col-span-1 space-y-8">
      <article
        v-for="post in secondaryFeatured"
        :key="post.id"
        class="group cursor-pointer"
      >
        <Link :href="`/blog/${post.slug}`" class="flex gap-6">
          <!-- Image -->
          <div class="flex-shrink-0 w-32 h-24 relative overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800 group-hover:scale-[1.02] transition-transform duration-300">
            <img
              v-if="post.featured_image"
              :src="post.featured_image"
              :alt="post.title"
              class="w-full h-full object-cover"
            />
            <div 
              v-else
              class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-400 to-slate-600"
            >
              <div class="text-white text-2xl font-bold opacity-30">
                {{ post.title.charAt(0) }}
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 space-y-2">
            <!-- Tags -->
            <div class="flex flex-wrap gap-1">
              <span
                v-for="tag in post.tags.slice(0, 2)"
                :key="tag"
                class="px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-xs rounded-full"
              >
                {{ tag }}
              </span>
            </div>

            <!-- Title -->
            <h4 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
              {{ post.title }}
            </h4>

            <!-- Excerpt -->
            <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-2">
              {{ post.excerpt }}
            </p>

            <!-- Meta -->
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2">
              <span>{{ post.author.name }}</span>
              <span>{{ post.reading_time }} min read</span>
            </div>
          </div>
        </Link>
      </article>
    </div>
  </div>
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

/* Smooth transitions */
article {
  transition: all 0.3s ease;
}

/* Hover effects */
article:hover {
  transform: translateY(-2px);
}
</style>