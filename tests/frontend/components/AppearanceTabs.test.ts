import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';

// Mock the composable
const mockUpdateAppearance = vi.fn();
const mockAppearance = ref('system');

vi.mock('@/composables/useAppearance', () => ({
    useAppearance: vi.fn(() => ({
        appearance: mockAppearance,
        updateAppearance: mockUpdateAppearance,
    })),
}));

// Mock lucide icons
vi.mock('lucide-vue-next', () => ({
    Sun: { name: 'Sun', template: '<svg data-testid="sun-icon"></svg>' },
    Moon: { name: 'Moon', template: '<svg data-testid="moon-icon"></svg>' },
    Monitor: { name: 'Monitor', template: '<svg data-testid="monitor-icon"></svg>' },
}));

describe('AppearanceTabs', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockAppearance.value = 'system';
    });

    describe('rendering', () => {
        it('should render all three appearance options', () => {
            const wrapper = mount(AppearanceTabs);

            expect(wrapper.text()).toContain('Light');
            expect(wrapper.text()).toContain('Dark');
            expect(wrapper.text()).toContain('System');
        });

        it('should render icons for each option', () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            expect(buttons).toHaveLength(3);
        });

        it('should render buttons in correct order', () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            expect(buttons[0].text()).toContain('Light');
            expect(buttons[1].text()).toContain('Dark');
            expect(buttons[2].text()).toContain('System');
        });
    });

    describe('active state', () => {
        it('should highlight light mode when active', () => {
            mockAppearance.value = 'light';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            const lightButton = buttons[0];
            
            expect(lightButton.classes()).toContain('bg-white');
            expect(lightButton.classes()).toContain('shadow-xs');
        });

        it('should highlight dark mode when active', () => {
            mockAppearance.value = 'dark';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            const darkButton = buttons[1];
            
            expect(darkButton.classes()).toContain('bg-white');
            expect(darkButton.classes()).toContain('shadow-xs');
        });

        it('should highlight system mode when active', () => {
            mockAppearance.value = 'system';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            const systemButton = buttons[2];
            
            expect(systemButton.classes()).toContain('bg-white');
            expect(systemButton.classes()).toContain('shadow-xs');
        });

        it('should not highlight inactive buttons', () => {
            mockAppearance.value = 'light';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            const darkButton = buttons[1];
            const systemButton = buttons[2];
            
            expect(darkButton.classes()).not.toContain('bg-white');
            expect(systemButton.classes()).not.toContain('bg-white');
        });

        it('should apply hover styles to inactive buttons', () => {
            mockAppearance.value = 'light';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            const darkButton = buttons[1];
            
            expect(darkButton.classes()).toContain('text-neutral-500');
            expect(darkButton.classes()).toContain('hover:bg-neutral-200/60');
        });
    });

    describe('interactions', () => {
        it('should call updateAppearance with light when light button clicked', async () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            await buttons[0].trigger('click');

            expect(mockUpdateAppearance).toHaveBeenCalledWith('light');
            expect(mockUpdateAppearance).toHaveBeenCalledTimes(1);
        });

        it('should call updateAppearance with dark when dark button clicked', async () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            await buttons[1].trigger('click');

            expect(mockUpdateAppearance).toHaveBeenCalledWith('dark');
            expect(mockUpdateAppearance).toHaveBeenCalledTimes(1);
        });

        it('should call updateAppearance with system when system button clicked', async () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            await buttons[2].trigger('click');

            expect(mockUpdateAppearance).toHaveBeenCalledWith('system');
            expect(mockUpdateAppearance).toHaveBeenCalledTimes(1);
        });

        it('should allow clicking the same option multiple times', async () => {
            mockAppearance.value = 'light';
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            await buttons[0].trigger('click');
            await buttons[0].trigger('click');

            expect(mockUpdateAppearance).toHaveBeenCalledTimes(2);
        });
    });

    describe('accessibility', () => {
        it('should use button elements for keyboard navigation', () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            expect(buttons).toHaveLength(3);
            buttons.forEach(button => {
                expect(button.element.tagName).toBe('BUTTON');
            });
        });

        it('should have text labels for screen readers', () => {
            const wrapper = mount(AppearanceTabs);

            expect(wrapper.text()).toContain('Light');
            expect(wrapper.text()).toContain('Dark');
            expect(wrapper.text()).toContain('System');
        });

        it('should pair icons with text labels', () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            buttons.forEach(button => {
                // Each button should have both an icon component and text
                expect(button.find('span').exists()).toBe(true);
            });
        });
    });

    describe('styling', () => {
        it('should have rounded container with padding', () => {
            const wrapper = mount(AppearanceTabs);

            const container = wrapper.find('.rounded-lg');
            expect(container.exists()).toBe(true);
            expect(container.classes()).toContain('p-1');
        });

        it('should have gap between buttons', () => {
            const wrapper = mount(AppearanceTabs);

            const container = wrapper.find('.gap-1');
            expect(container.exists()).toBe(true);
        });

        it('should apply transition classes to buttons', () => {
            const wrapper = mount(AppearanceTabs);

            const buttons = wrapper.findAll('button');
            buttons.forEach(button => {
                expect(button.classes()).toContain('transition-colors');
            });
        });

        it('should have dark mode classes', () => {
            const wrapper = mount(AppearanceTabs);

            const container = wrapper.find('.dark\\:bg-neutral-800');
            expect(container.exists()).toBe(true);
        });
    });
});
