<template>
  <Head :title="post.title" />

  <div class="min-h-screen bg-white dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="border-b border-gray-200 dark:border-gray-700">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <div class="flex">
            <div class="flex shrink-0 items-center">
              <Link
                href="/"
                class="text-xl font-bold text-gray-900 dark:text-white"
              >
                Blog
              </Link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <Link
              href="/"
              class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
            >
              Home
            </Link>
            <Link
              href="/blog"
              class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
            >
              All Posts
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Blog Post Content -->
    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Post Header -->
      <header class="mb-8">
        <div class="mb-4">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white sm:text-5xl">
            {{ post.title }}
          </h1>
        </div>
        
        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
          <div class="flex items-center">
            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
              <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                {{ post.author?.name?.charAt(0) || 'A' }}
              </span>
            </div>
            <span class="ml-2">{{ post.author?.name || 'Anonymous' }}</span>
          </div>
          
          <time :datetime="post.published_at">
            {{ formatDate(post.published_at) }}
          </time>
          
          <span v-if="post.reading_time">
            {{ post.reading_time }} min read
          </span>
        </div>

        <!-- Tags -->
        <div v-if="post.tags && post.tags.length" class="mt-4">
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in post.tags"
              :key="tag"
              class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300"
            >
              {{ tag }}
            </span>
          </div>
        </div>

        <!-- Excerpt -->
        <div v-if="post.excerpt" class="mt-6">
          <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
            {{ post.excerpt }}
          </p>
        </div>
      </header>

      <!-- Featured Image -->
      <div v-if="post.featured_image" class="mb-8">
        <img
          :src="post.featured_image"
          :alt="post.title"
          class="w-full rounded-lg shadow-lg"
        />
      </div>

      <!-- Post Content -->
      <div class="prose prose-lg max-w-none dark:prose-invert">
        <MarkdownRender :content="post.content" />
      </div>

      <!-- Footer -->
      <footer class="mt-12 border-t border-gray-200 dark:border-gray-700 pt-8">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Written by {{ post.author?.name || 'Anonymous' }}
            </h3>
          </div>
          
          <div class="flex space-x-4">
            <Link
              href="/blog"
              class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
              ← Back to all posts
            </Link>
          </div>
        </div>
      </footer>
    </article>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MarkdownRender from '@/components/MarkdownRender.vue';

interface Author {
  id: number;
  name: string;
}

interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  featured_image: string | null;
  tags: string[] | null;
  is_featured: boolean;
  is_published: boolean;
  published_at: string;
  reading_time: number | null;
  author: Author | null;
}

interface Props {
  post: BlogPost;
}

defineProps<Props>();

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Markdown is rendered via <MarkdownRender />
</script>

