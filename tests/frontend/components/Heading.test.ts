import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Heading from '@/components/Heading.vue';

describe('Heading', () => {
    describe('rendering', () => {
        it('should render title', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test Heading',
                },
            });

            expect(wrapper.find('h2').text()).toBe('Test Heading');
        });

        it('should render description when provided', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test Heading',
                    description: 'Test description',
                },
            });

            expect(wrapper.find('p').text()).toBe('Test description');
        });

        it('should not render description element when not provided', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test Heading',
                },
            });

            expect(wrapper.find('p').exists()).toBe(false);
        });

        it('should apply correct styling to title', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test Heading',
                },
            });

            const h2 = wrapper.find('h2');
            expect(h2.classes()).toContain('text-xl');
            expect(h2.classes()).toContain('font-semibold');
            expect(h2.classes()).toContain('tracking-tight');
        });

        it('should apply correct styling to description', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test',
                    description: 'Description',
                },
            });

            const p = wrapper.find('p');
            expect(p.classes()).toContain('text-sm');
            expect(p.classes()).toContain('text-muted-foreground');
        });

        it('should have correct container spacing', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Test',
                },
            });

            const container = wrapper.find('div');
            expect(container.classes()).toContain('mb-8');
            expect(container.classes()).toContain('space-y-0.5');
        });
    });

    describe('reactivity', () => {
        it('should update title when prop changes', async () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Initial Title',
                },
            });

            expect(wrapper.find('h2').text()).toBe('Initial Title');

            await wrapper.setProps({ title: 'Updated Title' });

            expect(wrapper.find('h2').text()).toBe('Updated Title');
        });

        it('should update description when prop changes', async () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Title',
                    description: 'Initial Description',
                },
            });

            expect(wrapper.find('p').text()).toBe('Initial Description');

            await wrapper.setProps({ description: 'Updated Description' });

            expect(wrapper.find('p').text()).toBe('Updated Description');
        });

        it('should show description when added dynamically', async () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Title',
                },
            });

            expect(wrapper.find('p').exists()).toBe(false);

            await wrapper.setProps({ description: 'New Description' });

            expect(wrapper.find('p').exists()).toBe(true);
            expect(wrapper.find('p').text()).toBe('New Description');
        });
    });

    describe('edge cases', () => {
        it('should handle long titles', () => {
            const longTitle = 'A'.repeat(200);
            const wrapper = mount(Heading, {
                props: {
                    title: longTitle,
                },
            });

            expect(wrapper.find('h2').text()).toBe(longTitle);
        });

        it('should handle long descriptions', () => {
            const longDesc = 'B'.repeat(500);
            const wrapper = mount(Heading, {
                props: {
                    title: 'Title',
                    description: longDesc,
                },
            });

            expect(wrapper.find('p').text()).toBe(longDesc);
        });

        it('should handle special characters', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Title with <special> & "chars"',
                    description: 'Description with <>&"\'',
                },
            });

            expect(wrapper.find('h2').text()).toContain('special');
            expect(wrapper.find('p').text()).toBe('Description with <>&"\'');
        });

        it('should handle multiline descriptions', () => {
            const wrapper = mount(Heading, {
                props: {
                    title: 'Title',
                    description: 'Line 1\nLine 2\nLine 3',
                },
            });

            expect(wrapper.find('p').text()).toBe('Line 1\nLine 2\nLine 3');
        });
    });
});
