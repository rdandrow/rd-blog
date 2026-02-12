import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Icon from '@/components/Icon.vue';

describe('Icon', () => {
    describe('rendering', () => {
        it('should render icon by name', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should handle capitalized icon names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'user',
                },
            });

            // Should convert 'user' to 'User' and find the icon
            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should handle multi-word icon names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'alertCircle',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should apply default size classes', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('h-4');
            expect(svg.classes()).toContain('w-4');
        });

        it('should apply custom class names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'custom-class text-red-500',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('custom-class');
            expect(svg.classes()).toContain('text-red-500');
        });

        it('should preserve default classes when custom class is added', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'custom-class',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('h-4');
            expect(svg.classes()).toContain('w-4');
            expect(svg.classes()).toContain('custom-class');
        });
    });

    describe('props', () => {
        it('should apply custom size', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    size: 24,
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('width')).toBe('24');
            expect(svg.attributes('height')).toBe('24');
        });

        it('should apply size as string', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    size: '32',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('width')).toBe('32');
            expect(svg.attributes('height')).toBe('32');
        });

        it('should apply custom stroke width', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    strokeWidth: 3,
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('stroke-width')).toBe('3');
        });

        it('should use default stroke width of 2', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('stroke-width')).toBe('2');
        });

        it('should apply custom color', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    color: 'red',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('stroke')).toBe('red');
        });

        it('should apply hex color', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    color: '#ff0000',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.attributes('stroke')).toBe('#ff0000');
        });
    });

    describe('icon name transformation', () => {
        it('should capitalize first letter', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should handle already capitalized names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'Home',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should handle camelCase icon names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'checkCircle',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should handle single character icon names', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'x',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });
    });

    describe('reactivity', () => {
        it('should update icon when name changes', async () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);

            await wrapper.setProps({ name: 'user' });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should update size when prop changes', async () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    size: 16,
                },
            });

            expect(wrapper.find('svg').attributes('width')).toBe('16');
            expect(wrapper.find('svg').attributes('height')).toBe('16');

            await wrapper.setProps({ size: 24 });

            expect(wrapper.find('svg').attributes('width')).toBe('24');
            expect(wrapper.find('svg').attributes('height')).toBe('24');
        });

        it('should update class when prop changes', async () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'text-blue-500',
                },
            });

            expect(wrapper.find('svg').classes()).toContain('text-blue-500');

            await wrapper.setProps({ class: 'text-red-500' });

            expect(wrapper.find('svg').classes()).toContain('text-red-500');
            expect(wrapper.find('svg').classes()).not.toContain('text-blue-500');
        });
    });

    describe('edge cases', () => {
        it('should handle empty string class gracefully', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: '',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
            expect(wrapper.find('svg').classes()).toContain('h-4');
        });

        it('should handle multiple custom classes', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'text-blue-500 hover:text-blue-700 dark:text-blue-300',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('text-blue-500');
            expect(svg.classes()).toContain('hover:text-blue-700');
            expect(svg.classes()).toContain('dark:text-blue-300');
        });

        it('should fallback to default size when size is 0', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    size: 0,
                },
            });

            expect(wrapper.find('svg').attributes('width')).toBe('24');
            expect(wrapper.find('svg').attributes('height')).toBe('24');
        });

        it('should handle very large sizes', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    size: 1000,
                },
            });

            expect(wrapper.find('svg').attributes('width')).toBe('1000');
            expect(wrapper.find('svg').attributes('height')).toBe('1000');
        });

        it('should handle fractional stroke widths', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    strokeWidth: 1.5,
                },
            });

            expect(wrapper.find('svg').attributes('stroke-width')).toBe('1.5');
        });
    });

    describe('computed className', () => {
        it('should merge default and custom classes', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'ml-2 mr-2',
                },
            });

            const svg = wrapper.find('svg');
            expect(svg.classes()).toContain('h-4');
            expect(svg.classes()).toContain('w-4');
            expect(svg.classes()).toContain('ml-2');
            expect(svg.classes()).toContain('mr-2');
        });

        it('should handle cn utility merging', () => {
            const wrapper = mount(Icon, {
                props: {
                    name: 'home',
                    class: 'h-8 w-8', // Should override h-4 w-4
                },
            });

            const svg = wrapper.find('svg');
            // cn utility should properly merge these
            expect(svg.classes()).toContain('h-8');
            expect(svg.classes()).toContain('w-8');
        });
    });
});
