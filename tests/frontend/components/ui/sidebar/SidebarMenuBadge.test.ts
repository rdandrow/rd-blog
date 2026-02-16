import SidebarMenuBadge from '@/components/ui/sidebar/SidebarMenuBadge.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarMenuBadge', () => {
    describe('rendering', () => {
        it('should render as div element', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.element.tagName).toBe('DIV');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarMenuBadge, {
                slots: {
                    default: '5',
                },
            });

            expect(wrapper.text()).toBe('5');
        });

        it('should render text content', () => {
            const wrapper = mount(SidebarMenuBadge, {
                slots: {
                    default: 'New',
                },
            });

            expect(wrapper.text()).toBe('New');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-menu-badge');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.attributes('data-sidebar')).toBe('menu-badge');
        });
    });

    describe('styling', () => {
        it('should have absolute positioning', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('absolute');
            expect(wrapper.classes()).toContain('right-1');
        });

        it('should be pointer-events-none', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('pointer-events-none');
        });

        it('should have flex layout', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('items-center');
            expect(wrapper.classes()).toContain('justify-center');
        });

        it('should have size constraints', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('h-5');
            expect(wrapper.classes()).toContain('min-w-5');
        });

        it('should have rounded corners', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('rounded-md');
        });

        it('should have padding', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('px-1');
        });

        it('should have text styling', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('text-xs');
            expect(wrapper.classes()).toContain('font-medium');
        });

        it('should be non-selectable', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.classes()).toContain('select-none');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarMenuBadge, {
                props: {
                    class: 'bg-red-500',
                },
            });

            expect(wrapper.classes()).toContain('bg-red-500');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarMenuBadge, {
                props: {
                    class: 'custom-badge',
                },
            });

            expect(wrapper.classes()).toContain('absolute');
            expect(wrapper.classes()).toContain('custom-badge');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarMenuBadge);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarMenuBadge, {
                props: {
                    class: 'badge-class',
                },
            });

            expect(wrapper.classes()).toContain('badge-class');
        });
    });
});
