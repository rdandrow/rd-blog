import SidebarTrigger from '@/components/ui/sidebar/SidebarTrigger.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock useSidebar composable
const mockToggleSidebar = vi.fn();
vi.mock('@/components/ui/sidebar/utils', () => ({
    useSidebar: () => ({
        toggleSidebar: mockToggleSidebar,
        state: { value: 'expanded' },
        open: { value: true },
        setOpen: vi.fn(),
        isMobile: { value: false },
        openMobile: { value: false },
        setOpenMobile: vi.fn(),
    }),
}));

// Mock Button component
vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :variant="variant" :size="size" :class="classValue" @click="$emit(\'click\')"><slot /></button>',
        props: ['variant', 'size', 'class'],
        emits: ['click'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

// Mock lucide-vue-next
vi.mock('lucide-vue-next', () => ({
    PanelLeft: {
        name: 'PanelLeft',
        template: '<svg data-testid="panel-left-icon"></svg>',
    },
}));

describe('SidebarTrigger', () => {
    describe('rendering', () => {
        it('should render Button component', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
        });

        it('should render PanelLeft icon', () => {
            const wrapper = mount(SidebarTrigger);

            const icon = wrapper.find('[data-testid="panel-left-icon"]');
            expect(icon.exists()).toBe(true);
        });

        it('should have screen reader only text', () => {
            const wrapper = mount(SidebarTrigger);

            const srText = wrapper.find('.sr-only');
            expect(srText.exists()).toBe(true);
            expect(srText.text()).toBe('Toggle Sidebar');
        });

        it('should have data-sidebar attribute', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.attributes('data-sidebar')).toBe('trigger');
        });

        it('should have data-slot attribute', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.attributes('data-slot')).toBe('sidebar-trigger');
        });
    });

    describe('button props', () => {
        it('should have ghost variant', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('variant')).toBe('ghost');
        });

        it('should have icon size', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('size')).toBe('icon');
        });

        it('should have default size classes', () => {
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('h-7');
            expect(button.props('class')).toContain('w-7');
        });

        it('should accept custom class', () => {
            const wrapper = mount(SidebarTrigger, {
                props: {
                    class: 'custom-trigger',
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('custom-trigger');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(SidebarTrigger, {
                props: {
                    class: 'bg-blue-500',
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('h-7');
            expect(button.props('class')).toContain('bg-blue-500');
        });
    });

    describe('interactions', () => {
        it('should call toggleSidebar on click', async () => {
            mockToggleSidebar.mockClear();
            const wrapper = mount(SidebarTrigger);

            const button = wrapper.findComponent({ name: 'Button' });
            await button.trigger('click');

            expect(mockToggleSidebar).toHaveBeenCalledTimes(1);
        });

        it('should toggle sidebar when button is clicked', async () => {
            mockToggleSidebar.mockClear();
            const wrapper = mount(SidebarTrigger);

            await wrapper.find('button').trigger('click');

            expect(mockToggleSidebar).toHaveBeenCalled();
        });
    });

    describe('accessibility', () => {
        it('should have accessible label', () => {
            const wrapper = mount(SidebarTrigger);

            const srText = wrapper.find('.sr-only');
            expect(srText.text()).toBe('Toggle Sidebar');
        });

        it('should hide label visually but keep for screen readers', () => {
            const wrapper = mount(SidebarTrigger);

            const srText = wrapper.find('.sr-only');
            expect(srText.classes()).toContain('sr-only');
        });
    });

    describe('props', () => {
        it('should work without class prop', () => {
            const wrapper = mount(SidebarTrigger);

            expect(wrapper.exists()).toBe(true);
        });

        it('should accept class prop', () => {
            const wrapper = mount(SidebarTrigger, {
                props: {
                    class: 'trigger-class',
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('trigger-class');
        });
    });
});
