<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import MarkdownRender from '@/components/MarkdownRender.vue';

interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  featured_image: string | null;
  tags: string[];
  is_featured: boolean;
  is_published: boolean;
  published_at: string | null;
  reading_time: number;
  created_at: string;
  updated_at: string;
  author: {
    id: number;
    name: string;
    email: string;
  };
}

interface PageProps {
  post: BlogPost;
}

const props = defineProps<PageProps>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Blog Posts', href: '/admin/blog-posts' },
  { title: props.post.title, href: `/admin/blog-posts/${props.post.id}` },
];

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const statusInfo = computed(() => {
  if (props.post.is_published) {
    return {
      text: 'Published',
      class: 'bg-green-100 text-green-800',
      icon: 'M5 13l4 4L19 7'
    };
  }
  return {
    text: 'Draft',
    class: 'bg-yellow-100 text-yellow-800',
    icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
  };
});
</script>

<template>
  <Head :title="post.title" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold text-foreground mb-2 break-words">
              {{ post.title }}
            </h1>
            
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span>By {{ post.author.name }}</span>
              <span>{{ post.reading_time }} min read</span>
              <span>
                {{ post.published_at ? 'Published ' + formatDate(post.published_at) : 'Created ' + formatDate(post.created_at) }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 ml-4">
            <Link
              :href="`/admin/blog-posts/${post.id}/edit`"
              class="inline-flex items-center px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Edit
            </Link>
            
            <Link
              href="/admin/blog-posts"
              class="inline-flex items-center px-4 py-2 border border-border text-foreground rounded-md hover:bg-muted transition-colors"
            >
              Back to Posts
            </Link>
          </div>
        </div>

        <!-- Status and Meta -->
        <div class="flex items-center gap-4 mb-6">
          <span :class="['inline-flex items-center gap-2 px-3 py-1 text-sm font-medium rounded-full', statusInfo.class]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusInfo.icon" />
            </svg>
            {{ statusInfo.text }}
          </span>
          
          <span v-if="post.is_featured" class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
            Featured
          </span>
        </div>

        <!-- Tags -->
        <div v-if="post.tags.length > 0" class="flex flex-wrap gap-2 mb-6">
          <span
            v-for="tag in post.tags"
            :key="tag"
            class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-full"
          >
            {{ tag }}
          </span>
        </div>
      </div>

      <!-- Featured Image -->
      <div v-if="post.featured_image" class="mb-8">
        <img
          :src="post.featured_image"
          :alt="post.title"
          class="w-full h-64 object-cover rounded-lg border border-border"
        />
      </div>

      <!-- Content -->
      <div class="bg-card border border-border rounded-lg overflow-hidden">
        <!-- Excerpt -->
        <div class="p-6 border-b border-border bg-muted/50">
          <h2 class="text-lg font-semibold text-foreground mb-2">Excerpt</h2>
          <p class="text-muted-foreground italic">{{ post.excerpt }}</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
          <h2 class="text-lg font-semibold text-foreground mb-4">Content</h2>
          <div class="prose prose-neutral max-w-none dark:prose-invert">
            <MarkdownRender :content="post.content" />
          </div>
        </div>
      </div>

      <!-- Metadata -->
      <div class="mt-8 bg-muted/50 border border-border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-foreground mb-4">Post Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="font-medium text-foreground">Slug:</span>
            <span class="ml-2 text-muted-foreground">{{ post.slug }}</span>
          </div>
          
          <div>
            <span class="font-medium text-foreground">Author:</span>
            <span class="ml-2 text-muted-foreground">{{ post.author.name }}</span>
          </div>
          
          <div>
            <span class="font-medium text-foreground">Created:</span>
            <span class="ml-2 text-muted-foreground">{{ formatDate(post.created_at) }}</span>
          </div>
          
          <div v-if="post.updated_at !== post.created_at">
            <span class="font-medium text-foreground">Updated:</span>
            <span class="ml-2 text-muted-foreground">{{ formatDate(post.updated_at) }}</span>
          </div>
          
          <div v-if="post.published_at">
            <span class="font-medium text-foreground">Published:</span>
            <span class="ml-2 text-muted-foreground">{{ formatDate(post.published_at) }}</span>
          </div>
          
          <div>
            <span class="font-medium text-foreground">Reading Time:</span>
            <span class="ml-2 text-muted-foreground">{{ post.reading_time }} minutes</span>
          </div>
        </div>
      </div>

      <!-- Preview Link -->
      <div v-if="post.is_published" class="mt-8 text-center">
        <Link
          :href="`/blog/${post.slug}`"
          class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          View Live Post
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.prose {
  line-height: 1.75;
}
</style>