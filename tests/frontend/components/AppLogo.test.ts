import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AppLogo from '@/components/AppLogo.vue';

describe('AppLogo', () => {
    describe('rendering', () => {
        it('should render logo icon', () => {
            const wrapper = mount(AppLogo);

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should render company name', () => {
            const wrapper = mount(AppLogo);

            expect(wrapper.text()).toContain('Laravel Starter Kit');
        });

        it('should have correct container styling', () => {
            const wrapper = mount(AppLogo);

            const container = wrapper.find('.flex.aspect-square');
            expect(container.exists()).toBe(true);
            expect(container.classes()).toContain('size-8');
            expect(container.classes()).toContain('rounded-md');
        });

        it('should have correct text styling', () => {
            const wrapper = mount(AppLogo);

            const textContainer = wrapper.find('.ml-1.grid');
            expect(textContainer.exists()).toBe(true);
            expect(textContainer.classes()).toContain('flex-1');
            expect(textContainer.classes()).toContain('text-left');
        });

        it('should render text with proper styles', () => {
            const wrapper = mount(AppLogo);

            const text = wrapper.find('.truncate.leading-tight');
            expect(text.exists()).toBe(true);
            expect(text.classes()).toContain('font-semibold');
        });
    });

    describe('structure', () => {
        it('should have icon before text', () => {
            const wrapper = mount(AppLogo);
            const html = wrapper.html();

            const svgIndex = html.indexOf('<svg');
            const textIndex = html.indexOf('Laravel Starter Kit');

            expect(svgIndex).toBeLessThan(textIndex);
        });

        it('should wrap content properly', () => {
            const wrapper = mount(AppLogo);

            // Should have icon container
            expect(wrapper.find('.flex.aspect-square').exists()).toBe(true);
            
            // Should have text container
            expect(wrapper.find('.ml-1.grid').exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should render text content for screen readers', () => {
            const wrapper = mount(AppLogo);

            expect(wrapper.text()).toContain('Laravel Starter Kit');
        });
    });
});
