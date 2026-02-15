import SidebarGroupLabel from '@/components/ui/sidebar/SidebarGroupLabel.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock Primitive component from reka-ui
vi.mock('reka-ui', () => ({
    Primitive: {
        name: 'Primitive',
        template: '<component :is="as || \'div\'" :class="classValue"><slot /></component>',
        props: ['as', 'asChild', 'class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

describe('SidebarGroupLabel', () => {
    describe('rendering', () => {
        it('should render Primitive component', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.exists()).toBe(true);
        });

        it('should render slot content', () => {
            const wrapper = mount(SidebarGroupLabel, {
                slots: {
                    default: 'Navigation',
                },
            });

            expect(wrapper.text()).toBe('Navigation');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.attributes('data-slot')).toBe('sidebar-group-label');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.attributes('data-sidebar')).toBe('group-label');
        });
    });

    describe('styling', () => {
        it('should have flex layout', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('flex');
            expect(primitive.props('class')).toContain('items-center');
        });

        it('should have height constraint', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('h-8');
        });

        it('should shrink when needed', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('shrink-0');
        });

        it('should have rounded corners', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('rounded-md');
        });

        it('should have padding', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('px-2');
        });

        it('should have text styling', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('text-xs');
            expect(primitive.props('class')).toContain('font-medium');
        });

        it('should have transition effects', () => {
            const wrapper = mount(SidebarGroupLabel);

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('duration-200');
            expect(primitive.props('class')).toContain('ease-linear');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarGroupLabel, {
                props: {
                    class: 'custom-label',
                },
            });

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('custom-label');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarGroupLabel, {
                props: {
                    class: 'text-blue-500',
                },
            });

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('flex');
            expect(primitive.props('class')).toContain('text-blue-500');
        });
    });

    describe('props', () => {
        it('should work without props', () => {
            const wrapper = mount(SidebarGroupLabel);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarGroupLabel, {
                props: {
                    class: 'label-class',
                },
            });

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('class')).toContain('label-class');
        });

        it('should accept as prop', () => {
            const wrapper = mount(SidebarGroupLabel, {
                props: {
                    as: 'h3',
                },
            });

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('as')).toBe('h3');
        });

        it('should accept asChild prop', () => {
            const wrapper = mount(SidebarGroupLabel, {
                props: {
                    asChild: true,
                },
            });

            const primitive = wrapper.findComponent({ name: 'Primitive' });
            expect(primitive.props('asChild')).toBe(true);
        });
    });
});
