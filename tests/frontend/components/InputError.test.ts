import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import InputError from '@/components/InputError.vue';

describe('InputError', () => {
    describe('rendering', () => {
        it('should render error message when provided', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'This field is required',
                },
            });

            expect(wrapper.text()).toBe('This field is required');
            expect(wrapper.find('p').classes()).toContain('text-red-600');
        });

        it('should not display when message is undefined', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: undefined,
                },
            });

            // v-show sets display: none but element still exists
            const div = wrapper.find('div');
            expect(div.element.style.display).toBe('none');
        });

        it('should not display when message is empty string', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: '',
                },
            });

            const div = wrapper.find('div');
            expect(div.element.style.display).toBe('none');
        });

        it('should apply correct styling classes', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'Error message',
                },
            });

            const paragraph = wrapper.find('p');
            expect(paragraph.classes()).toContain('text-sm');
            expect(paragraph.classes()).toContain('text-red-600');
            expect(paragraph.classes()).toContain('dark:text-red-500');
        });
    });

    describe('reactivity', () => {
        it('should update when message prop changes', async () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'Initial error',
                },
            });

            expect(wrapper.text()).toBe('Initial error');

            await wrapper.setProps({ message: 'Updated error' });

            expect(wrapper.text()).toBe('Updated error');
        });

        it('should hide when message becomes empty', async () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'Has error',
                },
            });

            const div = wrapper.find('div');
            expect(div.element.style.display).not.toBe('none');

            await wrapper.setProps({ message: '' });

            expect(div.element.style.display).toBe('none');
        });

        it('should show when message is added', async () => {
            const wrapper = mount(InputError, {
                props: {
                    message: '',
                },
            });

            const div = wrapper.find('div');
            expect(div.element.style.display).toBe('none');

            await wrapper.setProps({ message: 'New error' });

            expect(div.element.style.display).not.toBe('none');
        });
    });

    describe('edge cases', () => {
        it('should handle multiline error messages', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'Line 1\nLine 2\nLine 3',
                },
            });

            expect(wrapper.text()).toBe('Line 1\nLine 2\nLine 3');
        });

        it('should handle special characters in message', () => {
            const wrapper = mount(InputError, {
                props: {
                    message: 'Error: <>&"\'',
                },
            });

            expect(wrapper.text()).toContain('Error:');
        });

        it('should handle very long error messages', () => {
            const longMessage = 'A'.repeat(500);
            const wrapper = mount(InputError, {
                props: {
                    message: longMessage,
                },
            });

            expect(wrapper.text()).toBe(longMessage);
        });
    });
});
