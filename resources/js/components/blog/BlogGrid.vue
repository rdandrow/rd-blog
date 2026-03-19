<script setup lang="ts">
import { ChevronDown, Loader2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    loading?: boolean;
    // Set true only when parent supports server-side pagination.
    hasMore?: boolean;
}

interface Emits {
    (e: 'load-more'): void;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    // Local lists should naturally reach end-of-posts unless parent overrides.
    hasMore: false,
});

const emit = defineEmits<Emits>();

// Reactive state
const displayCount = ref(6);
const isLoadingMore = ref(false);

// Computed properties
const displayedPosts = computed(() => props.posts.slice(0, displayCount.value));

const hasMorePosts = computed(
    () => displayCount.value < props.posts.length || props.hasMore,
);

// Methods
const loadMore = async () => {
    if (isLoadingMore.value || props.loading) return;

    isLoadingMore.value = true;

    try {
        // If we have more posts in the current array, show them
        if (displayCount.value < props.posts.length) {
            displayCount.value = Math.min(
                displayCount.value + 6,
                props.posts.length,
            );
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
    props.posts.forEach((post) => {
        post.tags.forEach((tag) => tags.add(tag));
    });
    return Array.from(tags).sort();
});
</script>

<template>
    <div class="space-y-8">
        <!-- Filter Bar (optional - for future enhancement) -->
        <div
            class="flex flex-wrap items-center gap-4 border-b border-slate-200 pb-6 dark:border-slate-700"
        >
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    :class="[
                        'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                        selectedTag === null
                            ? 'bg-blue-600 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700',
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
                        'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                        selectedTag === tag
                            ? 'bg-blue-600 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700',
                    ]"
                    @click="selectedTag = selectedTag === tag ? null : tag"
                >
                    {{ tag }}
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div
            v-if="props.loading && displayedPosts.length === 0"
            class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3"
        >
            <div v-for="i in 6" :key="i" class="animate-pulse">
                <div
                    class="mb-4 h-48 rounded-xl bg-slate-200 dark:bg-slate-700"
                ></div>
                <div class="space-y-3">
                    <div
                        class="h-4 w-3/4 rounded bg-slate-200 dark:bg-slate-700"
                    ></div>
                    <div
                        class="h-4 w-1/2 rounded bg-slate-200 dark:bg-slate-700"
                    ></div>
                    <div
                        class="h-3 w-5/6 rounded bg-slate-200 dark:bg-slate-700"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Posts Grid -->
        <div
            v-else-if="displayedPosts.length > 0"
            class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3"
        >
            <BlogCard
                v-for="post in displayedPosts"
                :key="post.id"
                :post="post"
                size="default"
            />
        </div>

        <!-- Empty State -->
        <div v-else class="py-16 text-center">
            <div
                class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800"
            >
                <svg
                    class="h-12 w-12 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
            </div>
            <h3
                class="mb-2 text-xl font-semibold text-slate-900 dark:text-white"
            >
                No articles found
            </h3>
            <p class="text-slate-600 dark:text-slate-400">
                We couldn't find any articles matching your criteria. Check back
                later for new content!
            </p>
        </div>

        <!-- Load More Button -->
        <div
            v-if="hasMorePosts && displayedPosts.length > 0"
            class="pt-8 text-center"
        >
            <button
                type="button"
                :disabled="isLoadingMore || props.loading"
                @click="loadMore"
                class="inline-flex items-center rounded-lg bg-slate-100 px-8 py-3 font-semibold text-slate-900 transition-all duration-200 hover:bg-slate-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-800 dark:text-white dark:hover:bg-slate-700"
            >
                <Loader2
                    v-if="isLoadingMore || props.loading"
                    class="mr-2 h-5 w-5 animate-spin"
                />
                <ChevronDown v-else class="mr-2 h-5 w-5" />
                {{
                    isLoadingMore || props.loading
                        ? 'Loading...'
                        : 'Load More Articles'
                }}
            </button>
        </div>

        <!-- End of Posts Indicator -->
        <div
            v-else-if="displayedPosts.length > 0 && !hasMorePosts"
            class="border-t border-slate-200 pt-8 text-center dark:border-slate-700"
        >
            <p class="text-slate-500 dark:text-slate-400">
                You've reached the end of our articles.
                <br />
                <span class="text-sm"
                    >Subscribe to our newsletter to get notified when we publish
                    new content!</span
                >
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Staggered animation delays - using shared fadeInUp from app.css */
.grid > * {
    animation: fadeInUp 0.6s ease-out both;
}

.grid > *:nth-child(1) {
    animation-delay: 0.1s;
}
.grid > *:nth-child(2) {
    animation-delay: 0.2s;
}
.grid > *:nth-child(3) {
    animation-delay: 0.3s;
}
.grid > *:nth-child(4) {
    animation-delay: 0.4s;
}
.grid > *:nth-child(5) {
    animation-delay: 0.5s;
}
.grid > *:nth-child(6) {
    animation-delay: 0.6s;
}
</style>
