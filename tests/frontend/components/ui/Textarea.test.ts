import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Textarea from '@/components/ui/textarea/Textarea.vue';

describe('Textarea', () => {
    describe('rendering', () => {
        it('should render textarea element', () => {
            const wrapper = mount(Textarea);
            expect(wrapper.find('textarea').exists()).toBe(true);
        });

        it('should apply default classes', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('flex');
            expect(textarea.classes()).toContain('min-h-[80px]');
            expect(textarea.classes()).toContain('rounded-md');
            expect(textarea.classes()).toContain('border');
        });

        it('should apply custom class', () => {
            const wrapper = mount(Textarea, {
                props: {
                    class: 'custom-class',
                },
            });
            expect(wrapper.find('textarea').classes()).toContain('custom-class');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(Textarea, {
                props: {
                    class: 'w-full h-40',
                },
            });
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('w-full');
            expect(textarea.classes()).toContain('h-40');
            expect(textarea.classes()).toContain('rounded-md');
        });
    });

    describe('v-model', () => {
        it('should bind value via v-model', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: 'test value',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('test value');
        });

        it('should emit update:modelValue on input', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.setValue('new value');

            expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['new value']);
        });

        it('should update value reactively', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: 'initial',
                },
            });

            await wrapper.setProps({ modelValue: 'updated' });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('updated');
        });
    });

    describe('defaultValue', () => {
        it('should use defaultValue when no modelValue provided', () => {
            const wrapper = mount(Textarea, {
                props: {
                    defaultValue: 'default text',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('default text');
        });

        it('should prefer modelValue over defaultValue', () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: 'model value',
                    defaultValue: 'default value',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('model value');
        });
    });

    describe('disabled state', () => {
        it('should apply disabled attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    disabled: true,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('disabled')).toBeDefined();
        });

        it('should have disabled styles in class', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes().join(' ')).toContain('disabled:opacity-50');
            expect(textarea.classes().join(' ')).toContain('disabled:cursor-not-allowed');
        });
    });

    describe('placeholder', () => {
        it('should render placeholder text', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    placeholder: 'Enter description...',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('placeholder')).toBe('Enter description...');
        });

        it('should have placeholder styles', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes().join(' ')).toContain('placeholder:text-muted-foreground');
        });
    });

    describe('number values', () => {
        it('should handle numeric modelValue', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: 42,
                },
            });

            const textarea = wrapper.find('textarea');
            // Textarea element will have the numeric value 
            expect((textarea.element as HTMLTextAreaElement).value).toBe(42 as any);
        });

        it('should emit numeric value as string from textarea', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: 0,
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.setValue('123');

            expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['123']);
        });
    });

    describe('focus styles', () => {
        it('should have focus-visible styles', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            const classes = textarea.classes().join(' ');
            expect(classes).toContain('focus-visible:outline-none');
            expect(classes).toContain('focus-visible:ring-2');
            expect(classes).toContain('focus-visible:ring-ring');
        });

        it('should have ring offset on focus', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            const classes = textarea.classes().join(' ');
            expect(classes).toContain('focus-visible:ring-offset-2');
            expect(classes).toContain('ring-offset-background');
        });
    });

    describe('dimensions', () => {
        it('should have minimum height class', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('min-h-[80px]');
        });

        it('should have full width class', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('w-full');
        });

        it('should allow custom height via class prop', () => {
            const wrapper = mount(Textarea, {
                props: {
                    class: 'min-h-[200px]',
                },
            });
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('min-h-[200px]');
        });
    });

    describe('styling', () => {
        it('should have border styles', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            const classes = textarea.classes().join(' ');
            expect(classes).toContain('border');
            expect(classes).toContain('border-input');
        });

        it('should have background styles', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes().join(' ')).toContain('bg-background');
        });

        it('should have padding classes', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            const classes = textarea.classes().join(' ');
            expect(classes).toContain('px-3');
            expect(classes).toContain('py-2');
        });

        it('should have text size class', () => {
            const wrapper = mount(Textarea);
            const textarea = wrapper.find('textarea');
            expect(textarea.classes()).toContain('text-sm');
        });
    });

    describe('accessibility', () => {
        it('should support aria-label', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    'aria-label': 'Description',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('aria-label')).toBe('Description');
        });

        it('should support aria-describedby', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    'aria-describedby': 'error-message',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('aria-describedby')).toBe('error-message');
        });

        it('should support aria-required', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    'aria-required': true,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('aria-required')).toBe('true');
        });

        it('should support aria-invalid', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    'aria-invalid': true,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('aria-invalid')).toBe('true');
        });
    });

    describe('edge cases', () => {
        it('should handle empty string value', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('');
        });

        it('should handle multiline text', async () => {
            const multilineText = 'Line 1\nLine 2\nLine 3';
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: multilineText,
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe(multilineText);
        });

        it('should handle special characters', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '<script>alert("xss")</script>',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('<script>alert("xss")</script>');
        });

        it('should handle unicode characters', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '你好 世界 🎉',
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('你好 世界 🎉');
        });

        it('should handle very long text', async () => {
            const longText = 'a'.repeat(10000);
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: longText,
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe(longText);
        });

        it('should handle text with tabs and line breaks', async () => {
            const textWithFormatting = 'Line 1\n\tIndented line\nLine 3';
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: textWithFormatting,
                },
            });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe(textWithFormatting);
        });
    });

    describe('readonly state', () => {
        it('should apply readonly attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    readonly: true,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('readonly')).toBeDefined();
        });
    });

    describe('required state', () => {
        it('should apply required attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    required: true,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('required')).toBeDefined();
        });
    });

    describe('maxlength', () => {
        it('should apply maxlength attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    maxlength: 500,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('maxlength')).toBe('500');
        });
    });

    describe('rows', () => {
        it('should apply rows attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    rows: 10,
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('rows')).toBe('10');
        });
    });

    describe('reactivity', () => {
        it('should emit multiple updates', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '',
                },
            });

            const textarea = wrapper.find('textarea');
            await textarea.setValue('first');
            await textarea.setValue('second');
            await textarea.setValue('third');

            const emitted = wrapper.emitted('update:modelValue');
            expect(emitted).toHaveLength(3);
            expect(emitted?.[0]).toEqual(['first']);
            expect(emitted?.[1]).toEqual(['second']);
            expect(emitted?.[2]).toEqual(['third']);
        });

        it('should handle rapid value changes', async () => {
            const wrapper = mount(Textarea, {
                props: {
                    modelValue: '',
                },
            });

            await wrapper.setProps({ modelValue: 'a' });
            await wrapper.setProps({ modelValue: 'ab' });
            await wrapper.setProps({ modelValue: 'abc' });

            const textarea = wrapper.find('textarea');
            expect((textarea.element as HTMLTextAreaElement).value).toBe('abc');
        });
    });

    describe('name attribute', () => {
        it('should apply name attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    name: 'description',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('name')).toBe('description');
        });
    });

    describe('id attribute', () => {
        it('should apply id attribute', () => {
            const wrapper = mount(Textarea, {
                attrs: {
                    id: 'my-textarea',
                },
            });

            const textarea = wrapper.find('textarea');
            expect(textarea.attributes('id')).toBe('my-textarea');
        });
    });
});
