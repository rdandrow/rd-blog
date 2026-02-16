import SidebarHeader from '@/components/ui/sidebar/SidebarHeader.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarHeader', () => {
    describe('rendering', () => {
        it('should render as div element', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.element.tagName).toBe('DIV');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarHeader, {
                slots: {
                    default: '<h2>My Header</h2>',
                },
            });

            expect(wrapper.html()).toContain('<h2>My Header</h2>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-header');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.attributes('data-sidebar')).toBe('header');
        });
    });

    describe('styling', () => {
        it('should have flex column layout', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('flex-col');
        });

        it('should have gap spacing', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.classes()).toContain('gap-2');
        });

        it('should have padding', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.classes()).toContain('p-2');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarHeader, {
                props: {
                    class: 'custom-class',
                },
            });

            expect(wrapper.classes()).toContain('custom-class');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarHeader, {
                props: {
                    class: 'bg-red-500',
                },
            });

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('bg-red-500');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarHeader);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarHeader, {
                props: {
                    class: 'test-class',
                },
            });

            expect(wrapper.classes()).toContain('test-class');
        });
    });
});
