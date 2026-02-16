import SidebarMenuSubItem from '@/components/ui/sidebar/SidebarMenuSubItem.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarMenuSubItem', () => {
    describe('rendering', () => {
        it('should render as li element', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.element.tagName).toBe('LI');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarMenuSubItem, {
                slots: {
                    default: '<a href="#">Submenu Item</a>',
                },
            });

            expect(wrapper.html()).toContain('<a href="#">Submenu Item</a>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-menu-sub-item');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.attributes('data-sidebar')).toBe('menu-sub-item');
        });
    });

    describe('styling', () => {
        it('should have group class', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.classes()).toContain('group/menu-sub-item');
        });

        it('should have relative positioning', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.classes()).toContain('relative');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarMenuSubItem, {
                props: {
                    class: 'custom-subitem',
                },
            });

            expect(wrapper.classes()).toContain('custom-subitem');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarMenuSubItem, {
                props: {
                    class: 'hover:bg-gray-50',
                },
            });

            expect(wrapper.classes()).toContain('group/menu-sub-item');
            expect(wrapper.classes()).toContain('hover:bg-gray-50');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarMenuSubItem);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarMenuSubItem, {
                props: {
                    class: 'subitem-class',
                },
            });

            expect(wrapper.classes()).toContain('subitem-class');
        });
    });
});
