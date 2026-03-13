<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';
import { computed } from 'vue';

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
    const baseClasses =
        'group block bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-200 dark:border-slate-700';

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
        day: 'numeric',
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};
</script>

<template>
    <article :class="cardClasses">
        <Link :href="`/blog/${post.slug}`">
            <!-- Featured Image -->
            <div
                :class="[
                    'relative overflow-hidden bg-slate-100 dark:bg-slate-700',
                    imageHeight,
                ]"
            >
                <img
                    v-if="post.featured_image"
                    :src="post.featured_image"
                    :alt="post.title"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-300 to-slate-500 dark:from-slate-600 dark:to-slate-800"
                >
                    <div class="text-4xl font-bold text-white opacity-30">
                        {{ post.title.charAt(0) }}
                    </div>
                </div>

                <!-- Reading time overlay -->
                <div class="absolute top-3 right-3">
                    <div
                        class="flex items-center space-x-1 rounded-full bg-black/70 px-2 py-1 text-xs text-white"
                    >
                        <Clock class="h-3 w-3" />
                        <span>{{ post.reading_time }}m</span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Tags -->
                <div class="mb-3 flex flex-wrap gap-2">
                    <span
                        v-for="tag in post.tags.slice(0, 3)"
                        :key="tag"
                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                    >
                        {{ tag }}
                    </span>
                </div>

                <!-- Title -->
                <h3
                    :class="[
                        'mb-3 line-clamp-2 font-bold text-slate-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400',
                        titleSize,
                    ]"
                >
                    {{ post.title }}
                </h3>

                <!-- Excerpt -->
                <p
                    class="mb-4 line-clamp-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                >
                    {{ post.excerpt }}
                </p>

                <!-- Author and Date -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Avatar -->
                        <div
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-500"
                        >
                            <img
                                v-if="post.author.avatar"
                                :src="post.author.avatar"
                                :alt="post.author.name"
                                class="h-full w-full rounded-full object-cover"
                            />
                            <span
                                v-else
                                class="text-xs font-semibold text-white"
                            >
                                {{ getInitials(post.author.name) }}
                            </span>
                        </div>

                        <!-- Author Info -->
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-slate-900 dark:text-white"
                            >
                                {{ post.author.name }}
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{ formatDate(post.published_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Read More Indicator -->
                    <div
                        class="flex items-center text-blue-600 transition-colors group-hover:text-blue-700 dark:text-blue-400 dark:group-hover:text-blue-300"
                    >
                        <span class="text-sm font-medium">Read more</span>
                        <svg
                            class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </Link>
    </article>
</template>

<style scoped>
/* Line clamp utilities are defined in global app.css */

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

img[src=''] {
    opacity: 0;
}
</style>
