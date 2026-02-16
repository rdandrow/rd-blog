import SidebarSeparator from '@/components/ui/sidebar/SidebarSeparator.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock Separator component
vi.mock('@/components/ui/separator', () => ({
    Separator: {
        name: 'Separator',
        template: '<div :class="classValue"><slot /></div>',
        props: ['class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

describe('SidebarSeparator', () => {
    describe('rendering', () => {
        it('should render Separator component', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.exists()).toBe(true);
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarSeparator, {
                slots: {
                    default: '<span>Divider</span>',
                },
            });

            expect(wrapper.html()).toContain('<span>Divider</span>');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.attributes('data-slot')).toBe('sidebar-separator');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.attributes('data-sidebar')).toBe('separator');
        });
    });

    describe('styling', () => {
        it('should have border background color', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('bg-sidebar-border');
        });

        it('should have horizontal margins', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('mx-2');
        });

        it('should have auto width', () => {
            const wrapper = mount(SidebarSeparator);

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('w-auto');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarSeparator, {
                props: {
                    class: 'custom-separator',
                },
            });

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('custom-separator');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarSeparator, {
                props: {
                    class: 'my-4',
                },
            });

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('bg-sidebar-border');
            expect(separator.props('class')).toContain('my-4');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarSeparator);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarSeparator, {
                props: {
                    class: 'separator-class',
                },
            });

            const separator = wrapper.findComponent({ name: 'Separator' });
            expect(separator.props('class')).toContain('separator-class');
        });
    });
});
