import Show from '@/pages/Admin/BlogPosts/Show.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head">{{ title }}</div>', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href" :class="classValue"><slot /></a>',
        props: ['href', 'class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

// Mock AppLayout
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div data-testid="app-layout"><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

// Mock MarkdownRender
vi.mock('@/components/MarkdownRender.vue', () => ({
    default: {
        name: 'MarkdownRender',
        template: '<div class="markdown-render">{{ content }}</div>',
        props: ['content'],
    },
}));

const createWrapper = (props: any) => mount(Show, props as any);

describe('Show Page', () => {
    const mockPost = {
        id: 1,
        title: 'Test Blog Post',
        slug: 'test-blog-post',
        excerpt: 'This is a test excerpt for the blog post.',
        content: '# Test Content\n\nThis is the main content of the blog post.',
        featured_image: 'https://example.com/image.jpg',
        tags: ['javascript', 'vue', 'testing'],
        is_featured: true,
        is_published: true,
        published_at: '2024-01-15T10:30:00Z',
        reading_time: 5,
        created_at: '2024-01-10T09:00:00Z',
        updated_at: '2024-01-14T15:30:00Z',
        author: {
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
        },
    };

    const mockDraftPost = {
        ...mockPost,
        is_published: false,
        published_at: null,
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toBe('Test Blog Post');
        });

        it('should render within AppLayout', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.exists()).toBe(true);
        });

        it('should pass breadcrumbs to layout', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const layout = wrapper.findComponent({ name: 'AppLayout' });
            expect(layout.props('breadcrumbs')).toBeDefined();
            expect(layout.props('breadcrumbs')).toHaveLength(3);
        });

        it('should render post title', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Test Blog Post');
        });

        it('should render author name', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('By John Doe');
        });

        it('should render reading time', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('5 min read');
        });
    });

    describe('status badge', () => {
        it('should render published status for published posts', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Published');
            const publishedBadges = wrapper.findAll('.bg-green-100');
            expect(publishedBadges.length).toBeGreaterThan(0);
        });

        it('should render draft status for draft posts', () => {
            const wrapper = createWrapper({
                props: { post: mockDraftPost },
            });

            expect(wrapper.text()).toContain('Draft');
            const draftBadges = wrapper.findAll('.bg-yellow-100');
            expect(draftBadges.length).toBeGreaterThan(0);
        });

        it('should render featured badge for featured posts', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Featured');
            const featuredBadges = wrapper.findAll('.bg-blue-100');
            expect(featuredBadges.length).toBeGreaterThan(0);
        });

        it('should not render featured badge for non-featured posts', () => {
            const nonFeaturedPost = { ...mockPost, is_featured: false };
            const wrapper = createWrapper({
                props: { post: nonFeaturedPost },
            });

            const text = wrapper.text();
            const featuredCount = (text.match(/Featured/g) || []).length;
            expect(featuredCount).toBe(0);
        });
    });

    describe('action buttons', () => {
        it('should render edit button', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const editLink = links.find(l => l.props('href') === '/admin/blog-posts/1/edit');
            expect(editLink).toBeTruthy();
            expect(editLink?.text()).toContain('Edit');
        });

        it('should render back to posts button', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const backLink = links.find(l => l.props('href') === '/admin/blog-posts');
            expect(backLink).toBeTruthy();
            expect(backLink?.text()).toContain('Back to Posts');
        });
    });

    describe('tags', () => {
        it('should render all tags', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('javascript');
            expect(wrapper.text()).toContain('vue');
            expect(wrapper.text()).toContain('testing');
        });

        it('should render tags with proper styling', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const tags = wrapper.findAll('.bg-muted');
            expect(tags.length).toBeGreaterThan(0);
        });

        it('should not render tags section if no tags', () => {
            const postWithoutTags = { ...mockPost, tags: [] };
            const wrapper = createWrapper({
                props: { post: postWithoutTags },
            });

            const tags = wrapper.findAll('.bg-muted.text-muted-foreground.rounded-full');
            expect(tags.length).toBe(0);
        });
    });

    describe('featured image', () => {
        it('should render featured image when present', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe('https://example.com/image.jpg');
            expect(img.attributes('alt')).toBe('Test Blog Post');
        });

        it('should not render featured image when not present', () => {
            const postWithoutImage = { ...mockPost, featured_image: null };
            const wrapper = createWrapper({
                props: { post: postWithoutImage },
            });

            const img = wrapper.find('img');
            expect(img.exists()).toBe(false);
        });
    });

    describe('content sections', () => {
        it('should render excerpt section', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Excerpt');
            expect(wrapper.text()).toContain('This is a test excerpt for the blog post.');
        });

        it('should render content section', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Content');
            const markdownRender = wrapper.findComponent({ name: 'MarkdownRender' });
            expect(markdownRender.exists()).toBe(true);
            expect(markdownRender.props('content')).toBe(mockPost.content);
        });
    });

    describe('metadata section', () => {
        it('should render post information heading', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Post Information');
        });

        it('should render slug', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Slug:');
            expect(wrapper.text()).toContain('test-blog-post');
        });

        it('should render author', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Author:');
            expect(wrapper.text()).toContain('John Doe');
        });

        it('should render created date', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Created:');
        });

        it('should render updated date when different from created', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Updated:');
        });

        it('should not render updated date when same as created', () => {
            const postWithSameDate = {
                ...mockPost,
                updated_at: mockPost.created_at,
            };
            const wrapper = createWrapper({
                props: { post: postWithSameDate },
            });

            const text = wrapper.text();
            const updatedMatches = text.match(/Updated:/g);
            expect(updatedMatches).toBeNull();
        });

        it('should render published date for published posts', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Published:');
        });

        it('should not render published date for draft posts', () => {
            const wrapper = createWrapper({
                props: { post: mockDraftPost },
            });

            const text = wrapper.text();
            // Should still have "Published" from the badge, but not "Published:" from metadata
            const publishedColonMatches = text.match(/Published:/g);
            expect(publishedColonMatches).toBeNull();
        });

        it('should render reading time', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.text()).toContain('Reading Time:');
            expect(wrapper.text()).toContain('5 minutes');
        });
    });

    describe('live post link', () => {
        it('should render view live post link for published posts', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const liveLink = links.find(l => l.props('href') === '/blog/test-blog-post');
            expect(liveLink).toBeTruthy();
            expect(liveLink?.text()).toContain('View Live Post');
        });

        it('should not render view live post link for draft posts', () => {
            const wrapper = createWrapper({
                props: { post: mockDraftPost },
            });

            const links = wrapper.findAllComponents({ name: 'Link' });
            const liveLink = links.find(l => l.props('href')?.includes('/blog/'));
            expect(liveLink).toBeFalsy();
        });
    });

    describe('date formatting', () => {
        it('should format dates correctly', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const vm = wrapper.vm as any;
            const formatted = vm.formatDate('2024-01-15T10:30:00Z');
            expect(formatted).toContain('January');
            expect(formatted).toContain('2024');
        });
    });

    describe('status info computed property', () => {
        it('should return published info for published posts', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            const vm = wrapper.vm as any;
            expect(vm.statusInfo.text).toBe('Published');
            expect(vm.statusInfo.class).toContain('bg-green-100');
        });

        it('should return draft info for draft posts', () => {
            const wrapper = createWrapper({
                props: { post: mockDraftPost },
            });

            const vm = wrapper.vm as any;
            expect(vm.statusInfo.text).toBe('Draft');
            expect(vm.statusInfo.class).toContain('bg-yellow-100');
        });
    });

    describe('props', () => {
        it('should accept post prop', () => {
            const wrapper = createWrapper({
                props: { post: mockPost },
            });

            expect(wrapper.props('post')).toEqual(mockPost);
        });
    });
});
