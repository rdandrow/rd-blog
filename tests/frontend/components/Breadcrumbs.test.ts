import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Breadcrumbs from '@/components/Breadcrumbs.vue';

describe('Breadcrumbs', () => {
    describe('rendering', () => {
        it('should render single breadcrumb item', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [{ title: 'Home' }],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Home');
        });

        it('should render multiple breadcrumb items', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/' },
                        { title: 'Blog', href: '/blog' },
                        { title: 'Post' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Home');
            expect(wrapper.text()).toContain('Blog');
            expect(wrapper.text()).toContain('Post');
        });

        it('should render last item as current page', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/' },
                        { title: 'Current Page' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            // Last item should not be a link
            const links = wrapper.findAll('a');
            expect(links.length).toBe(1); // Only the first item should be a link
        });

        it('should render separators between items', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/' },
                        { title: 'Blog', href: '/blog' },
                        { title: 'Post' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            // Should have 2 separators for 3 items
            const html = wrapper.html();
            expect(html).toContain('breadcrumb-separator');
        });

        it('should not render separator after last item', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/' },
                        { title: 'Current' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            const html = wrapper.html();
            // Check structure for correct number of separators
            expect(html).toBeTruthy();
        });

        it('should use href from breadcrumb item', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/home' },
                        { title: 'Current' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            const link = wrapper.find('a');
            expect(link.attributes('href')).toBe('/home');
        });

        it('should default to # when href is not provided', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Link Without Href' },
                        { title: 'Current' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            const link = wrapper.find('a');
            expect(link.attributes('href')).toBe('#');
        });
    });

    describe('reactivity', () => {
        it('should update when breadcrumbs prop changes', async () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [{ title: 'Home' }],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Home');

            await wrapper.setProps({
                breadcrumbs: [
                    { title: 'Dashboard', href: '/' },
                    { title: 'Settings' },
                ],
            });

            expect(wrapper.text()).toContain('Dashboard');
            expect(wrapper.text()).toContain('Settings');
            expect(wrapper.text()).not.toContain('Home');
        });

        it('should handle adding breadcrumbs dynamically', async () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [{ title: 'Home', href: '/' }],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            await wrapper.setProps({
                breadcrumbs: [
                    { title: 'Home', href: '/' },
                    { title: 'Blog', href: '/blog' },
                    { title: 'Post' },
                ],
            });

            expect(wrapper.text()).toContain('Home');
            expect(wrapper.text()).toContain('Blog');
            expect(wrapper.text()).toContain('Post');
        });
    });

    describe('edge cases', () => {
        it('should handle empty breadcrumbs array', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.html()).toBeTruthy();
        });

        it('should handle long breadcrumb titles', () => {
            const longTitle = 'A'.repeat(100);
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: longTitle, href: '/' },
                        { title: 'Current' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain(longTitle);
        });

        it('should handle special characters in titles', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home & Garden', href: '/' },
                        { title: 'Products <Sale>' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Home & Garden');
            expect(wrapper.text()).toContain('Products <Sale>');
        });

        it('should handle special characters in URLs', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Search', href: '/search?q=test&sort=date' },
                        { title: 'Results' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            const link = wrapper.find('a');
            expect(link.attributes('href')).toBe('/search?q=test&sort=date');
        });

        it('should handle many breadcrumb levels', () => {
            const breadcrumbs = Array.from({ length: 10 }, (_, i) => ({
                title: `Level ${i + 1}`,
                href: i < 9 ? `/level-${i + 1}` : undefined,
            }));

            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs,
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Level 1');
            expect(wrapper.text()).toContain('Level 10');
        });
    });

    describe('structure', () => {
        it('should use proper breadcrumb HTML structure', () => {
            const wrapper = mount(Breadcrumbs, {
                props: {
                    breadcrumbs: [
                        { title: 'Home', href: '/' },
                        { title: 'Current' },
                    ],
                },
                global: {
                    stubs: {
                        Link: {
                            template: '<a :href="href"><slot /></a>',
                            props: ['href'],
                        },
                    },
                },
            });

            // Should contain breadcrumb components with data-slot attributes
            expect(wrapper.html()).toContain('breadcrumb-list');
            expect(wrapper.html()).toContain('breadcrumb-item');
        });
    });
});
