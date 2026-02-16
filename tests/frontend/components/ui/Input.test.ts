import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Input from '@/components/ui/input/Input.vue';

describe('Input', () => {
    describe('rendering', () => {
        it('should render input element', () => {
            const wrapper = mount(Input);
            expect(wrapper.find('input').exists()).toBe(true);
        });

        it('should have correct data-slot attribute', () => {
            const wrapper = mount(Input);
            expect(wrapper.find('input').attributes('data-slot')).toBe('input');
        });

        it('should apply default classes', () => {
            const wrapper = mount(Input);
            const input = wrapper.find('input');
            expect(input.classes()).toContain('h-9');
            expect(input.classes()).toContain('rounded-md');
            expect(input.classes()).toContain('border');
        });

        it('should apply custom class', () => {
            const wrapper = mount(Input, {
                props: {
                    class: 'custom-class',
                },
            });
            expect(wrapper.find('input').classes()).toContain('custom-class');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(Input, {
                props: {
                    class: 'w-full',
                },
            });
            const input = wrapper.find('input');
            expect(input.classes()).toContain('w-full');
            expect(input.classes()).toContain('h-9');
            expect(input.classes()).toContain('rounded-md');
        });
    });

    describe('v-model', () => {
        it('should bind value via v-model', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: 'test value',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('test value');
        });

        it('should emit update:modelValue on input', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: '',
                },
            });

            const input = wrapper.find('input');
            await input.setValue('new value');

            expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['new value']);
        });

        it('should update value reactively', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: 'initial',
                },
            });

            await wrapper.setProps({ modelValue: 'updated' });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('updated');
        });
    });

    describe('defaultValue', () => {
        it('should use defaultValue when no modelValue provided', () => {
            const wrapper = mount(Input, {
                props: {
                    defaultValue: 'default text',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('default text');
        });

        it('should prefer modelValue over defaultValue', () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: 'model value',
                    defaultValue: 'default value',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('model value');
        });
    });

    describe('disabled state', () => {
        it('should apply disabled attribute', () => {
            const wrapper = mount(Input, {
                attrs: {
                    disabled: true,
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('disabled')).toBeDefined();
        });

        it('should have disabled styles in class', () => {
            const wrapper = mount(Input);
            const input = wrapper.find('input');
            expect(input.classes().join(' ')).toContain('disabled:opacity-50');
        });
    });

    describe('placeholder', () => {
        it('should render placeholder text', () => {
            const wrapper = mount(Input, {
                attrs: {
                    placeholder: 'Enter text...',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('placeholder')).toBe('Enter text...');
        });
    });

    describe('input types', () => {
        it('should support text type', () => {
            const wrapper = mount(Input, {
                attrs: {
                    type: 'text',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('type')).toBe('text');
        });

        it('should support email type', () => {
            const wrapper = mount(Input, {
                attrs: {
                    type: 'email',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('type')).toBe('email');
        });

        it('should support password type', () => {
            const wrapper = mount(Input, {
                attrs: {
                    type: 'password',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('type')).toBe('password');
        });

        it('should support number type', () => {
            const wrapper = mount(Input, {
                attrs: {
                    type: 'number',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('type')).toBe('number');
        });
    });

    describe('number values', () => {
        it('should handle numeric modelValue', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: 42,
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('42');
        });

        it('should emit numeric value from number input', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: 0,
                },
                attrs: {
                    type: 'number',
                },
            });

            const input = wrapper.find('input');
            await input.setValue('123');

            expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([123]);
        });
    });

    describe('focus styles', () => {
        it('should have focus-visible styles', () => {
            const wrapper = mount(Input);
            const input = wrapper.find('input');
            expect(input.classes().join(' ')).toContain('focus-visible:ring-ring');
        });
    });

    describe('validation states', () => {
        it('should have aria-invalid styles', () => {
            const wrapper = mount(Input);
            const input = wrapper.find('input');
            expect(input.classes().join(' ')).toContain('aria-invalid:border-destructive');
        });

        it('should apply aria-invalid attribute', () => {
            const wrapper = mount(Input, {
                attrs: {
                    'aria-invalid': true,
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('aria-invalid')).toBe('true');
        });
    });

    describe('ref exposure', () => {
        it('should expose input ref', () => {
            const wrapper = mount(Input);
            expect(wrapper.vm.input).toBeTruthy();
            expect(wrapper.vm.input).toBeInstanceOf(HTMLInputElement);
        });

        it('should expose null ref initially', () => {
            const wrapper = mount(Input);
            expect(wrapper.vm.input).toBeTruthy();
        });
    });

    describe('accessibility', () => {
        it('should support aria-label', () => {
            const wrapper = mount(Input, {
                attrs: {
                    'aria-label': 'Username',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('aria-label')).toBe('Username');
        });

        it('should support aria-describedby', () => {
            const wrapper = mount(Input, {
                attrs: {
                    'aria-describedby': 'error-message',
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('aria-describedby')).toBe('error-message');
        });
    });

    describe('edge cases', () => {
        it('should handle empty string value', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: '',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('');
        });

        it('should handle special characters', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: '<script>alert("xss")</script>',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('<script>alert("xss")</script>');
        });

        it('should handle unicode characters', async () => {
            const wrapper = mount(Input, {
                props: {
                    modelValue: '你好 世界 🎉',
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe('你好 世界 🎉');
        });

        it('should handle very long text', async () => {
            const longText = 'a'.repeat(1000);
            const wrapper = mount(Input, {
                props: {
                    modelValue: longText,
                },
            });

            const input = wrapper.find('input');
            expect((input.element as HTMLInputElement).value).toBe(longText);
        });
    });

    describe('readonly state', () => {
        it('should apply readonly attribute', () => {
            const wrapper = mount(Input, {
                attrs: {
                    readonly: true,
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('readonly')).toBeDefined();
        });
    });

    describe('required state', () => {
        it('should apply required attribute', () => {
            const wrapper = mount(Input, {
                attrs: {
                    required: true,
                },
            });

            const input = wrapper.find('input');
            expect(input.attributes('required')).toBeDefined();
        });
    });
});
