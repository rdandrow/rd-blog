import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

describe('AppLogoIcon', () => {
    describe('rendering', () => {
        it('should render as SVG element', () => {
            const wrapper = mount(AppLogoIcon);

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should have correct viewBox', () => {
            const wrapper = mount(AppLogoIcon);

            const svg = wrapper.find('svg');
            expect(svg.attributes('viewBox')).toBe('0 0 40 42');
        });

        it('should have xmlns attribute', () => {
            const wrapper = mount(AppLogoIcon);

            const svg = wrapper.find('svg');
            expect(svg.attributes('xmlns')).toBe('http://www.w3.org/2000/svg');
        });

        it('should render path element', () => {
            const wrapper = mount(AppLogoIcon);

            expect(wrapper.find('path').exists()).toBe(true);
        });

        it('should have currentColor fill', () => {
            const wrapper = mount(AppLogoIcon);

            const path = wrapper.find('path');
            expect(path.attributes('fill')).toBe('currentColor');
        });

        it('should apply custom className prop', () => {
            const wrapper = mount(AppLogoIcon, {
                props: {
                    className: 'custom-class text-blue-500',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('custom-class');
            expect(svg.classes()).toContain('text-blue-500');
        });

        it('should not apply className when not provided', () => {
            const wrapper = mount(AppLogoIcon);

            const svg = wrapper.find('svg');
            expect(svg.attributes('class')).toBeFalsy();
        });
    });

    describe('props', () => {
        it('should accept and apply className prop', () => {
            const wrapper = mount(AppLogoIcon, {
                props: {
                    className: 'size-10',
                },
            });

            expect(wrapper.find('svg').classes()).toContain('size-10');
        });

        it('should handle multiple classes in className', () => {
            const wrapper = mount(AppLogoIcon, {
                props: {
                    className: 'size-8 fill-current text-red-500',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('size-8');
            expect(svg.classes()).toContain('fill-current');
            expect(svg.classes()).toContain('text-red-500');
        });
    });

    describe('reactivity', () => {
        it('should update className when prop changes', async () => {
            const wrapper = mount(AppLogoIcon, {
                props: {
                    className: 'size-4',
                },
            });

            expect(wrapper.find('svg').classes()).toContain('size-4');

            await wrapper.setProps({ className: 'size-8' });

            expect(wrapper.find('svg').classes()).toContain('size-8');
            expect(wrapper.find('svg').classes()).not.toContain('size-4');
        });
    });

    describe('attributes', () => {
        it('should inherit additional attributes', () => {
            const wrapper = mount(AppLogoIcon, {
                attrs: {
                    'data-testid': 'logo-icon',
                    'aria-label': 'Logo',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('data-testid')).toBe('logo-icon');
            expect(svg.attributes('aria-label')).toBe('Logo');
        });

        it('should support custom inline styles', () => {
            const wrapper = mount(AppLogoIcon, {
                attrs: {
                    style: 'width: 50px; height: 50px;',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('style')).toContain('width');
        });
    });

    describe('edge cases', () => {
        it('should handle empty className gracefully', () => {
            const wrapper = mount(AppLogoIcon, {
                props: {
                    className: '',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });
    });
});
