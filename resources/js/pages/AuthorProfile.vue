<template>
  <Head :title="`${author.name} - Author Profile`" />

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

    <!-- Author Profile -->
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Author Header -->
      <div class="mb-12">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-6">
              <!-- Avatar -->
              <div class="h-24 w-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shrink-0">
                <span class="text-3xl font-bold text-white">
                  {{ author.name.charAt(0).toUpperCase() }}
                </span>
              </div>

              <!-- Name and Stats -->
              <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                  {{ author.name }}
                </h1>
                
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <span>{{ author.followers_count }} {{ author.followers_count === 1 ? 'Follower' : 'Followers' }}</span>
                  <span>·</span>
                  <span>{{ author.following_count }} Following</span>
                  <span>·</span>
                  <span>{{ author.posts_count }} {{ author.posts_count === 1 ? 'Post' : 'Posts' }}</span>
                </div>
              </div>
            </div>

            <!-- Bio -->
            <div v-if="author.bio" class="mt-6">
              <p class="text-gray-700 dark:text-gray-300 text-lg leading-relaxed whitespace-pre-line">
                {{ author.bio }}
              </p>
            </div>

            <!-- Website -->
            <div v-if="author.website" class="mt-4">
              <a
                :href="author.website"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                {{ author.website.replace(/^https?:\/\//, '') }}
              </a>
            </div>
          </div>

          <!-- Follow Button -->
          <button
            v-if="$page.props.auth.user"
            @click="toggleFollow"
            class="rounded-full px-6 py-2.5 text-sm font-medium transition-colors shrink-0"
            :class="is_following 
              ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' 
              : 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600'"
          >
            {{ is_following ? 'Following' : 'Follow' }}
          </button>
        </div>
      </div>

      <!-- Posts Section -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
          Recent Articles
        </h2>

        <div v-if="posts && posts.length > 0" class="space-y-6">
          <article
            v-for="post in posts"
            :key="post.id"
            class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-b-0"
          >
            <Link :href="`/blog/${post.slug}`" class="group">
              <div class="flex gap-4">
                <!-- Featured Image -->
                <div v-if="post.featured_image" class="w-32 h-24 shrink-0">
                  <img
                    :src="post.featured_image"
                    :alt="post.title"
                    class="w-full h-full object-cover rounded-lg"
                  />
                </div>

                <!-- Post Content -->
                <div class="flex-1 min-w-0">
                  <h3 class="text-xl font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 mb-2">
                    {{ post.title }}
                  </h3>
                  
                  <p v-if="post.excerpt" class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mb-2">
                    {{ post.excerpt }}
                  </p>
                  
                  <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-500">
                    <time :datetime="post.published_at">
                      {{ formatDate(post.published_at) }}
                    </time>
                    <span v-if="post.reading_time">·</span>
                    <span v-if="post.reading_time">{{ post.reading_time }} min read</span>
                  </div>
                </div>
              </div>
            </Link>
          </article>
        </div>

        <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
          <p>No published articles yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Author {
  id: number;
  name: string;
  email: string;
  bio: string | null;
  website: string | null;
  followers_count: number;
  following_count: number;
  posts_count: number;
}

interface Post {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  featured_image: string | null;
  published_at: string;
  reading_time: number | null;
}

interface Props {
  author: Author;
  posts: Post[];
  is_following: boolean;
}

const props = defineProps<Props>();

const toggleFollow = () => {
  router.post(`/user/${props.author.id}/follow`, {}, {
    preserveScroll: true,
  });
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>
