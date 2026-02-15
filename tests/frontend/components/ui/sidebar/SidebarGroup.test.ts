import SidebarGroup from '@/components/ui/sidebar/SidebarGroup.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarGroup', () => {
    describe('rendering', () => {
        it('should render as div element', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.element.tagName).toBe('DIV');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarGroup, {
                slots: {
                    default: '<div>Group content</div>',
                },
            });

            expect(wrapper.html()).toContain('<div>Group content</div>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-group');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.attributes('data-sidebar')).toBe('group');
        });
    });

    describe('styling', () => {
        it('should have relative positioning', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.classes()).toContain('relative');
        });

        it('should have flex layout', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('flex-col');
        });

        it('should have full width', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.classes()).toContain('w-full');
        });

        it('should have min-w-0', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.classes()).toContain('min-w-0');
        });

        it('should have padding', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.classes()).toContain('p-2');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarGroup, {
                props: {
                    class: 'custom-group',
                },
            });

            expect(wrapper.classes()).toContain('custom-group');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarGroup, {
                props: {
                    class: 'bg-gray-50',
                },
            });

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('bg-gray-50');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarGroup);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarGroup, {
                props: {
                    class: 'group-class',
                },
            });

            expect(wrapper.classes()).toContain('group-class');
        });
    });
});
