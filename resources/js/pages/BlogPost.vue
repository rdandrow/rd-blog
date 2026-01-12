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
            {{ formatPostDate(post.published_at) }}
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

      <!-- Like Section -->
      <div v-if="$page.props.auth.user" class="mt-8 flex items-center gap-4">
        <button
          @click="toggleLike"
          class="flex items-center gap-2 rounded-lg px-4 py-2 transition-colors"
          :class="post.user_has_liked 
            ? 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30' 
            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
        >
          <svg 
            class="w-6 h-6" 
            :fill="post.user_has_liked ? 'currentColor' : 'none'" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
          <span class="font-medium">{{ post.likes_count }} {{ post.likes_count === 1 ? 'Like' : 'Likes' }}</span>
        </button>
      </div>
      
      <!-- Like count for guests -->
      <div v-else-if="post.likes_count > 0" class="mt-8">
        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
          <span>{{ post.likes_count }} {{ post.likes_count === 1 ? 'Like' : 'Likes' }}</span>
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-12 border-t border-gray-200 dark:border-gray-700 pt-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div>
              <Link
                v-if="post.author"
                :href="`/author/${post.author.id}`"
                class="text-lg font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline"
              >
                {{ post.author.name }}
              </Link>
              <h3 v-else class="text-lg font-medium text-gray-900 dark:text-white">
                Anonymous
              </h3>
            </div>
            
            <button
              v-if="$page.props.auth.user && post.author"
              @click="toggleFollow"
              class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
              :class="post.is_following_author 
                ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' 
                : 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600'"
            >
              {{ post.is_following_author ? 'Following' : 'Follow' }}
            </button>
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

    <!-- Comments Section -->
    <section class="mx-auto max-w-4xl px-4 pb-12 sm:px-6 lg:px-8">
      <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
          Comments ({{ post.comments?.length || 0 }})
        </h2>

        <!-- Comment Form (for authenticated users) -->
        <div v-if="$page.props.auth.user" class="mb-8">
          <form @submit.prevent="submitMainComment" class="space-y-4">
            <div>
              <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Add a comment
              </label>
              <textarea
                id="comment"
                v-model="form.content"
                rows="4"
                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                placeholder="Share your thoughts..."
                maxlength="1000"
                required
              ></textarea>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ form.content.length }}/1000 characters
              </p>
            </div>
            <button
              type="submit"
              :disabled="form.processing || !form.content.trim()"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ form.processing ? 'Posting...' : 'Post Comment' }}
            </button>
          </form>
        </div>

        <!-- Login prompt for guests -->
        <div v-else class="mb-8 rounded-md bg-gray-100 dark:bg-gray-800 p-4">
          <p class="text-sm text-gray-700 dark:text-gray-300">
            <Link :href="login()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
              Log in
            </Link>
            or
            <Link :href="register()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
              register
            </Link>
            to leave a comment
          </p>
        </div>

        <!-- Comments List -->
        <div v-if="post.comments && post.comments.length > 0" class="space-y-6">
          <div
            v-for="comment in post.comments"
            :key="comment.id"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
          >
            <!-- Comment Header -->
            <div class="flex justify-between items-start mb-2">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-semibold">
                  {{ comment.user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">
                    {{ comment.user.name }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatCommentDate(comment.created_at) }}
                  </p>
                </div>
              </div>
              <button
                v-if="canDeleteComment(comment)"
                @click="deleteComment(comment.id)"
                class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              >
                Delete
              </button>
            </div>
            
            <!-- Comment Content -->
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-3">
              {{ comment.content }}
            </p>

            <!-- Reply Button -->
            <button
              v-if="$page.props.auth.user && replyingTo !== comment.id"
              @click="startReply(comment.id)"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
            >
              Reply
            </button>

            <!-- Reply Form -->
            <div v-if="replyingTo === comment.id" class="mt-4 ml-8">
              <form @submit.prevent="submitReply" class="space-y-3">
                <div>
                  <textarea
                    v-model="replyForm.content"
                    rows="3"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Write your reply..."
                    maxlength="1000"
                    required
                  ></textarea>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ replyForm.content.length }}/1000 characters
                  </p>
                </div>
                <div class="flex gap-2">
                  <button
                    type="submit"
                    :disabled="replyForm.processing || !replyForm.content.trim()"
                    class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ replyForm.processing ? 'Posting...' : 'Post Reply' }}
                  </button>
                  <button
                    type="button"
                    @click="cancelReply"
                    class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>

            <!-- Replies -->
            <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 ml-8 space-y-4">
              <div
                v-for="reply in comment.replies"
                :key="reply.id"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3"
              >
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center text-white text-xs font-semibold">
                      {{ reply.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ reply.user.name }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatCommentDate(reply.created_at) }}
                      </p>
                    </div>
                  </div>
                  <button
                    v-if="canDeleteComment(reply)"
                    @click="deleteComment(reply.id)"
                    class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                  >
                    Delete
                  </button>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                  {{ reply.content }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- No comments message -->
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
          <p>No comments yet. Be the first to comment!</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import MarkdownRender from '@/components/MarkdownRender.vue';
import { login, register } from '@/routes';

interface Author {
  id: number;
  name: string;
}

interface Comment {
  id: number;
  content: string;
  created_at: string;
  user: {
    id: number;
    name: string;
  };
  replies?: Comment[];
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
  comments?: Comment[];
  likes_count: number;
  user_has_liked: boolean;
  is_following_author: boolean;
}

interface Props {
  post: BlogPost;
}

const props = defineProps<Props>();
const page = usePage();

// Comment form for main comments
const form = useForm({
  content: '',
  parent_id: null as number | null,
});

// Separate form for replies
const replyForm = useForm({
  content: '',
  parent_id: null as number | null,
});

const replyingTo = ref<number | null>(null);

const toggleLike = () => {
  router.post(`/blog/${props.post.slug}/like`, {}, {
    preserveScroll: true,
  });
};

const toggleFollow = () => {
  if (!props.post.author) return;
  router.post(`/user/${props.post.author.id}/follow`, {}, {
    preserveScroll: true,
  });
};

const submitMainComment = () => {
  // Ensure parent_id is null for main comments
  form.parent_id = null;
  form.post(`/blog/${props.post.slug}/comments`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};

const submitReply = () => {
  replyForm.post(`/blog/${props.post.slug}/comments`, {
    preserveScroll: true,
    onSuccess: () => {
      replyForm.reset();
      replyingTo.value = null;
    },
  });
};

const startReply = (commentId: number) => {
  replyingTo.value = commentId;
  replyForm.parent_id = commentId;
  replyForm.content = '';
};

const cancelReply = () => {
  replyingTo.value = null;
  replyForm.reset();
};

const deleteComment = (commentId: number) => {
  if (confirm('Are you sure you want to delete this comment?')) {
    router.delete(`/comments/${commentId}`, {
      preserveScroll: true,
    });
  }
};

const canDeleteComment = (comment: Comment) => {
  const user = page.props.auth?.user as any;
  if (!user) return false;
  
  // User can delete their own comments, or admins can delete any comment
  return comment.user.id === user.id || user.role === 'admin' || user.role === 'master_admin';
};

const formatPostDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatCommentDate = (dateString: string): string => {
  const date = new Date(dateString);
  const dateStr = date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  const timeStr = date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
  return `${dateStr} at ${timeStr}`;
};

// Markdown is rendered via <MarkdownRender />
</script>

