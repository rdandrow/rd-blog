import SidebarMenu from '@/components/ui/sidebar/SidebarMenu.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarMenu', () => {
    describe('rendering', () => {
        it('should render as ul element', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.element.tagName).toBe('UL');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarMenu, {
                slots: {
                    default: '<li>Menu item</li>',
                },
            });

            expect(wrapper.html()).toContain('<li>Menu item</li>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-menu');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.attributes('data-sidebar')).toBe('menu');
        });
    });

    describe('styling', () => {
        it('should have flex layout', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('flex-col');
        });

        it('should have full width', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.classes()).toContain('w-full');
        });

        it('should have min-w-0', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.classes()).toContain('min-w-0');
        });

        it('should have gap spacing', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.classes()).toContain('gap-1');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarMenu, {
                props: {
                    class: 'custom-menu',
                },
            });

            expect(wrapper.classes()).toContain('custom-menu');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarMenu, {
                props: {
                    class: 'space-y-2',
                },
            });

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('space-y-2');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarMenu);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarMenu, {
                props: {
                    class: 'menu-class',
                },
            });

            expect(wrapper.classes()).toContain('menu-class');
        });
    });
});
