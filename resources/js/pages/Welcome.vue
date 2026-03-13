<script setup lang="ts">
import SearchSidebar from '@/components/SearchSidebar.vue';
import UserDropdown from '@/components/UserDropdown.vue';
import { formatDate } from '@/composables/useBlogUtils';
import { useSearchState } from '@/composables/useSearchState';
import { dashboard, login, register } from '@/routes';
import type { Author, BlogPost, SearchFilters } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

withDefaults(
    defineProps<{
        canRegister: boolean;
        posts?: BlogPost[];
        featured_posts?: BlogPost[];
        filters?: SearchFilters;
        availableTags?: string[];
        availableAuthors?: Author[];
    }>(),
    {
        canRegister: true,
        posts: () => [],
        featured_posts: () => [],
        filters: () => ({}),
        availableTags: () => [],
        availableAuthors: () => [],
    },
);

// Use search state composable
const { isSearchOpen, openSearch, closeSearch } = useSearchState();

// Check if user is authenticated and is a member
const user = computed(() => page.props.auth?.user as any);
const isMember = computed(() => user.value && user.value.role === 'member');

// Sign out function
const signOut = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <!-- Header -->
        <header class="border-b border-[#19140035] dark:border-[#3E3E3A]">
            <div class="mx-auto max-w-7xl px-6 py-4">
                <nav class="flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <h1 class="text-2xl font-bold">Blog</h1>
                        <Link
                            href="/blog"
                            class="text-sm hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            All Posts
                        </Link>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Search Icon Button -->
                        <button
                            @click="openSearch"
                            class="inline-flex items-center gap-2 rounded-sm border border-transparent px-3 py-1.5 text-sm leading-normal transition-colors hover:border-[#19140035] dark:hover:border-[#3E3E3A]"
                            aria-label="Open search"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            <span class="hidden sm:inline">Search</span>
                        </button>

                        <template v-if="$page.props.auth.user">
                            <!-- Dashboard link for admins -->
                            <Link
                                v-if="!isMember"
                                :href="dashboard()"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>

                            <!-- User dropdown for members -->
                            <UserDropdown
                                v-if="isMember"
                                :userName="user.name"
                                :userRole="user.role"
                            />

                            <!-- Sign out button for admins -->
                            <button
                                v-if="!isMember"
                                @click="signOut"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Sign out
                            </button>
                        </template>

                        <template v-else>
                            <Link
                                :href="login()"
                                class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal hover:border-[#19140035] dark:hover:border-[#3E3E3A]"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="register()"
                                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Register
                            </Link>
                        </template>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="py-12 lg:py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <h2 class="mb-6 text-4xl font-bold lg:text-6xl">
                        Curiously Testing
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-lg text-gray-600 lg:text-xl dark:text-gray-300"
                    >
                        Discover insights, tutorials, and stories from my
                        journey as a quality engineer.
                    </p>
                </div>

                <!-- Featured Posts -->
                <div
                    v-if="featured_posts && featured_posts.length > 0"
                    class="mb-16"
                >
                    <h3 class="mb-8 text-2xl font-bold">Featured Posts</h3>
                    <div class="grid gap-8 lg:grid-cols-2">
                        <article
                            v-for="post in featured_posts"
                            :key="post.id"
                            v-memo="[post.id, post.title, post.featured_image]"
                            class="group cursor-pointer"
                        >
                            <Link :href="`/blog/${post.slug}`" class="block">
                                <div
                                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div
                                        class="relative aspect-video overflow-hidden"
                                    >
                                        <img
                                            v-if="post.featured_image"
                                            :src="post.featured_image"
                                            :alt="post.title"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                            loading="lazy"
                                            @error="
                                                ($event) =>
                                                    ((
                                                        $event.target as HTMLElement
                                                    ).style.display = 'none')
                                            "
                                        />
                                        <div
                                            v-if="!post.featured_image"
                                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-600"
                                        >
                                            <svg
                                                class="h-12 w-12 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <div
                                            class="mb-3 flex items-center gap-2"
                                        >
                                            <span
                                                class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                                            >
                                                Featured
                                            </span>
                                        </div>
                                        <h4
                                            class="mb-2 text-xl font-semibold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                                        >
                                            {{ post.title }}
                                        </h4>
                                        <p
                                            class="mb-4 line-clamp-2 min-h-[3rem] text-gray-600 dark:text-gray-300"
                                        >
                                            {{ post.excerpt }}
                                        </p>
                                        <div
                                            class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            <span
                                                >By {{ post.author.name }}</span
                                            >
                                            <div
                                                class="flex items-center gap-4"
                                            >
                                                <span
                                                    >{{ post.reading_time }} min
                                                    read</span
                                                >
                                                <span>{{
                                                    formatDate(
                                                        post.published_at,
                                                    )
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </article>
                    </div>
                </div>

                <!-- Recent Posts -->
                <div v-if="posts && posts.length > 0">
                    <h3 class="mb-8 text-2xl font-bold">Recent Posts</h3>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="post in posts"
                            :key="post.id"
                            v-memo="[post.id, post.title, post.featured_image]"
                            class="group cursor-pointer"
                        >
                            <Link :href="`/blog/${post.slug}`" class="block">
                                <div
                                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <div
                                        class="relative aspect-video overflow-hidden"
                                    >
                                        <img
                                            v-if="post.featured_image"
                                            :src="post.featured_image"
                                            :alt="post.title"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                            loading="lazy"
                                            @error="
                                                ($event) =>
                                                    ((
                                                        $event.target as HTMLElement
                                                    ).style.display = 'none')
                                            "
                                        />
                                        <div
                                            v-if="!post.featured_image"
                                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700"
                                        >
                                            <svg
                                                class="h-8 w-8 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h4
                                            class="mb-2 line-clamp-2 font-semibold transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                                        >
                                            {{ post.title }}
                                        </h4>
                                        <p
                                            class="mb-3 line-clamp-2 min-h-[2.5rem] text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {{ post.excerpt }}
                                        </p>
                                        <div
                                            class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            <span>{{ post.author.name }}</span>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    >{{
                                                        post.reading_time
                                                    }}
                                                    min</span
                                                >
                                                <span>{{
                                                    formatDate(
                                                        post.published_at,
                                                    )
                                                }}</span>
                                            </div>
                                        </div>
                                        <!-- Tags -->
                                        <div
                                            v-if="
                                                post.tags &&
                                                post.tags.length > 0
                                            "
                                            class="mt-3 flex flex-wrap gap-1"
                                        >
                                            <span
                                                v-for="tag in post.tags.slice(
                                                    0,
                                                    3,
                                                )"
                                                :key="tag"
                                                class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ tag }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </article>
                    </div>
                </div>

                <!-- Call to Action -->
                <div
                    class="mt-16 rounded-lg bg-gray-50 py-12 text-center dark:bg-gray-800"
                >
                    <h3 class="mb-4 text-2xl font-bold">Want to see more?</h3>
                    <p class="mb-6 text-gray-600 dark:text-gray-300">
                        Explore all our blog posts and discover more great
                        content.
                    </p>
                    <Link
                        href="/blog"
                        class="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        View All Posts
                        <svg
                            class="ml-2 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"
                            />
                        </svg>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-if="
                        (!posts || posts.length === 0) &&
                        (!featured_posts || featured_posts.length === 0)
                    "
                    class="py-16 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                    >
                        <svg
                            class="h-8 w-8 text-gray-400"
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
                    <h3 class="mb-2 text-lg font-semibold">
                        No blog posts yet
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Check back soon for new content!
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-[#19140035] py-8 dark:border-[#3E3E3A]">
            <div class="mx-auto max-w-7xl px-6 text-center">
                <p class="text-gray-600 dark:text-gray-300">
                    © 2025 Blog. Built with Laravel and Vue.js.
                </p>
            </div>
        </footer>

        <!-- Search Sidebar -->
        <SearchSidebar
            :is-open="isSearchOpen"
            :filters="filters"
            :available-tags="availableTags"
            :available-authors="availableAuthors"
            current-route="/"
            @close="closeSearch"
        />
    </div>
</template>

<!-- Line clamp utilities moved to global app.css for reusability -->
