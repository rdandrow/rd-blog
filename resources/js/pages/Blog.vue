<script setup lang="ts">
import BlogFeatured from '@/components/blog/BlogFeatured.vue';
import BlogGrid from '@/components/blog/BlogGrid.vue';
import BlogHero from '@/components/blog/BlogHero.vue';
import BlogLayout from '@/layouts/BlogLayout.vue';
import type { AppPageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Interface for blog post data
interface BlogPost {
    id: number;
    title: string;
    excerpt: string;
    content: string;
    slug: string;
    featured_image?: string;
    author: {
        name: string;
        avatar?: string;
    };
    published_at: string;
    reading_time: number;
    tags: string[];
    is_featured?: boolean;
}

interface BlogPageProps extends AppPageProps {
    posts: BlogPost[];
    featured_posts: BlogPost[];
}

// Get page props with type safety
const page = usePage<BlogPageProps>();
const { posts, featured_posts } = page.props;

// Computed properties following Vue 3 composition API best practices
const regularPosts = computed(() => posts.filter((post) => !post.is_featured));

const pageTitle = computed(() => 'Blog - Latest Articles & Insights');
const pageDescription = computed(
    () =>
        'Discover the latest articles, tutorials, and insights on web development, programming, and technology trends.',
);
</script>

<template>
    <Head :title="pageTitle" />

    <BlogLayout>
        <!-- Hero Section -->
        <BlogHero
            title="Welcome to Our Blog"
            subtitle="Discover insights, tutorials, and stories that matter"
            :description="pageDescription"
        />

        <!-- Featured Posts Section -->
        <section
            v-if="featured_posts.length > 0"
            class="bg-muted/50 py-16"
            aria-labelledby="featured-heading"
        >
            <div class="container mx-auto max-w-6xl px-4">
                <div class="w-full">
                    <header class="mb-12 text-center">
                        <h2
                            id="featured-heading"
                            class="mb-4 text-3xl font-bold text-foreground"
                        >
                            Featured Articles
                        </h2>
                        <p
                            class="mx-auto max-w-2xl text-lg text-muted-foreground"
                        >
                            Hand-picked articles that showcase the best of our
                            content
                        </p>
                    </header>

                    <BlogFeatured :posts="featured_posts" />
                </div>
            </div>
        </section>

        <!-- Latest Posts Section -->
        <section class="py-16" aria-labelledby="latest-heading">
            <div class="container mx-auto max-w-6xl px-4">
                <div class="w-full">
                    <header class="mb-12 text-center">
                        <h2
                            id="latest-heading"
                            class="mb-4 text-3xl font-bold text-foreground"
                        >
                            Latest Articles
                        </h2>
                        <p
                            class="mx-auto max-w-2xl text-lg text-muted-foreground"
                        >
                            Stay up to date with our newest content and insights
                        </p>
                    </header>

                    <BlogGrid
                        :posts="regularPosts"
                        :loading="false"
                        @load-more="() => {}"
                    />
                </div>
            </div>
        </section>

        <!-- Newsletter Signup Section -->
        <section
            class="border-t border-border bg-card py-20"
            aria-labelledby="newsletter-heading"
        >
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <h2
                        id="newsletter-heading"
                        class="mb-6 text-3xl font-bold text-foreground md:text-4xl"
                    >
                        Stay in the Loop
                    </h2>
                    <p
                        class="mx-auto mb-8 max-w-2xl text-xl text-muted-foreground"
                    >
                        Subscribe to our newsletter and never miss our latest
                        articles, tutorials, and insights.
                    </p>

                    <form
                        class="mx-auto flex max-w-md flex-col gap-4 sm:flex-row"
                        @submit.prevent="() => {}"
                    >
                        <label class="sr-only" for="email-newsletter">
                            Email address
                        </label>
                        <input
                            id="email-newsletter"
                            type="email"
                            placeholder="Enter your email"
                            required
                            class="flex-1 rounded-lg border border-border bg-background px-6 py-3 text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                        />
                        <button
                            type="submit"
                            class="rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground transition-colors duration-200 hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </BlogLayout>
</template>

<style scoped>
/* Component-scoped styles following Vue.js best practices */

/* Smooth transitions for interactive elements */
button {
    transition: all 0.2s ease-in-out;
}

/* Focus styles for accessibility */
input:focus,
button:focus {
    outline-offset: 2px;
}
</style>

<!-- Animations moved to global app.css for reusability -->
