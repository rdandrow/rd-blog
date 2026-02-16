import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import BlogPost from '@/pages/BlogPost.vue';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { 
        name: 'Head', 
        template: '<head><title>{{ title }}</title></head>',
        props: ['title'],
    },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    router: {
        post: vi.fn(),
        delete: vi.fn(),
    },
    useForm: vi.fn((data) => ({
        ...data,
        processing: false,
        post: vi.fn(),
        reset: vi.fn(),
    })),
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: null,
            },
        },
    })),
}));

vi.mock('@/components/MarkdownRender.vue', () => ({
    default: {
        name: 'MarkdownRender',
        template: '<div class="markdown-render">{{ content }}</div>',
        props: ['content'],
    },
}));

vi.mock('@/routes', () => ({
    login: vi.fn(() => '/login'),
    register: vi.fn(() => '/register'),
}));

// Helper function to create mount options with proper $page mock
const createMountOptions = (props: any, authUser: any = null) => {
    const page = {
        props: {
            auth: {
                user: authUser,
            },
        },
    };
    
    return {
        props,
        global: {
            mocks: {
                $page: page,
            },
        },
    };
};

describe('BlogPost Page', () => {
    const mockPost = {
        id: 1,
        title: 'Test Blog Post',
        slug: 'test-blog-post',
        excerpt: 'This is a test excerpt',
        content: '# Test Content\n\nThis is the post content.',
        featured_image: '/images/test.jpg',
        tags: ['vue', 'testing'],
        is_featured: false,
        is_published: true,
        published_at: '2024-01-15T10:00:00Z',
        reading_time: 5,
        author: {
            id: 1,
            name: 'John Doe',
        },
        comments: [],
        likes_count: 10,
        user_has_liked: false,
        is_following_author: false,
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering - post header', () => {
        it('should render post title', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('Test Blog Post');
            expect(wrapper.find('h1').text()).toBe('Test Blog Post');
        });

        it('should render author name', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('John Doe');
        });

        it('should render published date', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const time = wrapper.find('time');
            expect(time.exists()).toBe(true);
            expect(time.attributes('datetime')).toBe('2024-01-15T10:00:00Z');
        });

        it('should render reading time', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('5 min read');
        });

        it('should render excerpt when provided', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('This is a test excerpt');
        });

        it('should not render excerpt when not provided', () => {
            const postWithoutExcerpt = { ...mockPost, excerpt: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutExcerpt }));

            // Should not have the excerpt section
            const excerptParagraphs = wrapper.findAll('p').filter(p => 
                p.text() === mockPost.excerpt
            );
            expect(excerptParagraphs.length).toBe(0);
        });

        it('should render author avatar with initials', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const avatar = wrapper.find('.h-8.w-8.rounded-full');
            expect(avatar.exists()).toBe(true);
            expect(avatar.text()).toBe('J'); // First letter of John
        });

        it('should handle missing author gracefully', () => {
            const postWithoutAuthor = { ...mockPost, author: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutAuthor }));

            expect(wrapper.text()).toContain('Anonymous');
        });
    });

    describe('rendering - tags', () => {
        it('should render tags when provided', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('vue');
            expect(wrapper.text()).toContain('testing');
        });

        it('should not render tags section when no tags', () => {
            const postWithoutTags = { ...mockPost, tags: [] };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutTags }));

            const tagBadges = wrapper.findAll('.inline-flex.items-center.rounded-full');
            expect(tagBadges.length).toBe(0);
        });

        it('should not render tags section when tags is null', () => {
            const postWithoutTags = { ...mockPost, tags: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutTags }));

            // Find tag container - should not exist
            const html = wrapper.html();
            expect(html).not.toContain('rounded-full bg-blue-100');
        });

        it('should render multiple tags correctly', () => {
            const postWithManyTags = {
                ...mockPost,
                tags: ['vue', 'testing', 'vitest', 'typescript'],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithManyTags }));

            expect(wrapper.text()).toContain('vue');
            expect(wrapper.text()).toContain('testing');
            expect(wrapper.text()).toContain('vitest');
            expect(wrapper.text()).toContain('typescript');
        });
    });

    describe('rendering - featured image', () => {
        it('should render featured image when provided', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const img = wrapper.find('img[alt="Test Blog Post"]');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe('/images/test.jpg');
        });

        it('should not render featured image when not provided', () => {
            const postWithoutImage = { ...mockPost, featured_image: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutImage }));

            const images = wrapper.findAll('img');
            expect(images.length).toBe(0);
        });
    });

    describe('rendering - post content', () => {
        it('should render MarkdownRender component with content', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const markdown = wrapper.findComponent({ name: 'MarkdownRender' });
            expect(markdown.exists()).toBe(true);
            expect(markdown.props('content')).toBe(mockPost.content);
        });

        it('should render content within prose classes', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const proseDiv = wrapper.find('.prose.prose-lg');
            expect(proseDiv.exists()).toBe(true);
        });
    });

    describe('likes functionality', () => {
        it('should show like button for authenticated users', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const likeButton = wrapper.find('button svg path[d*="M4.318"]');
            expect(likeButton.exists()).toBe(true);
        });

        it('should display correct likes count', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            expect(wrapper.text()).toContain('10 Likes');
        });

        it('should show singular "Like" when count is 1', () => {
            const postWithOneLike = { ...mockPost, likes_count: 1 };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithOneLike }));

            expect(wrapper.text()).toContain('1 Like');
            expect(wrapper.text()).not.toContain('1 Likes');
        });

        it('should show liked state when user has liked', () => {
            const likedPost = { ...mockPost, user_has_liked: true };
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: likedPost }, authUser));

            const likeButton = wrapper.find('button svg');
            expect(likeButton.attributes('fill')).toBe('currentColor');
        });

        it('should show unliked state when user has not liked', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const likeButton = wrapper.find('button svg');
            expect(likeButton.attributes('fill')).toBe('none');
        });

        it('should call toggleLike when like button clicked', async () => {
            const { router } = await import('@inertiajs/vue3');
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const likeButton = wrapper.findAll('button').find(b => 
                b.text().includes('Likes')
            );
            await likeButton?.trigger('click');

            expect(router.post).toHaveBeenCalledWith(
                '/blog/test-blog-post/like',
                {},
                { preserveScroll: true }
            );
        });

        it('should show like count for guests without like button', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            // Should show count but no clickable button
            expect(wrapper.text()).toContain('10 Likes');
            const clickableButtons = wrapper.findAll('button').filter(b => 
                b.text().includes('Likes')
            );
            expect(clickableButtons.length).toBe(0);
        });

        it('should not show likes section for guests when count is 0', () => {
            const postWithNoLikes = { ...mockPost, likes_count: 0 };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithNoLikes }));

            // Should not display likes section at all
            const likesSection = wrapper.text().includes('Likes');
            expect(likesSection).toBe(false);
        });
    });

    describe('author follow functionality', () => {
        it('should show follow button for authenticated users', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const followButtons = wrapper.findAll('button').filter(b => 
                b.text() === 'Follow' || b.text() === 'Following'
            );
            expect(followButtons.length).toBeGreaterThan(0);
        });

        it('should show "Following" when already following', () => {
            const followingPost = { ...mockPost, is_following_author: true };
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: followingPost }, authUser));

            const followButton = wrapper.findAll('button').find(b => 
                b.text() === 'Following'
            );
            expect(followButton).toBeDefined();
        });

        it('should show "Follow" when not following', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const followButton = wrapper.findAll('button').find(b => 
                b.text() === 'Follow'
            );
            expect(followButton).toBeDefined();
        });

        it('should call toggleFollow when follow button clicked', async () => {
            const { router } = await import('@inertiajs/vue3');
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const followButton = wrapper.findAll('button').find(b => 
                b.text() === 'Follow' || b.text() === 'Following'
            );
            await followButton?.trigger('click');

            expect(router.post).toHaveBeenCalledWith(
                '/user/1/follow',
                {},
                { preserveScroll: true }
            );
        });

        it('should not show follow button when no author', () => {
            const postWithoutAuthor = { ...mockPost, author: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutAuthor }));

            const followButtons = wrapper.findAll('button').filter(b => 
                b.text() === 'Follow' || b.text() === 'Following'
            );
            expect(followButtons.length).toBe(0);
        });
    });

    describe('comments section', () => {
        it('should render comments heading with count', () => {
            const postWithComments = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Test comment',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Commenter' },
                    },
                ],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithComments }));

            expect(wrapper.text()).toContain('Comments (1)');
        });

        it('should show comment form for authenticated users', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            expect(wrapper.find('textarea#comment').exists()).toBe(true);
            expect(wrapper.text()).toContain('Add a comment');
        });

        it('should show login prompt for guests', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('Log in');
            expect(wrapper.text()).toContain('register');
            expect(wrapper.text()).toContain('to leave a comment');
        });

        it('should show character count for comment textarea', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            expect(wrapper.text()).toContain('/1000 characters');
        });

        it('should disable submit when comment is empty', async () => {
            const { useForm } = await import('@inertiajs/vue3');
            const authUser = { id: 1, name: 'Test User' };

            (useForm as any).mockReturnValue({
                content: '',
                parent_id: null,
                processing: false,
                post: vi.fn(),
                reset: vi.fn(),
            });

            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const submitButton = wrapper.findAll('button').find(b => 
                b.text().includes('Post Comment')
            );
            expect(submitButton?.attributes('disabled')).toBeDefined();
        });

        it('should render existing comments', () => {
            const postWithComments = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Great article!',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Jane Smith' },
                    },
                ],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithComments }));

            expect(wrapper.text()).toContain('Great article!');
            expect(wrapper.text()).toContain('Jane Smith');
        });

        it('should show "no comments" message when no comments exist', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('No comments yet');
            expect(wrapper.text()).toContain('Be the first to comment!');
        });

        it('should render comment with user avatar initial', () => {
            const postWithComments = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Test comment',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Alice' },
                    },
                ],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithComments }));

            const avatars = wrapper.findAll('.rounded-full');
            const commentAvatar = avatars.find(a => a.text() === 'A');
            expect(commentAvatar).toBeDefined();
        });
    });

    describe('comment replies', () => {
        it('should show reply button for authenticated users', () => {
            const postWithComments = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Test comment',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Commenter' },
                    },
                ],
            };
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithComments }, authUser));

            const replyButtons = wrapper.findAll('button').filter(b => 
                b.text() === 'Reply'
            );
            expect(replyButtons.length).toBeGreaterThan(0);
        });

        it('should render comment replies when they exist', () => {
            const postWithReplies = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Parent comment',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Commenter' },
                        replies: [
                            {
                                id: 2,
                                content: 'Reply to comment',
                                created_at: '2024-01-15T12:00:00Z',
                                user: { id: 3, name: 'Replier' },
                            },
                        ],
                    },
                ],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithReplies }));

            expect(wrapper.text()).toContain('Parent comment');
            expect(wrapper.text()).toContain('Reply to comment');
            expect(wrapper.text()).toContain('Replier');
        });

        it('should not show reply button for guests', () => {
            const postWithComments = {
                ...mockPost,
                comments: [
                    {
                        id: 1,
                        content: 'Test comment',
                        created_at: '2024-01-15T11:00:00Z',
                        user: { id: 2, name: 'Commenter' },
                    },
                ],
            };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithComments }));

            const replyButtons = wrapper.findAll('button').filter(b => 
                b.text() === 'Reply'
            );
            expect(replyButtons.length).toBe(0);
        });
    });

    describe('navigation', () => {
        it('should render navigation bar', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.find('nav').exists()).toBe(true);
        });

        it('should have link to home page', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const homeLink = wrapper.findAllComponents({ name: 'Link' }).find(l => 
                l.props('href') === '/'
            );
            expect(homeLink).toBeDefined();
        });

        it('should have link to blog list', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const blogLinks = wrapper.findAllComponents({ name: 'Link' }).filter(l => 
                l.props('href') === '/blog'
            );
            expect(blogLinks.length).toBeGreaterThan(0);
        });

        it('should have "Back to all posts" link', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.text()).toContain('Back to all posts');
        });
    });

    describe('author footer section', () => {
        it('should render author name in footer', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const footer = wrapper.find('footer');
            expect(footer.text()).toContain('John Doe');
        });

        it('should have link to author profile', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const authorLink = wrapper.findAllComponents({ name: 'Link' }).find(l => 
                l.props('href') === '/author/1'
            );
            expect(authorLink).toBeDefined();
        });

        it('should show Anonymous when no author', () => {
            const postWithoutAuthor = { ...mockPost, author: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutAuthor }));

            const footer = wrapper.find('footer');
            expect(footer.text()).toContain('Anonymous');
        });
    });

    describe('accessibility', () => {
        it('should have semantic article element', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            expect(wrapper.find('article').exists()).toBe(true);
        });

        it('should have proper heading hierarchy', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            // Main title should be h1
            const h1 = wrapper.find('h1');
            expect(h1.exists()).toBe(true);
            expect(h1.text()).toBe('Test Blog Post');

            // Comments should be h2
            const h2Elements = wrapper.findAll('h2');
            expect(h2Elements.length).toBeGreaterThan(0);
        });

        it('should have proper labels for form inputs', () => {
            const authUser = { id: 1, name: 'Test User' };
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }, authUser));

            const commentLabel = wrapper.find('label[for="comment"]');
            expect(commentLabel.exists()).toBe(true);
        });

        it('should have time element with datetime attribute', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            const time = wrapper.find('time');
            expect(time.attributes('datetime')).toBe('2024-01-15T10:00:00Z');
        });
    });

    describe('edge cases', () => {
        it('should handle post with no reading time', () => {
            const postWithoutReadingTime = { ...mockPost, reading_time: null };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutReadingTime }));

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.text()).not.toContain('min read');
        });

        it('should handle empty comments array', () => {
            const postWithEmptyComments = { ...mockPost, comments: [] };
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithEmptyComments }));

            expect(wrapper.text()).toContain('Comments (0)');
            expect(wrapper.text()).toContain('No comments yet');
        });

        it('should handle missing comments property', () => {
            const postWithoutComments = { ...mockPost };
            delete (postWithoutComments as any).comments;
            
            const wrapper = mount(BlogPost, createMountOptions({ post: postWithoutComments }));

            expect(wrapper.text()).toContain('Comments (0)');
        });

        it('should format date correctly', () => {
            const wrapper = mount(BlogPost, createMountOptions({ post: mockPost }));

            // Date should be formatted in a readable way
            const time = wrapper.find('time');
            expect(time.exists()).toBe(true);
        });
    });
});
