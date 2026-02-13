import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AuthorProfile from '@/pages/AuthorProfile.vue';

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
    },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: null,
            },
        },
    })),
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

describe('AuthorProfile Page', () => {
    const mockAuthor = {
        id: 1,
        name: 'Jane Doe',
        email: 'jane@example.com',
        bio: 'Passionate writer and developer.\nLoves sharing knowledge.',
        website: 'https://janedoe.com',
        followers_count: 150,
        following_count: 75,
        posts_count: 25,
    };

    const mockPosts = [
        {
            id: 1,
            title: 'First Blog Post',
            slug: 'first-blog-post',
            excerpt: 'This is the first post excerpt',
            featured_image: '/images/post1.jpg',
            published_at: '2024-01-15T10:00:00Z',
            reading_time: 5,
        },
        {
            id: 2,
            title: 'Second Blog Post',
            slug: 'second-blog-post',
            excerpt: 'This is the second post excerpt',
            featured_image: null,
            published_at: '2024-01-20T10:00:00Z',
            reading_time: 8,
        },
    ];

    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering - author header', () => {
        it('should render author name', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                author: mockAuthor,
                posts: [],
                is_following: false
            }));

            expect(wrapper.text()).toContain('Jane Doe');
            expect(wrapper.find('h1').text()).toBe('Jane Doe');
        });

        it('should render author avatar with initial', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const avatar = wrapper.find('.h-24.w-24.rounded-full');
            expect(avatar.exists()).toBe(true);
            expect(avatar.text()).toBe('J'); // First letter of Jane
        });

        it('should render follower count', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('150 Followers');
        });

        it('should render singular "Follower" when count is 1', () => {
            const authorWithOneFollower = { ...mockAuthor, followers_count: 1 };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: authorWithOneFollower,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('1 Follower');
            expect(wrapper.text()).not.toContain('1 Followers');
        });

        it('should render following count', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('75 Following');
        });

        it('should render posts count', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('25 Posts');
        });

        it('should render singular "Post" when count is 1', () => {
            const authorWithOnePost = { ...mockAuthor, posts_count: 1 };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: authorWithOnePost,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('1 Post');
            expect(wrapper.text()).not.toContain('1 Posts');
        });

        it('should render author bio when provided', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('Passionate writer and developer');
            expect(wrapper.text()).toContain('Loves sharing knowledge');
        });

        it('should not render bio section when bio is null', () => {
            const authorWithoutBio = { ...mockAuthor, bio: null };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: authorWithoutBio,
                    posts: [],
                    is_following: false
                }));

            const bioParagraphs = wrapper.findAll('p').filter(p => 
                p.classes().includes('whitespace-pre-line')
            );
            expect(bioParagraphs.length).toBe(0);
        });

        it('should render website link when provided', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const websiteLink = wrapper.find('a[href="https://janedoe.com"]');
            expect(websiteLink.exists()).toBe(true);
            expect(websiteLink.text()).toContain('janedoe.com');
        });

        it('should not render website section when website is null', () => {
            const authorWithoutWebsite = { ...mockAuthor, website: null };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: authorWithoutWebsite,
                    posts: [],
                    is_following: false
                }));

            const websiteLinks = wrapper.findAll('a[target="_blank"]');
            expect(websiteLinks.length).toBe(0);
        });

        it('should have rel attributes for security on website link', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const websiteLink = wrapper.find('a[href="https://janedoe.com"]');
            expect(websiteLink.attributes('target')).toBe('_blank');
            expect(websiteLink.attributes('rel')).toBe('noopener noreferrer');
        });
    });

    describe('follow functionality', () => {
        it('should show follow button for authenticated users', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }, { id: 2, name: 'Other User' }));

            const followButton = wrapper.find('button');
            expect(followButton.exists()).toBe(true);
            expect(followButton.text()).toBe('Follow');
        });

        it('should show "Following" when already following', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: true
                }, { id: 2, name: 'Other User' }));

            const followButton = wrapper.find('button');
            expect(followButton.text()).toBe('Following');
        });

        it('should not show follow button for guests', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const followButtons = wrapper.findAll('button');
            expect(followButtons.length).toBe(0);
        });

        it('should call toggleFollow when button clicked', async () => {
            const { router } = await import('@inertiajs/vue3');

            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }, { id: 2, name: 'Other User' }));

            const followButton = wrapper.find('button');
            await followButton.trigger('click');

            expect(router.post).toHaveBeenCalledWith(
                '/user/1/follow',
                {},
                { preserveScroll: true }
            );
        });
    });

    describe('posts section', () => {
        it('should render "Recent Articles" heading', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            expect(wrapper.text()).toContain('Recent Articles');
            expect(wrapper.find('h2').text()).toBe('Recent Articles');
        });

        it('should render posts when available', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            expect(wrapper.text()).toContain('First Blog Post');
            expect(wrapper.text()).toContain('Second Blog Post');
        });

        it('should render post excerpts', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            expect(wrapper.text()).toContain('This is the first post excerpt');
            expect(wrapper.text()).toContain('This is the second post excerpt');
        });

        it('should render post featured images when available', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const images = wrapper.findAll('img');
            const postImage = images.find(img => 
                img.attributes('src') === '/images/post1.jpg'
            );
            expect(postImage).toBeDefined();
        });

        it('should not render image section when featured_image is null', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            // Second post has no featured image
            const images = wrapper.findAll('img');
            expect(images.length).toBe(1); // Only first post's image
        });

        it('should render published dates', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const times = wrapper.findAll('time');
            expect(times.length).toBeGreaterThanOrEqual(2);
            expect(times[0].attributes('datetime')).toBe('2024-01-15T10:00:00Z');
        });

        it('should render reading times', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            expect(wrapper.text()).toContain('5 min read');
            expect(wrapper.text()).toContain('8 min read');
        });

        it('should have links to individual posts', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const postLinks = wrapper.findAllComponents({ name: 'Link' }).filter(l => 
                l.props('href')?.includes('/blog/')
            );
            expect(postLinks.length).toBe(2);
        });

        it('should show empty state when no posts', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('No published articles yet');
        });

        it('should handle null posts array', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: null as any,
                    is_following: false
                }));

            expect(wrapper.text()).toContain('No published articles yet');
        });

        it('should wrap post titles correctly', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const postTitles = wrapper.findAll('h3');
            expect(postTitles.length).toBe(2);
            expect(postTitles[0].text()).toBe('First Blog Post');
        });

        it('should use article semantic element for posts', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const articles = wrapper.findAll('article');
            expect(articles.length).toBe(2);
        });
    });

    describe('navigation', () => {
        it('should render navigation bar', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.find('nav').exists()).toBe(true);
        });

        it('should have link to home page', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const homeLink = wrapper.findAllComponents({ name: 'Link' }).find(l => 
                l.props('href') === '/'
            );
            expect(homeLink).toBeDefined();
        });

        it('should have link to blog list', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const blogLink = wrapper.findAllComponents({ name: 'Link' }).find(l => 
                l.props('href') === '/blog'
            );
            expect(blogLink).toBeDefined();
        });

        it('should have "All Posts" text in navigation', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const nav = wrapper.find('nav');
            expect(nav.text()).toContain('All Posts');
        });
    });

    describe('Head component', () => {
        it('should render correct page title', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.props('title')).toBe('Jane Doe - Author Profile');
        });

        it('should include author name in title', () => {
            const author = { ...mockAuthor, name: 'John Smith' };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author,
                    posts: [],
                    is_following: false
                }));

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.props('title')).toBe('John Smith - Author Profile');
        });
    });

    describe('accessibility', () => {
        it('should have proper heading hierarchy', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            // Author name should be h1
            const h1 = wrapper.find('h1');
            expect(h1.exists()).toBe(true);
            expect(h1.text()).toBe('Jane Doe');

            // Section heading should be h2
            const h2 = wrapper.find('h2');
            expect(h2.exists()).toBe(true);
            expect(h2.text()).toBe('Recent Articles');

            // Post titles should be h3
            const h3Elements = wrapper.findAll('h3');
            expect(h3Elements.length).toBe(2);
        });

        it('should have semantic article elements', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const articles = wrapper.findAll('article');
            expect(articles.length).toBe(2);
        });

        it('should have time elements with datetime attributes', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const times = wrapper.findAll('time');
            expect(times.length).toBeGreaterThanOrEqual(2);
            times.forEach(time => {
                expect(time.attributes('datetime')).toBeDefined();
            });
        });

        it('should have accessible website link with icon', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const websiteLink = wrapper.find('a[href="https://janedoe.com"]');
            expect(websiteLink.find('svg').exists()).toBe(true);
        });
    });

    describe('edge cases', () => {
        it('should handle author with no followers', () => {
            const author = { ...mockAuthor, followers_count: 0 };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('0 Followers');
        });

        it('should handle author with no posts', () => {
            const author = { ...mockAuthor, posts_count: 0 };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author,
                    posts: [],
                    is_following: false
                }));

            expect(wrapper.text()).toContain('0 Posts');
        });

        it('should handle post without reading time', () => {
            const postsWithoutReadingTime = [
                {
                    ...mockPosts[0],
                    reading_time: null,
                },
            ];

            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: postsWithoutReadingTime,
                    is_following: false
                }));

            expect(wrapper.exists()).toBe(true);
            // Should not crash, but reading time shouldn't be shown
        });

        it('should handle post without excerpt', () => {
            const postsWithoutExcerpt = [
                {
                    ...mockPosts[0],
                    excerpt: null,
                },
            ];

            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: postsWithoutExcerpt,
                    is_following: false
                }));

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.text()).toContain('First Blog Post');
        });

        it('should handle large number of posts', () => {
            const manyPosts = Array.from({ length: 50 }, (_, i) => ({
                id: i + 1,
                title: `Post ${i + 1}`,
                slug: `post-${i + 1}`,
                excerpt: `Excerpt ${i + 1}`,
                featured_image: null,
                published_at: '2024-01-15T10:00:00Z',
                reading_time: 5,
            }));

            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: manyPosts,
                    is_following: false
                }));

            const articles = wrapper.findAll('article');
            expect(articles.length).toBe(50);
        });

        it('should format date correctly', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            // Date should be formatted and visible
            const times = wrapper.findAll('time');
            expect(times.length).toBeGreaterThan(0);
        });

        it('should handle bio with multiple newlines', () => {
            const author = {
                ...mockAuthor,
                bio: 'Line 1\n\nLine 2\n\n\nLine 3',
            };

            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author,
                    posts: [],
                    is_following: false
                }));

            const bioParagraph = wrapper.find('.whitespace-pre-line');
            expect(bioParagraph.exists()).toBe(true);
            expect(bioParagraph.text()).toContain('Line 1');
            expect(bioParagraph.text()).toContain('Line 2');
            expect(bioParagraph.text()).toContain('Line 3');
        });

        it('should uppercase first letter of avatar initial', () => {
            const author = { ...mockAuthor, name: 'jane doe' };
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author,
                    posts: [],
                    is_following: false
                }));

            const avatar = wrapper.find('.h-24.w-24.rounded-full');
            expect(avatar.text()).toBe('J'); // Should be uppercase
        });

        it('should handle website URL with protocol', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const websiteLink = wrapper.find('a[href="https://janedoe.com"]');
            // Text should remove protocol
            expect(websiteLink.text()).toContain('janedoe.com');
            expect(websiteLink.text()).not.toContain('https://');
        });
    });

    describe('styling and layout', () => {
        it('should have gradient avatar background', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: [],
                    is_following: false
                }));

            const avatar = wrapper.find('.h-24.w-24.rounded-full');
            expect(avatar.classes()).toContain('bg-gradient-to-br');
        });

        it('should have border separator between posts', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const articles = wrapper.findAll('article');
            expect(articles[0].classes()).toContain('border-b');
        });

        it('should use line-clamp for excerpt truncation', () => {
            const wrapper = mount(AuthorProfile, createMountOptions({ 
                    author: mockAuthor,
                    posts: mockPosts,
                    is_following: false
                }));

            const excerptParagraphs = wrapper.findAll('p.line-clamp-2');
            expect(excerptParagraphs.length).toBeGreaterThan(0);
        });
    });
});
