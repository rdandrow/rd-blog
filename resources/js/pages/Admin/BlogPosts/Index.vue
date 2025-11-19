<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';

interface BlogPost {
  id: number;
  title: string;
  excerpt: string;
  slug: string;
  is_featured: boolean;
  is_published: boolean;
  published_at: string | null;
  created_at: string | null;
  reading_time: number;
  tags: string[] | null;
  author: {
    id: number;
    name: string;
    email: string;
  } | null;
}

type PageProps = AppPageProps<{
  posts?: {
    data: BlogPost[];
    links: any[];
    meta: any;
  };
}>;

const props = defineProps<PageProps>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Blog Posts', href: '/admin/blog-posts' },
];

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'No date';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const getStatusBadge = (post: BlogPost) => {
  if (post.is_published) {
    return { text: 'Published', class: 'bg-green-100 text-green-800' };
  }
  return { text: 'Draft', class: 'bg-yellow-100 text-yellow-800' };
};

const deletePost = (post: BlogPost) => {
  if (confirm('Are you sure you want to delete this post?')) {
    router.delete(`/admin/blog-posts/${post.id}`);
  }
};
</script>

<template>
  <Head title="Blog Posts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- Loading state for when posts data is not available -->
    <div v-if="!props.posts" class="flex items-center justify-center min-h-64">
      <div class="text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
        <p class="text-muted-foreground">Loading...</p>
      </div>
    </div>
    
    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-foreground">Blog Posts</h1>
          <p class="text-muted-foreground">Manage your blog posts and articles</p>
        </div>
        
        <Link
          href="/admin/blog-posts/create"
          class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          New Post
        </Link>
      </div>

      <!-- Posts List -->
      <div class="bg-card border border-border rounded-lg overflow-hidden">
        <div v-if="!props.posts?.data || props.posts.data.length === 0" class="p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-4 bg-muted rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-foreground mb-2">No blog posts yet</h3>
          <p class="text-muted-foreground mb-4">Get started by creating your first blog post.</p>
          <Link
            href="/admin/blog-posts/create"
            class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
          >
            Create Your First Post
          </Link>
        </div>

        <div v-else-if="props.posts?.data" class="divide-y divide-border">
          <div
            v-for="post in props.posts.data"
            :key="post.id"
            class="p-6 hover:bg-muted/50 transition-colors"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                  <Link
                    :href="`/admin/blog-posts/${post.id}`"
                    class="text-lg font-semibold text-foreground hover:text-primary transition-colors truncate"
                  >
                    {{ post.title }}
                  </Link>
                  
                  <!-- Status Badge -->
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadge(post).class]">
                    {{ getStatusBadge(post).text }}
                  </span>
                  
                  <!-- Featured Badge -->
                  <span
                    v-if="post.is_featured"
                    class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                  >
                    Featured
                  </span>
                </div>
                
                <p class="text-muted-foreground mb-3 line-clamp-2">
                  {{ post.excerpt }}
                </p>
                
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                  <span>By {{ post.author?.name || 'Unknown Author' }}</span>
                  <span>{{ post.reading_time }} min read</span>
                  <span>
                    {{ post.published_at ? 'Published ' + formatDate(post.published_at) : 'Created ' + formatDate(post.created_at) }}
                  </span>
                </div>
                
                <!-- Tags -->
                <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-1 mt-3">
                  <span
                    v-for="tag in post.tags"
                    :key="tag"
                    class="px-2 py-1 text-xs bg-muted text-muted-foreground rounded"
                  >
                    {{ tag }}
                  </span>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex items-center gap-2 ml-4">
                <Link
                  :href="`/admin/blog-posts/${post.id}`"
                  class="p-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded transition-colors"
                  title="View"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </Link>
                
                <Link
                  :href="`/admin/blog-posts/${post.id}/edit`"
                  class="p-2 text-muted-foreground hover:text-foreground hover:bg-muted rounded transition-colors"
                  title="Edit"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </Link>
                
                <button
                  @click="deletePost(post)"
                  class="p-2 text-muted-foreground hover:text-destructive hover:bg-muted rounded transition-colors"
                  title="Delete"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="props.posts?.links && props.posts.links.length > 3" class="flex items-center justify-center space-x-2">
        <template v-for="link in props.posts.links" :key="link.label || 'unknown'">
          <Link
            v-if="link.url"
            :href="link.url"
            :class="[
              'px-3 py-2 text-sm rounded-md transition-colors',
              link.active 
                ? 'bg-primary text-primary-foreground' 
                : 'text-muted-foreground hover:text-foreground hover:bg-muted'
            ]"
            v-html="link.label || ''"
          />
          <span
            v-else
            :class="[
              'px-3 py-2 text-sm rounded-md transition-colors',
              'text-muted-foreground opacity-50 cursor-not-allowed'
            ]"
            v-html="link.label || ''"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>