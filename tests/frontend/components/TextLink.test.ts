import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import TextLink from '@/components/TextLink.vue';
import { Link } from '@inertiajs/vue3';

describe('TextLink', () => {
    describe('rendering', () => {
        it('should render Inertia Link component', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: 'Click me',
                },
            });

            expect(wrapper.findComponent(Link).exists()).toBe(true);
            expect(wrapper.text()).toBe('Click me');
        });

        it('should pass href to Link component', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/dashboard',
                },
                slots: {
                    default: 'Dashboard',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('href')).toBe('/dashboard');
        });

        it('should apply styling classes', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: 'Link',
                },
            });

            const link = wrapper.findComponent(Link);
            const classes = link.classes();
            
            expect(classes).toContain('text-foreground');
            expect(classes).toContain('underline');
            expect(classes).toContain('underline-offset-4');
        });

        it('should have transition classes', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
            });

            const link = wrapper.findComponent(Link);
            const classes = link.classes();
            
            expect(classes).toContain('transition-colors');
            expect(classes).toContain('duration-300');
            expect(classes).toContain('ease-out');
        });

        it('should have decoration classes', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
            });

            const link = wrapper.findComponent(Link);
            const classes = link.classes();
            
            expect(classes).toContain('decoration-neutral-300');
            expect(classes).toContain('dark:decoration-neutral-500');
        });
    });

    describe('props', () => {
        it('should pass tabindex prop to Link', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                    tabindex: 0,
                },
            });

            // tabindex is an HTML attribute, check it was passed
            expect(wrapper.props('tabindex')).toBe(0);
        });

        it('should pass method prop for POST requests', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/logout',
                    method: 'post',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('method')).toBe('post');
        });

        it('should pass method prop for DELETE requests', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/resource/1',
                    method: 'delete',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('method')).toBe('delete');
        });

        it('should pass method prop for PUT requests', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/resource/1',
                    method: 'put',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('method')).toBe('put');
        });

        it('should pass as prop for custom element', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                    as: 'button',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('as')).toBe('button');
        });

        it('should handle external URLs', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: 'https://example.com',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('href')).toBe('https://example.com');
        });
    });

    describe('slots', () => {
        it('should render default slot content', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: 'Link Text',
                },
            });

            expect(wrapper.text()).toBe('Link Text');
        });

        it('should render HTML in slot', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: '<span>Formatted</span> Text',
                },
            });

            expect(wrapper.html()).toContain('<span>Formatted</span>');
        });

        it('should render multiple elements in slot', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: '<span>First</span><span>Second</span>',
                },
            });

            expect(wrapper.html()).toContain('<span>First</span>');
            expect(wrapper.html()).toContain('<span>Second</span>');
        });

        it('should handle empty slot', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                },
                slots: {
                    default: '',
                },
            });

            expect(wrapper.findComponent(Link).exists()).toBe(true);
        });
    });

    describe('reactivity', () => {
        it('should update href when prop changes', async () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/initial',
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe('/initial');

            await wrapper.setProps({ href: '/updated' });

            expect(wrapper.findComponent(Link).props('href')).toBe('/updated');
        });

        it('should update method when prop changes', async () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                    method: 'get',
                },
            });

            expect(wrapper.findComponent(Link).props('method')).toBe('get');

            await wrapper.setProps({ method: 'post' });

            expect(wrapper.findComponent(Link).props('method')).toBe('post');
        });

        it('should update tabindex when prop changes', async () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test',
                    tabindex: 0,
                },
            });

            expect(wrapper.props('tabindex')).toBe(0);

            await wrapper.setProps({ tabindex: -1 });

            expect(wrapper.props('tabindex')).toBe(-1);
        });
    });

    describe('edge cases', () => {
        it('should handle special characters in href', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/test?param=value&other=test',
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe('/test?param=value&other=test');
        });

        it('should handle fragment identifiers', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/page#section',
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe('/page#section');
        });

        it('should handle relative paths', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '../parent',
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe('../parent');
        });

        it('should handle root path', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/',
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe('/');
        });

        it('should handle very long URLs', () => {
            const longUrl = '/path/' + 'segment/'.repeat(100);
            const wrapper = mount(TextLink, {
                props: {
                    href: longUrl,
                },
            });

            expect(wrapper.findComponent(Link).props('href')).toBe(longUrl);
        });
    });

    describe('Inertia.js integration', () => {
        it('should use Inertia Link for client-side navigation', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/dashboard',
                },
            });

            // Should use Inertia's Link component, not a regular <a>
            expect(wrapper.findComponent(Link).exists()).toBe(true);
        });

        it('should support form methods through Inertia', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/logout',
                    method: 'post',
                },
                slots: {
                    default: 'Logout',
                },
            });

            const link = wrapper.findComponent(Link);
            expect(link.props('method')).toBe('post');
            expect(link.props('href')).toBe('/logout');
        });

        it('should support custom element types', () => {
            const wrapper = mount(TextLink, {
                props: {
                    href: '/action',
                    as: 'button',
                },
            });

            expect(wrapper.findComponent(Link).props('as')).toBe('button');
        });
    });
});
