import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AlertError from '@/components/AlertError.vue';

describe('AlertError', () => {
    describe('rendering', () => {
        it('should render with default title', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                },
            });

            expect(wrapper.text()).toContain('Something went wrong.');
        });

        it('should render with custom title', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                    title: 'Custom Error Title',
                },
            });

            expect(wrapper.text()).toContain('Custom Error Title');
            expect(wrapper.text()).not.toContain('Something went wrong.');
        });

        it('should render all error messages', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', 'Error 2', 'Error 3'],
                },
            });

            expect(wrapper.text()).toContain('Error 1');
            expect(wrapper.text()).toContain('Error 2');
            expect(wrapper.text()).toContain('Error 3');
        });

        it('should render errors in a list', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', 'Error 2'],
                },
            });

            const ul = wrapper.find('ul');
            expect(ul.exists()).toBe(true);
            expect(ul.classes()).toContain('list-disc');
            expect(ul.classes()).toContain('list-inside');

            const listItems = wrapper.findAll('li');
            expect(listItems).toHaveLength(2);
        });

        it('should have destructive variant styling', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                },
            });

            // The Alert component should receive variant="destructive"
            expect(wrapper.html()).toContain('destructive');
        });

        it('should render AlertCircle icon', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                },
            });

            // Check for the icon component
            const icon = wrapper.find('[class*="size-4"]');
            expect(icon.exists()).toBe(true);
        });
    });

    describe('unique errors', () => {
        it('should remove duplicate error messages', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', 'Error 2', 'Error 1', 'Error 2', 'Error 3'],
                },
            });

            const listItems = wrapper.findAll('li');
            expect(listItems).toHaveLength(3);
            
            const texts = listItems.map(li => li.text());
            expect(texts).toEqual(['Error 1', 'Error 2', 'Error 3']);
        });

        it('should handle all duplicate errors', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Same error', 'Same error', 'Same error'],
                },
            });

            const listItems = wrapper.findAll('li');
            expect(listItems).toHaveLength(1);
            expect(listItems[0].text()).toBe('Same error');
        });

        it('should preserve order of unique errors', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Third', 'First', 'Second', 'Third', 'First'],
                },
            });

            const listItems = wrapper.findAll('li');
            const texts = listItems.map(li => li.text());
            expect(texts).toEqual(['Third', 'First', 'Second']);
        });

        it('should handle empty strings in errors array', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', '', 'Error 2', ''],
                },
            });

            const listItems = wrapper.findAll('li');
            // Empty strings are still unique and will be shown
            expect(listItems).toHaveLength(3); // 'Error 1', '', 'Error 2'
        });
    });

    describe('reactivity', () => {
        it('should update when errors array changes', async () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                },
            });

            expect(wrapper.findAll('li')).toHaveLength(1);

            await wrapper.setProps({ errors: ['Error 1', 'Error 2', 'Error 3'] });

            expect(wrapper.findAll('li')).toHaveLength(3);
        });

        it('should update when title changes', async () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1'],
                    title: 'Initial Title',
                },
            });

            expect(wrapper.text()).toContain('Initial Title');

            await wrapper.setProps({ title: 'Updated Title' });

            expect(wrapper.text()).toContain('Updated Title');
            expect(wrapper.text()).not.toContain('Initial Title');
        });

        it('should update unique errors when duplicates are added', async () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', 'Error 2'],
                },
            });

            expect(wrapper.findAll('li')).toHaveLength(2);

            await wrapper.setProps({ errors: ['Error 1', 'Error 1', 'Error 2', 'Error 2'] });

            expect(wrapper.findAll('li')).toHaveLength(2);
        });
    });

    describe('edge cases', () => {
        it('should handle empty errors array', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: [],
                },
            });

            expect(wrapper.findAll('li')).toHaveLength(0);
            expect(wrapper.find('ul').exists()).toBe(true);
        });

        it('should handle single error', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Single error'],
                },
            });

            expect(wrapper.findAll('li')).toHaveLength(1);
            expect(wrapper.text()).toContain('Single error');
        });

        it('should handle very long error messages', () => {
            const longError = 'A'.repeat(500);
            const wrapper = mount(AlertError, {
                props: {
                    errors: [longError],
                },
            });

            expect(wrapper.text()).toContain(longError);
        });

        it('should handle many errors', () => {
            const manyErrors = Array.from({ length: 50 }, (_, i) => `Error ${i + 1}`);
            const wrapper = mount(AlertError, {
                props: {
                    errors: manyErrors,
                },
            });

            expect(wrapper.findAll('li')).toHaveLength(50);
        });

        it('should handle errors with special characters', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error with <>&"\'', 'Error with\nnewline'],
                },
            });

            expect(wrapper.text()).toContain('Error with');
            expect(wrapper.text()).toContain('newline');
        });

        it('should handle null-like values in title gracefully', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error'],
                    title: '',
                },
            });

            // Empty string title should still render
            expect(wrapper.find('ul').exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should use semantic list markup', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error 1', 'Error 2'],
                },
            });

            expect(wrapper.find('ul').exists()).toBe(true);
            expect(wrapper.findAll('li')).toHaveLength(2);
        });

        it('should have descriptive title for screen readers', () => {
            const wrapper = mount(AlertError, {
                props: {
                    errors: ['Error'],
                    title: 'Form submission failed',
                },
            });

            expect(wrapper.html()).toContain('Form submission failed');
        });
    });
});
