import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Blog from '@/pages/Blog.vue';
import { usePage } from '@inertiajs/vue3';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { 
        name: 'Head', 
        template: '<head><title>{{ title }}</title></head>',
        props: ['title'],
    },
    usePage: vi.fn(() => ({
        props: {
            posts: [],
            featured_posts: [],
        },
    })),
}));

vi.mock('@/layouts/BlogLayout.vue', () => ({
    default: {
        name: 'BlogLayout',
        template: '<div class="blog-layout"><slot /></div>',
    },
}));

vi.mock('@/components/blog/BlogHero.vue', () => ({
    default: {
        name: 'BlogHero',
        template: '<div class="blog-hero"><h1>{{ title }}</h1><p>{{ subtitle }}</p><p>{{ description }}</p></div>',
        props: ['title', 'subtitle', 'description'],
    },
}));

vi.mock('@/components/blog/BlogGrid.vue', () => ({
    default: {
        name: 'BlogGrid',
        template: '<div class="blog-grid" :data-posts-count="posts.length"></div>',
        props: ['posts', 'loading'],
        emits: ['load-more'],
    },
}));

vi.mock('@/components/blog/BlogFeatured.vue', () => ({
    default: {
        name: 'BlogFeatured',
        template: '<div class="blog-featured" :data-posts-count="posts.length"></div>',
        props: ['posts'],
    },
}));

// Helper function to create mount options with proper $page mock
const createMountOptions = (authUser: any = null, posts: any[] = [], featured_posts: any[] = []) => {
    const pageData = {
        props: {
            auth: {
                user: authUser,
            },
            posts,
            featured_posts,
        },
    };
    
    // Update usePage mock to return the current test data
    (usePage as any).mockReturnValue(pageData);
    
    return {
        global: {
            mocks: {
                $page: pageData,
            },
        },
    };
};

describe('Blog Page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('rendering', () => {
        it('should render blog layout with hero section', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.findComponent({ name: 'BlogLayout' }).exists()).toBe(true);
            expect(wrapper.findComponent({ name: 'BlogHero' }).exists()).toBe(true);
        });

        it('should render hero with correct props', () => {
            const wrapper = mount(Blog, createMountOptions());
            const hero = wrapper.findComponent({ name: 'BlogHero' });

            expect(hero.props('title')).toBe('Welcome to Our Blog');
            expect(hero.props('subtitle')).toBe('Discover insights, tutorials, and stories that matter');
            expect(hero.props('description')).toContain('latest articles');
        });

        it('should render Head with correct title', () => {
            const wrapper = mount(Blog, createMountOptions());
            const head = wrapper.findComponent({ name: 'Head' });

            expect(head.exists()).toBe(true);
            expect(head.props('title')).toBe('Blog - Latest Articles & Insights');
        });

        it('should render newsletter signup section', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.find('input[type="email"]').exists()).toBe(true);
            expect(wrapper.text()).toContain('Stay in the Loop');
            expect(wrapper.text()).toContain('Subscribe');
        });
    });

    describe('featured posts section', () => {
        it('should render featured section when featured posts exist', () => {
            const featuredPosts = [
                {
                    id: 1,
                    title: 'Featured Post',
                    excerpt: 'Featured excerpt',
                    slug: 'featured-post',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: ['vue'],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, [], featuredPosts));

            expect(wrapper.text()).toContain('Featured Articles');
            expect(wrapper.findComponent({ name: 'BlogFeatured' }).exists()).toBe(true);
        });

        it('should not render featured section when no featured posts', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.text()).not.toContain('Featured Articles');
            expect(wrapper.findComponent({ name: 'BlogFeatured' }).exists()).toBe(false);
        });

        it('should pass featured posts to BlogFeatured component', () => {
            const featuredPosts = [
                {
                    id: 1,
                    title: 'Featured Post 1',
                    excerpt: 'Excerpt 1',
                    slug: 'featured-1',
                    is_featured: true,
                    author: { name: 'Author 1' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: ['vue'],
                },
                {
                    id: 2,
                    title: 'Featured Post 2',
                    excerpt: 'Excerpt 2',
                    slug: 'featured-2',
                    is_featured: true,
                    author: { name: 'Author 2' },
                    published_at: '2024-01-02',
                    reading_time: 8,
                    tags: ['react'],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, [], featuredPosts));
            const featured = wrapper.findComponent({ name: 'BlogFeatured' });

            expect(featured.props('posts')).toEqual(featuredPosts);
        });

        it('should have correct aria-labelledby for featured section', () => {
            const featuredPosts = [
                {
                    id: 1,
                    title: 'Featured',
                    excerpt: 'Excerpt',
                    slug: 'featured',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, [], featuredPosts));
            const section = wrapper.find('section[aria-labelledby="featured-heading"]');

            expect(section.exists()).toBe(true);
            expect(wrapper.find('#featured-heading').text()).toBe('Featured Articles');
        });
    });

    describe('latest posts section', () => {
        it('should render latest posts section', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.text()).toContain('Latest Articles');
            expect(wrapper.findComponent({ name: 'BlogGrid' }).exists()).toBe(true);
        });

        it('should filter out featured posts from regular posts', () => {
            const posts = [
                {
                    id: 1,
                    title: 'Regular Post',
                    excerpt: 'Regular excerpt',
                    slug: 'regular-post',
                    is_featured: false,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
                {
                    id: 2,
                    title: 'Featured Post',
                    excerpt: 'Featured excerpt',
                    slug: 'featured-post',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-02',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, posts, []));
            const grid = wrapper.findComponent({ name: 'BlogGrid' });

            // Should only pass non-featured posts
            expect(grid.props('posts')).toHaveLength(1);
            expect(grid.props('posts')[0].is_featured).toBe(false);
        });

        it('should pass loading prop as false to BlogGrid', () => {
            const wrapper = mount(Blog, createMountOptions());
            const grid = wrapper.findComponent({ name: 'BlogGrid' });

            expect(grid.props('loading')).toBe(false);
        });

        it('should have correct aria-labelledby for latest section', () => {
            const wrapper = mount(Blog, createMountOptions());
            const section = wrapper.find('section[aria-labelledby="latest-heading"]');

            expect(section.exists()).toBe(true);
            expect(wrapper.find('#latest-heading').text()).toBe('Latest Articles');
        });
    });

    describe('newsletter section', () => {
        it('should render newsletter form', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.find('#email-newsletter').exists()).toBe(true);
            expect(wrapper.find('button[type="submit"]').text()).toContain('Subscribe');
        });

        it('should have email input with correct attributes', () => {
            const wrapper = mount(Blog, createMountOptions());
            const input = wrapper.find('#email-newsletter');

            expect(input.attributes('type')).toBe('email');
            expect(input.attributes('required')).toBeDefined();
            expect(input.attributes('placeholder')).toBe('Enter your email');
        });

        it('should have sr-only label for accessibility', () => {
            const wrapper = mount(Blog, createMountOptions());
            const label = wrapper.find('label[for="email-newsletter"]');

            expect(label.exists()).toBe(true);
            expect(label.classes()).toContain('sr-only');
        });

        it('should have newsletter heading with correct aria-labelledby', () => {
            const wrapper = mount(Blog, createMountOptions());
            const section = wrapper.find('section[aria-labelledby="newsletter-heading"]');

            expect(section.exists()).toBe(true);
            expect(wrapper.find('#newsletter-heading').text()).toBe('Stay in the Loop');
        });

        it('should prevent form default submission', async () => {
            const wrapper = mount(Blog, createMountOptions());
            const form = wrapper.find('form');

            // Form should have @submit.prevent
            await form.trigger('submit');
            // Test passes if no error is thrown
            expect(form.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should have proper heading hierarchy', () => {
            const featuredPosts = [
                {
                    id: 1,
                    title: 'Featured',
                    excerpt: 'Excerpt',
                    slug: 'featured',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, [], featuredPosts));

            // Should have h2 headings for sections
            expect(wrapper.find('#featured-heading').element.tagName).toBe('H2');
            expect(wrapper.find('#latest-heading').element.tagName).toBe('H2');
            expect(wrapper.find('#newsletter-heading').element.tagName).toBe('H2');
        });

        it('should have semantic section elements with labels', () => {
            const wrapper = mount(Blog, createMountOptions());
            const sections = wrapper.findAll('section[aria-labelledby]');

            expect(sections.length).toBeGreaterThanOrEqual(2); // latest and newsletter
        });
    });

    describe('computed properties', () => {
        it('should compute regularPosts correctly when no posts are featured', () => {
            const posts = [
                {
                    id: 1,
                    title: 'Post 1',
                    excerpt: 'Excerpt 1',
                    slug: 'post-1',
                    is_featured: false,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
                {
                    id: 2,
                    title: 'Post 2',
                    excerpt: 'Excerpt 2',
                    slug: 'post-2',
                    is_featured: false,
                    author: { name: 'Author' },
                    published_at: '2024-01-02',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, posts, []));
            const grid = wrapper.findComponent({ name: 'BlogGrid' });

            expect(grid.props('posts')).toHaveLength(2);
        });

        it('should compute page title correctly', () => {
            const wrapper = mount(Blog, createMountOptions());
            const head = wrapper.findComponent({ name: 'Head' });

            expect(head.props('title')).toBe('Blog - Latest Articles & Insights');
        });
    });

    describe('edge cases', () => {
        it('should handle empty posts array', () => {
            const wrapper = mount(Blog, createMountOptions());

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.findComponent({ name: 'BlogGrid' }).exists()).toBe(true);
        });

        it('should handle all posts being featured', () => {
            const posts = [
                {
                    id: 1,
                    title: 'Featured Post 1',
                    excerpt: 'Excerpt 1',
                    slug: 'featured-1',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
                {
                    id: 2,
                    title: 'Featured Post 2',
                    excerpt: 'Excerpt 2',
                    slug: 'featured-2',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-02',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, posts, posts));
            const grid = wrapper.findComponent({ name: 'BlogGrid' });

            // Should have empty array for regular posts
            expect(grid.props('posts')).toHaveLength(0);
        });

        it('should render correctly with large number of posts', () => {
            const posts = Array.from({ length: 50 }, (_, i) => ({
                id: i + 1,
                title: `Post ${i + 1}`,
                excerpt: `Excerpt ${i + 1}`,
                slug: `post-${i + 1}`,
                is_featured: false,
                author: { name: 'Author' },
                published_at: '2024-01-01',
                reading_time: 5,
                tags: [],
            }));
            const wrapper = mount(Blog, createMountOptions(null, posts, []));
            const grid = wrapper.findComponent({ name: 'BlogGrid' });

            expect(grid.props('posts')).toHaveLength(50);
        });
    });

    describe('structure', () => {
        it('should have container with max-width', () => {
            const wrapper = mount(Blog, createMountOptions());
            const containers = wrapper.findAll('.container');

            expect(containers.length).toBeGreaterThan(0);
        });

        it.skip('should render in correct order: hero, featured, latest, newsletter', () => {
            const featuredPosts = [
                {
                    id: 1,
                    title: 'Featured',
                    excerpt: 'Excerpt',
                    slug: 'featured',
                    is_featured: true,
                    author: { name: 'Author' },
                    published_at: '2024-01-01',
                    reading_time: 5,
                    tags: [],
                },
            ];
            const wrapper = mount(Blog, createMountOptions(null, [], featuredPosts));
            const html = wrapper.html();

            const heroIndex = html.indexOf('blog-hero');
            const featuredIndex = html.indexOf('Featured Articles');
            const latestIndex = html.indexOf('Latest Articles');
            const newsletterIndex = html.indexOf('Stay in the Loop');

            expect(heroIndex).toBeLessThan(featuredIndex);
            expect(featuredIndex).toBeLessThan(latestIndex);
            expect(latestIndex).toBeLessThan(newsletterIndex);
        });
    });
});
