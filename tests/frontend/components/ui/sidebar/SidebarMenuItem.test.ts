import SidebarMenuItem from '@/components/ui/sidebar/SidebarMenuItem.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarMenuItem', () => {
    describe('rendering', () => {
        it('should render as li element', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.element.tagName).toBe('LI');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarMenuItem, {
                slots: {
                    default: '<a href="#">Menu Item</a>',
                },
            });

            expect(wrapper.html()).toContain('<a href="#">Menu Item</a>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-menu-item');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.attributes('data-sidebar')).toBe('menu-item');
        });
    });

    describe('styling', () => {
        it('should have group class', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.classes()).toContain('group/menu-item');
        });

        it('should have relative positioning', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.classes()).toContain('relative');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarMenuItem, {
                props: {
                    class: 'custom-item',
                },
            });

            expect(wrapper.classes()).toContain('custom-item');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarMenuItem, {
                props: {
                    class: 'hover:bg-gray-100',
                },
            });

            expect(wrapper.classes()).toContain('group/menu-item');
            expect(wrapper.classes()).toContain('hover:bg-gray-100');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarMenuItem);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarMenuItem, {
                props: {
                    class: 'menu-item-class',
                },
            });

            expect(wrapper.classes()).toContain('menu-item-class');
        });
    });
});
