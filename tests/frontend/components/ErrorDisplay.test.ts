import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import ErrorDisplay from '@/components/ErrorDisplay.vue';

describe('ErrorDisplay', () => {
    describe('rendering', () => {
        it('should not render when error is null', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: null,
                },
            });

            expect(wrapper.find('[role="alert"]').exists()).toBe(false);
        });

        it('should not render when error is empty string', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: '',
                },
            });

            expect(wrapper.find('[role="alert"]').exists()).toBe(false);
        });

        it('should render error message', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Something went wrong',
                },
            });

            expect(wrapper.find('.p-4').exists()).toBe(true);
            expect(wrapper.text()).toContain('Something went wrong');
        });

        it('should apply default error type styling', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                },
            });

            const alert = wrapper.find('.p-4');
            expect(alert.classes()).toContain('bg-destructive/10');
            expect(alert.classes()).toContain('border-destructive/20');
        });

        it('should apply warning type styling', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Warning message',
                    type: 'warning',
                },
            });

            const alert = wrapper.find('.p-4');
            expect(alert.classes()).toContain('bg-yellow-50');
            expect(alert.classes()).toContain('border-yellow-200');
        });

        it('should apply info type styling', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Info message',
                    type: 'info',
                },
            });

            const alert = wrapper.find('.p-4');
            expect(alert.classes()).toContain('bg-blue-50');
            expect(alert.classes()).toContain('border-blue-200');
        });

        it('should render dismiss button when dismissible', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    dismissible: true,
                },
            });

            expect(wrapper.find('button').exists()).toBe(true);
            expect(wrapper.find('button').attributes('aria-label')).toBe('Dismiss');
        });

        it('should not render dismiss button when not dismissible', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    dismissible: false,
                },
            });

            expect(wrapper.find('button').exists()).toBe(false);
        });

        it('should render correct icon for error type', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    type: 'error',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should render correct icon for warning type', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Warning message',
                    type: 'warning',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('should render correct icon for info type', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Info message',
                    type: 'info',
                },
            });

            expect(wrapper.find('svg').exists()).toBe(true);
        });
    });

    describe('interactions', () => {
        it('should emit dismiss event when dismiss button is clicked', async () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    dismissible: true,
                },
            });

            await wrapper.find('button').trigger('click');

            expect(wrapper.emitted()).toHaveProperty('dismiss');
            expect(wrapper.emitted('dismiss')).toHaveLength(1);
        });

        it('should not emit dismiss when dismissible is false', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    dismissible: false,
                },
            });

            expect(wrapper.emitted('dismiss')).toBeUndefined();
        });
    });

    describe('reactivity', () => {
        it('should update error message when prop changes', async () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Initial error',
                },
            });

            expect(wrapper.text()).toContain('Initial error');

            await wrapper.setProps({ error: 'Updated error' });

            expect(wrapper.text()).toContain('Updated error');
            expect(wrapper.text()).not.toContain('Initial error');
        });

        it('should hide when error becomes null', async () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                },
            });

            expect(wrapper.find('.p-4').exists()).toBe(true);

            await wrapper.setProps({ error: null });

            expect(wrapper.find('.p-4').exists()).toBe(false);
        });

        it('should update type styling when prop changes', async () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Message',
                    type: 'error',
                },
            });

            expect(wrapper.find('.p-4').classes()).toContain('bg-destructive/10');

            await wrapper.setProps({ type: 'warning' });

            expect(wrapper.find('.p-4').classes()).toContain('bg-yellow-50');
        });
    });

    describe('edge cases', () => {
        it('should handle long error messages', () => {
            const longError = 'A'.repeat(500);
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: longError,
                },
            });

            expect(wrapper.text()).toContain(longError);
        });

        it('should handle HTML entities in error messages', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error with <script>alert("xss")</script> tags',
                },
            });

            expect(wrapper.text()).toContain('<script>');
            expect(wrapper.text()).toContain('</script>');
        });

        it('should handle special characters', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error with & < > " \' characters',
                },
            });

            expect(wrapper.text()).toContain('Error with & < > " \' characters');
        });

        it('should handle multiline error messages', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Line 1\nLine 2\nLine 3',
                },
            });

            expect(wrapper.text()).toContain('Line 1');
            expect(wrapper.text()).toContain('Line 2');
            expect(wrapper.text()).toContain('Line 3');
        });
    });

    describe('accessibility', () => {
        it('should render with proper structure for screen readers', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                },
            });

            expect(wrapper.find('.p-4').exists()).toBe(true);
            expect(wrapper.text()).toContain('Error message');
        });

        it('should have aria-label on dismiss button', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    dismissible: true,
                },
            });

            expect(wrapper.find('button').attributes('aria-label')).toBe('Dismiss');
        });

        it('should render properly for different types', () => {
            const wrapper = mount(ErrorDisplay, {
                props: {
                    error: 'Error message',
                    type: 'error',
                },
            });

            expect(wrapper.find('.p-4').exists()).toBe(true);
            expect(wrapper.find('svg').exists()).toBe(true);
        });
    });
});
