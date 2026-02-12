import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import HeadingSmall from '@/components/HeadingSmall.vue';

describe('HeadingSmall', () => {
    describe('rendering', () => {
        it('should render title in h3 element', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Small Heading',
                },
            });

            expect(wrapper.find('h3').text()).toBe('Small Heading');
        });

        it('should render description when provided', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Small Heading',
                    description: 'Subtitle text',
                },
            });

            expect(wrapper.find('p').text()).toBe('Subtitle text');
        });

        it('should not render description element when not provided', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Small Heading',
                },
            });

            expect(wrapper.find('p').exists()).toBe(false);
        });

        it('should apply correct styling to title', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                },
            });

            const h3 = wrapper.find('h3');
            expect(h3.classes()).toContain('text-base');
            expect(h3.classes()).toContain('font-medium');
            expect(h3.classes()).toContain('mb-0.5');
        });

        it('should apply correct styling to description', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                    description: 'Description',
                },
            });

            const p = wrapper.find('p');
            expect(p.classes()).toContain('text-sm');
            expect(p.classes()).toContain('text-muted-foreground');
        });

        it('should wrap content in header element', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                },
            });

            expect(wrapper.find('header').exists()).toBe(true);
            expect(wrapper.find('header h3').exists()).toBe(true);
        });
    });

    describe('reactivity', () => {
        it('should update title when prop changes', async () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Original',
                },
            });

            expect(wrapper.find('h3').text()).toBe('Original');

            await wrapper.setProps({ title: 'Changed' });

            expect(wrapper.find('h3').text()).toBe('Changed');
        });

        it('should update description when prop changes', async () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Title',
                    description: 'First',
                },
            });

            expect(wrapper.find('p').text()).toBe('First');

            await wrapper.setProps({ description: 'Second' });

            expect(wrapper.find('p').text()).toBe('Second');
        });

        it('should show description when added', async () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Title',
                },
            });

            expect(wrapper.find('p').exists()).toBe(false);

            await wrapper.setProps({ description: 'Added later' });

            expect(wrapper.find('p').exists()).toBe(true);
            expect(wrapper.find('p').text()).toBe('Added later');
        });
    });

    describe('edge cases', () => {
        it('should handle empty string description', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Title',
                    description: '',
                },
            });

            // Empty string is falsy so v-if should not render it
            expect(wrapper.find('p').exists()).toBe(false);
        });

        it('should handle very long titles', () => {
            const longTitle = 'A'.repeat(300);
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: longTitle,
                },
            });

            expect(wrapper.find('h3').text()).toBe(longTitle);
        });

        it('should handle special HTML characters', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test <script>alert("xss")</script>',
                    description: 'Desc with <b>tags</b>',
                },
            });

            // Vue should escape these by default
            expect(wrapper.find('h3').text()).toContain('script');
            expect(wrapper.find('p').text()).toContain('tags');
        });
    });

    describe('comparison with Heading', () => {
        it('should use h3 instead of h2', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                },
            });

            expect(wrapper.find('h3').exists()).toBe(true);
            expect(wrapper.find('h2').exists()).toBe(false);
        });

        it('should use text-base instead of text-xl', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                },
            });

            const h3 = wrapper.find('h3');
            expect(h3.classes()).toContain('text-base');
            expect(h3.classes()).not.toContain('text-xl');
        });

        it('should use font-medium instead of font-semibold', () => {
            const wrapper = mount(HeadingSmall, {
                props: {
                    title: 'Test',
                },
            });

            const h3 = wrapper.find('h3');
            expect(h3.classes()).toContain('font-medium');
            expect(h3.classes()).not.toContain('font-semibold');
        });
    });
});
