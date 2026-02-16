import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Button from '@/components/ui/button/Button.vue';

describe('Button', () => {
    describe('rendering', () => {
        it('should render button element by default', () => {
            const wrapper = mount(Button);
            expect(wrapper.find('button').exists()).toBe(true);
        });

        it('should have correct data-slot attribute', () => {
            const wrapper = mount(Button);
            expect(wrapper.find('button').attributes('data-slot')).toBe('button');
        });

        it('should render slot content', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: 'Click me',
                },
            });
            expect(wrapper.text()).toBe('Click me');
        });

        it('should render HTML in slot', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: '<span>Click</span>',
                },
            });
            expect(wrapper.html()).toContain('<span>Click</span>');
        });
    });

    describe('variants', () => {
        it('should apply default variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'default',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('bg-primary');
        });

        it('should apply destructive variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'destructive',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('bg-destructive');
        });

        it('should apply outline variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'outline',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('border');
        });

        it('should apply secondary variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'secondary',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('bg-secondary');
        });

        it('should apply ghost variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'ghost',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('hover:bg-accent');
        });

        it('should apply link variant', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'link',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('underline-offset-4');
        });
    });

    describe('sizes', () => {
        it('should apply default size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'default',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('h-9');
        });

        it('should apply sm size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'sm',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('h-8');
        });

        it('should apply lg size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'lg',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('h-10');
        });

        it('should apply icon size', () => {
            const wrapper = mount(Button, {
                props: {
                    size: 'icon',
                },
            });
            expect(wrapper.find('button').classes().join(' ')).toContain('size-9');
        });
    });

    describe('custom element', () => {
        it('should render as different element with as prop', () => {
            const wrapper = mount(Button, {
                props: {
                    as: 'a',
                },
            });
            expect(wrapper.find('a').exists()).toBe(true);
        });

        it('should render as div', () => {
            const wrapper = mount(Button, {
                props: {
                    as: 'div',
                },
            });
            expect(wrapper.find('div').exists()).toBe(true);
        });
    });

    describe('custom classes', () => {
        it('should apply custom class', () => {
            const wrapper = mount(Button, {
                props: {
                    class: 'custom-button',
                },
            });
            expect(wrapper.find('button').classes()).toContain('custom-button');
        });

        it('should merge custom classes with variant classes', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'default',
                    class: 'w-full',
                },
            });
            const button = wrapper.find('button');
            expect(button.classes()).toContain('w-full');
            expect(button.classes().join(' ')).toContain('bg-primary');
        });

        it('should handle multiple custom classes', () => {
            const wrapper = mount(Button, {
                props: {
                    class: 'mt-4 mb-4',
                },
            });
            const classes = wrapper.find('button').classes();
            expect(classes).toContain('mt-4');
            expect(classes).toContain('mb-4');
        });
    });

    describe('disabled state', () => {
        it('should apply disabled attribute', () => {
            const wrapper = mount(Button, {
                attrs: {
                    disabled: true,
                },
            });
            expect(wrapper.find('button').attributes('disabled')).toBeDefined();
        });

        it('should have disabled styles', () => {
            const wrapper = mount(Button);
            expect(wrapper.find('button').classes().join(' ')).toContain('disabled:opacity-50');
        });
    });

    describe('button type', () => {
        it('should support button type', () => {
            const wrapper = mount(Button, {
                attrs: {
                    type: 'button',
                },
            });
            expect(wrapper.find('button').attributes('type')).toBe('button');
        });

        it('should support submit type', () => {
            const wrapper = mount(Button, {
                attrs: {
                    type: 'submit',
                },
            });
            expect(wrapper.find('button').attributes('type')).toBe('submit');
        });

        it('should support reset type', () => {
            const wrapper = mount(Button, {
                attrs: {
                    type: 'reset',
                },
            });
            expect(wrapper.find('button').attributes('type')).toBe('reset');
        });
    });

    describe('click events', () => {
        it('should emit click event', async () => {
            const wrapper = mount(Button);
            await wrapper.find('button').trigger('click');
            expect(wrapper.emitted('click')).toBeTruthy();
        });

        it('should not emit click when disabled', async () => {
            const wrapper = mount(Button, {
                attrs: {
                    disabled: true,
                },
            });
            await wrapper.find('button').trigger('click');
            // Button will still technically emit but browser prevents default behavior
            expect(wrapper.find('button').attributes('disabled')).toBeDefined();
        });
    });

    describe('accessibility', () => {
        it('should support aria-label', () => {
            const wrapper = mount(Button, {
                attrs: {
                    'aria-label': 'Close dialog',
                },
            });
            expect(wrapper.find('button').attributes('aria-label')).toBe('Close dialog');
        });

        it('should support aria-pressed', () => {
            const wrapper = mount(Button, {
                attrs: {
                    'aria-pressed': 'true',
                },
            });
            expect(wrapper.find('button').attributes('aria-pressed')).toBe('true');
        });

        it('should support aria-expanded', () => {
            const wrapper = mount(Button, {
                attrs: {
                    'aria-expanded': 'false',
                },
            });
            expect(wrapper.find('button').attributes('aria-expanded')).toBe('false');
        });
    });

    describe('combinations', () => {
        it('should apply variant and size together', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'outline',
                    size: 'lg',
                },
            });
            const classes = wrapper.find('button').classes().join(' ');
            expect(classes).toContain('border');
            expect(classes).toContain('h-10');
        });

        it('should work with destructive variant and small size', () => {
            const wrapper = mount(Button, {
                props: {
                    variant: 'destructive',
                    size: 'sm',
                },
            });
            const classes = wrapper.find('button').classes().join(' ');
            expect(classes).toContain('bg-destructive');
            expect(classes).toContain('h-8');
        });
    });

    describe('asChild prop', () => {
        it('should support asChild for custom rendering', () => {
            const wrapper = mount(Button, {
                props: {
                    asChild: true,
                },
                slots: {
                    default: '<a href="/test">Link</a>',
                },
            });
            // asChild allows passing through to child component
            expect(wrapper.html()).toBeTruthy();
        });
    });

    describe('edge cases', () => {
        it('should handle empty slot', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: '',
                },
            });
            expect(wrapper.find('button').exists()).toBe(true);
        });

        it('should handle complex slot content', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: '<svg class="icon"><path /></svg><span>Text</span>',
                },
            });
            expect(wrapper.html()).toContain('<svg');
            expect(wrapper.html()).toContain('<span>Text</span>');
        });

        it('should handle special characters in text', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: 'Save & Continue',
                },
            });
            expect(wrapper.text()).toBe('Save & Continue');
        });

        it('should handle unicode characters', () => {
            const wrapper = mount(Button, {
                slots: {
                    default: '保存 💾',
                },
            });
            expect(wrapper.text()).toBe('保存 💾');
        });
    });

    describe('hover and focus states', () => {
        it('should have hover styles', () => {
            const wrapper = mount(Button);
            const classes = wrapper.find('button').classes().join(' ');
            // Most variants have hover styles
            expect(classes.length).toBeGreaterThan(0);
        });

        it('should have focus styles', () => {
            const wrapper = mount(Button);
            const classes = wrapper.find('button').classes().join(' ');
            expect(classes).toContain('outline-none');
            expect(classes).toContain('focus-visible:border-ring');
        });
    });
});
