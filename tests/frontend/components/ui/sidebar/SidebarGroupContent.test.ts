import SidebarGroupContent from '@/components/ui/sidebar/SidebarGroupContent.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarGroupContent', () => {
    describe('rendering', () => {
        it('should render as div element', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.element.tagName).toBe('DIV');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarGroupContent, {
                slots: {
                    default: '<nav>Navigation</nav>',
                },
            });

            expect(wrapper.html()).toContain('<nav>Navigation</nav>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-group-content');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.attributes('data-sidebar')).toBe('group-content');
        });
    });

    describe('styling', () => {
        it('should have full width', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.classes()).toContain('w-full');
        });

        it('should have small text size', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.classes()).toContain('text-sm');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarGroupContent, {
                props: {
                    class: 'custom-content',
                },
            });

            expect(wrapper.classes()).toContain('custom-content');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarGroupContent, {
                props: {
                    class: 'p-4',
                },
            });

            expect(wrapper.classes()).toContain('w-full');
            expect(wrapper.classes()).toContain('p-4');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarGroupContent);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarGroupContent, {
                props: {
                    class: 'content-class',
                },
            });

            expect(wrapper.classes()).toContain('content-class');
        });
    });
});
