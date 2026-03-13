<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
        day: 'numeric',
    });
};
</script>

<template>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Primary Featured Post -->
        <article
            v-if="primaryFeatured"
            class="group cursor-pointer lg:col-span-1"
        >
            <Link :href="`/blog/${primaryFeatured.slug}`" class="block">
                <div
                    class="relative mb-6 aspect-[4/3] overflow-hidden rounded-2xl bg-slate-100 transition-transform duration-300 group-hover:scale-[1.02] dark:bg-slate-800"
                >
                    <img
                        v-if="primaryFeatured.featured_image"
                        :src="primaryFeatured.featured_image"
                        :alt="primaryFeatured.title"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600"
                    >
                        <div class="text-6xl font-bold text-white opacity-20">
                            {{ primaryFeatured.title.charAt(0) }}
                        </div>
                    </div>

                    <!-- Featured Badge -->
                    <div class="absolute top-4 left-4">
                        <span
                            class="rounded-full bg-blue-600 px-3 py-1 text-sm font-semibold text-white"
                        >
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
                            class="rounded-full bg-slate-200 px-3 py-1 text-sm text-slate-700 dark:bg-slate-700 dark:text-slate-300"
                        >
                            {{ tag }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3
                        class="line-clamp-2 text-2xl font-bold text-slate-900 transition-colors group-hover:text-blue-600 lg:text-3xl dark:text-white dark:group-hover:text-blue-400"
                    >
                        {{ primaryFeatured.title }}
                    </h3>

                    <!-- Excerpt -->
                    <p
                        class="line-clamp-3 text-lg leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        {{ primaryFeatured.excerpt }}
                    </p>

                    <!-- Meta -->
                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-500"
                            >
                                <span class="font-semibold text-white">
                                    {{ primaryFeatured.author.name.charAt(0) }}
                                </span>
                            </div>
                            <div>
                                <p
                                    class="text-sm font-medium text-slate-900 dark:text-white"
                                >
                                    {{ primaryFeatured.author.name }}
                                </p>
                                <p
                                    class="text-sm text-slate-500 dark:text-slate-400"
                                >
                                    {{
                                        formatDate(primaryFeatured.published_at)
                                    }}
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
        <div class="space-y-8 lg:col-span-1">
            <article
                v-for="post in secondaryFeatured"
                :key="post.id"
                class="group cursor-pointer"
            >
                <Link :href="`/blog/${post.slug}`" class="flex gap-6">
                    <!-- Image -->
                    <div
                        class="relative h-24 w-32 flex-shrink-0 overflow-hidden rounded-xl bg-slate-100 transition-transform duration-300 group-hover:scale-[1.02] dark:bg-slate-800"
                    >
                        <img
                            v-if="post.featured_image"
                            :src="post.featured_image"
                            :alt="post.title"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-400 to-slate-600"
                        >
                            <div
                                class="text-2xl font-bold text-white opacity-30"
                            >
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
                                class="rounded-full bg-slate-200 px-2 py-1 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                            >
                                {{ tag }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h4
                            class="line-clamp-2 text-lg font-bold text-slate-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                        >
                            {{ post.title }}
                        </h4>

                        <!-- Excerpt -->
                        <p
                            class="line-clamp-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            {{ post.excerpt }}
                        </p>

                        <!-- Meta -->
                        <div
                            class="flex items-center justify-between pt-2 text-xs text-slate-500 dark:text-slate-400"
                        >
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
/* Line clamp utilities are defined in global app.css */

/* Smooth transitions */
article {
    transition: all 0.3s ease;
}

/* Hover effects */
article:hover {
    transform: translateY(-2px);
}
</style>
