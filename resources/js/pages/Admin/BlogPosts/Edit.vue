<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
  { title: 'Edit', href: `/admin/blog-posts/${props.post.id}/edit` },
];

const form = useForm({
  title: props.post.title,
  excerpt: props.post.excerpt,
  content: props.post.content,
  featured_image: props.post.featured_image || '',
  tags: [...props.post.tags],
  is_featured: props.post.is_featured,
  is_published: props.post.is_published,
  published_at: props.post.published_at ? new Date(props.post.published_at).toISOString().slice(0, 16) : null,
});

const tagInput = ref('');

const addTag = () => {
  const tag = tagInput.value.trim();
  if (tag && !form.tags.includes(tag)) {
    form.tags.push(tag);
    tagInput.value = '';
  }
};

const removeTag = (index: number) => {
  form.tags.splice(index, 1);
};

const handleTagKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addTag();
  }
};

const submit = () => {
  form.put(`/admin/blog-posts/${props.post.id}`, {
    onSuccess: () => {
      // Success is handled by the controller redirect
    },
  });
};
</script>

<template>
  <Head title="Edit Blog Post" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-foreground">Edit Blog Post</h1>
        <p class="text-muted-foreground">Update your blog post content and settings</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-card border border-border rounded-lg p-6 space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-foreground mb-2">
              Title *
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              placeholder="Enter post title..."
            />
            <div v-if="form.errors.title" class="mt-1 text-sm text-destructive">
              {{ form.errors.title }}
            </div>
          </div>

          <!-- Excerpt -->
          <div>
            <label for="excerpt" class="block text-sm font-medium text-foreground mb-2">
              Excerpt *
            </label>
            <textarea
              id="excerpt"
              v-model="form.excerpt"
              required
              rows="3"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y"
              placeholder="Brief description of your post..."
            />
            <p class="mt-1 text-sm text-muted-foreground">
              A short summary that appears in post listings and social media previews.
            </p>
            <div v-if="form.errors.excerpt" class="mt-1 text-sm text-destructive">
              {{ form.errors.excerpt }}
            </div>
          </div>

          <!-- Content -->
          <div>
            <label for="content" class="block text-sm font-medium text-foreground mb-2">
              Content *
            </label>
            <textarea
              id="content"
              v-model="form.content"
              required
              rows="12"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-y"
              placeholder="Write your post content here..."
            />
            <p class="mt-1 text-sm text-muted-foreground">
              You can use Markdown formatting for rich text content.
            </p>
            <div v-if="form.errors.content" class="mt-1 text-sm text-destructive">
              {{ form.errors.content }}
            </div>
          </div>

          <!-- Featured Image -->
          <div>
            <label for="featured_image" class="block text-sm font-medium text-foreground mb-2">
              Featured Image URL
            </label>
            <input
              id="featured_image"
              v-model="form.featured_image"
              type="url"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              placeholder="https://example.com/image.jpg"
            />
            <p class="mt-1 text-sm text-muted-foreground">
              Optional image URL to display with your post.
            </p>
            <div v-if="form.errors.featured_image" class="mt-1 text-sm text-destructive">
              {{ form.errors.featured_image }}
            </div>
          </div>

          <!-- Tags -->
          <div>
            <label for="tags" class="block text-sm font-medium text-foreground mb-2">
              Tags
            </label>
            <div class="space-y-3">
              <!-- Tag Input -->
              <div class="flex gap-2">
                <input
                  id="tags"
                  v-model="tagInput"
                  type="text"
                  @keydown="handleTagKeydown"
                  class="flex-1 px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                  placeholder="Add a tag and press Enter..."
                />
                <button
                  type="button"
                  @click="addTag"
                  class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
                >
                  Add
                </button>
              </div>

              <!-- Tag List -->
              <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="(tag, index) in form.tags"
                  :key="index"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-muted text-muted-foreground text-sm rounded-full"
                >
                  {{ tag }}
                  <button
                    type="button"
                    @click="removeTag(index)"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              </div>
            </div>
            <div v-if="form.errors.tags" class="mt-1 text-sm text-destructive">
              {{ form.errors.tags }}
            </div>
          </div>
        </div>

        <!-- Publishing Options -->
        <div class="bg-card border border-border rounded-lg p-6 space-y-4">
          <h3 class="text-lg font-semibold text-foreground">Publishing Options</h3>
          
          <!-- Featured -->
          <div class="flex items-center gap-3">
            <input
              id="is_featured"
              v-model="form.is_featured"
              type="checkbox"
              class="w-4 h-4 border border-border rounded focus:ring-2 focus:ring-ring"
            />
            <label for="is_featured" class="text-sm font-medium text-foreground">
              Featured Post
            </label>
            <span class="text-sm text-muted-foreground">
              Display this post prominently on the blog homepage
            </span>
          </div>

          <!-- Published -->
          <div class="flex items-center gap-3">
            <input
              id="is_published"
              v-model="form.is_published"
              type="checkbox"
              class="w-4 h-4 border border-border rounded focus:ring-2 focus:ring-ring"
            />
            <label for="is_published" class="text-sm font-medium text-foreground">
              Published
            </label>
            <span class="text-sm text-muted-foreground">
              Make this post visible to readers
            </span>
          </div>

          <!-- Custom Publish Date -->
          <div v-if="form.is_published" class="ml-7">
            <label for="published_at" class="block text-sm font-medium text-foreground mb-2">
              Publish Date (Optional)
            </label>
            <input
              id="published_at"
              v-model="form.published_at"
              type="datetime-local"
              class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
            <p class="mt-1 text-sm text-muted-foreground">
              Leave blank to use current publish date, or set a future date to schedule.
            </p>
          </div>
        </div>

        <!-- Post Info -->
        <div class="bg-muted/50 border border-border rounded-lg p-6">
          <h3 class="text-lg font-semibold text-foreground mb-3">Post Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="font-medium text-foreground">Slug:</span>
              <span class="ml-2 text-muted-foreground">{{ post.slug }}</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Reading Time:</span>
              <span class="ml-2 text-muted-foreground">{{ post.reading_time }} minutes</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Created:</span>
              <span class="ml-2 text-muted-foreground">{{ new Date(post.created_at).toLocaleDateString() }}</span>
            </div>
            <div>
              <span class="font-medium text-foreground">Last Updated:</span>
              <span class="ml-2 text-muted-foreground">{{ new Date(post.updated_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="$inertia.visit(`/admin/blog-posts/${post.id}`)"
              class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors"
            >
              Cancel
            </button>
          </div>
          
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ form.processing ? 'Updating...' : 'Update Post' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>