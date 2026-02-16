import SidebarFooter from '@/components/ui/sidebar/SidebarFooter.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('SidebarFooter', () => {
    describe('rendering', () => {
        it('should render as div element', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.element.tagName).toBe('DIV');
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarFooter, {
                slots: {
                    default: '<p>Footer content</p>',
                },
            });

            expect(wrapper.html()).toContain('<p>Footer content</p>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.attributes('data-slot')).toBe('sidebar-footer');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.attributes('data-sidebar')).toBe('footer');
        });
    });

    describe('styling', () => {
        it('should have flex column layout', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('flex-col');
        });

        it('should have gap spacing', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.classes()).toContain('gap-2');
        });

        it('should have padding', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.classes()).toContain('p-2');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarFooter, {
                props: {
                    class: 'custom-footer',
                },
            });

            expect(wrapper.classes()).toContain('custom-footer');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarFooter, {
                props: {
                    class: 'bg-blue-500',
                },
            });

            expect(wrapper.classes()).toContain('flex');
            expect(wrapper.classes()).toContain('bg-blue-500');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarFooter);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarFooter, {
                props: {
                    class: 'footer-class',
                },
            });

            expect(wrapper.classes()).toContain('footer-class');
        });
    });
});
