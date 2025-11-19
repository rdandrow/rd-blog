<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SearchSidebar from '@/components/SearchSidebar.vue';

interface BlogPost {
    id: number;
    title: string;
    excerpt: string;
    slug: string;
    featured_image: string | null;
    author: {
        id: number;
        name: string;
    };
    published_at: string;
    reading_time: number;
    tags: string[];
    is_featured: boolean;
}

interface Author {
    id: number;
    name: string;
}

interface Filters {
    search?: string | null;
    tag?: string | null;
    author?: string | null;
}

withDefaults(
    defineProps<{
        canRegister: boolean;
        posts?: BlogPost[];
        featured_posts?: BlogPost[];
        filters?: Filters;
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Search sidebar state
const isSearchOpen = ref(false);

const openSearch = () => {
    isSearchOpen.value = true;
};

const closeSearch = () => {
    isSearchOpen.value = false;
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <!-- Header -->
        <header class="border-b border-[#19140035] dark:border-[#3E3E3A]">
            <div class="max-w-7xl mx-auto px-6 py-4">
                <nav class="flex items-center justify-between">
                    <div class="flex items-center gap-8">
                        <h1 class="text-2xl font-bold">Blog</h1>
                        <Link href="/blog" class="text-sm hover:text-gray-600 dark:hover:text-gray-300">
                            All Posts
                        </Link>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Search Icon Button -->
                        <button
                            @click="openSearch"
                            class="inline-flex items-center gap-2 rounded-sm border border-transparent px-3 py-1.5 text-sm leading-normal hover:border-[#19140035] dark:hover:border-[#3E3E3A] transition-colors"
                            aria-label="Open search"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span class="hidden sm:inline">Search</span>
                        </button>
                        
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                        >
                            Dashboard
                        </Link>
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
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-4xl lg:text-6xl font-bold mb-6">
                        Curiously Testing
                    </h2>
                    <p class="text-lg lg:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Discover insights, tutorials, and stories from my journey as a quality engineer.
                    </p>
                </div>

                <!-- Featured Posts -->
                <div v-if="featured_posts && featured_posts.length > 0" class="mb-16">
                    <h3 class="text-2xl font-bold mb-8">Featured Posts</h3>
                    <div class="grid lg:grid-cols-2 gap-8">
                        <article 
                            v-for="post in featured_posts" 
                            :key="post.id"
                            class="group cursor-pointer"
                        >
                            <Link :href="`/blog/${post.slug}`" class="block">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                                    <div class="aspect-video relative overflow-hidden">
                                        <img 
                                            v-if="post.featured_image"
                                            :src="post.featured_image" 
                                            :alt="post.title"
                                            class="w-full h-full object-cover transition-transform group-hover:scale-105"
                                            loading="lazy"
                                            @error="($event) => ($event.target as HTMLElement).style.display = 'none'"
                                        />
                                        <div 
                                            v-if="!post.featured_image"
                                            class="w-full h-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center"
                                        >
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                Featured
                                            </span>
                                        </div>
                                        <h4 class="text-xl font-semibold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ post.title }}
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                                            {{ post.excerpt }}
                                        </p>
                                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                            <span>By {{ post.author.name }}</span>
                                            <div class="flex items-center gap-4">
                                                <span>{{ post.reading_time }} min read</span>
                                                <span>{{ formatDate(post.published_at) }}</span>
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
                    <h3 class="text-2xl font-bold mb-8">Recent Posts</h3>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <article 
                            v-for="post in posts" 
                            :key="post.id"
                            class="group cursor-pointer"
                        >
                            <Link :href="`/blog/${post.slug}`" class="block">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                                    <div class="aspect-video relative overflow-hidden">
                                        <img 
                                            v-if="post.featured_image"
                                            :src="post.featured_image" 
                                            :alt="post.title"
                                            class="w-full h-full object-cover transition-transform group-hover:scale-105"
                                            loading="lazy"
                                            @error="($event) => ($event.target as HTMLElement).style.display = 'none'"
                                        />
                                        <div 
                                            v-if="!post.featured_image"
                                            class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center"
                                        >
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h4 class="font-semibold mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
                                            {{ post.title }}
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-2">
                                            {{ post.excerpt }}
                                        </p>
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ post.author.name }}</span>
                                            <div class="flex items-center gap-2">
                                                <span>{{ post.reading_time }} min</span>
                                                <span>{{ formatDate(post.published_at) }}</span>
                                            </div>
                                        </div>
                                        <!-- Tags -->
                                        <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-1 mt-3">
                                            <span
                                                v-for="tag in post.tags.slice(0, 3)"
                                                :key="tag"
                                                class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded"
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
                <div class="text-center mt-16 py-12 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-2xl font-bold mb-4">Want to see more?</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Explore all our blog posts and discover more great content.
                    </p>
                    <Link 
                        href="/blog"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors"
                    >
                        View All Posts
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="(!posts || posts.length === 0) && (!featured_posts || featured_posts.length === 0)" class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">No blog posts yet</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Check back soon for new content!
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-[#19140035] dark:border-[#3E3E3A] py-8">
            <div class="max-w-7xl mx-auto px-6 text-center">
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

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
