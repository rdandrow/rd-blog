import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Label from '@/components/ui/label/Label.vue';

// Mock reka-ui Label component
vi.mock('reka-ui', () => ({
    Label: {
        name: 'Label',
        template: '<label data-slot="label"><slot /></label>',
        props: ['for', 'asChild'],
    },
}));

describe('Label', () => {
    describe('rendering', () => {
        it('should render label element', () => {
            const wrapper = mount(Label);
            expect(wrapper.find('[data-slot="label"]').exists()).toBe(true);
        });

        it('should have correct data-slot attribute', () => {
            const wrapper = mount(Label);
            expect(wrapper.find('[data-slot="label"]').attributes('data-slot')).toBe('label');
        });

        it('should render slot content', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Username',
                },
            });

            expect(wrapper.text()).toContain('Username');
        });

        it('should render HTML in slot', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: '<span class="required">Email *</span>',
                },
            });

            expect(wrapper.find('.required').exists()).toBe(true);
            expect(wrapper.text()).toContain('Email *');
        });
    });

    describe('styling', () => {
        it('should apply default classes', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            const classes = label.classes().join(' ');
            expect(classes).toContain('flex');
            expect(classes).toContain('items-center');
            expect(classes).toContain('gap-2');
            expect(classes).toContain('text-sm');
        });

        it('should have leading-none class', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            expect(label.classes().join(' ')).toContain('leading-none');
        });

        it('should have font-medium class', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            expect(label.classes()).toContain('font-medium');
        });

        it('should have select-none class', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            expect(label.classes().join(' ')).toContain('select-none');
        });

        it('should apply custom class', () => {
            const wrapper = mount(Label, {
                props: {
                    class: 'custom-label',
                },
            });

            expect(wrapper.find('[data-slot="label"]').classes()).toContain('custom-label');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(Label, {
                props: {
                    class: 'text-lg font-bold',
                },
            });

            const label = wrapper.find('[data-slot="label"]');
            expect(label.classes()).toContain('text-lg');
            expect(label.classes()).toContain('font-bold');
            expect(label.classes()).toContain('flex');
        });
    });

    describe('disabled state styling', () => {
        it('should have group-disabled styles', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            const classes = label.classes().join(' ');
            expect(classes).toContain('group-data-[disabled=true]:pointer-events-none');
            expect(classes).toContain('group-data-[disabled=true]:opacity-50');
        });

        it('should have peer-disabled styles', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            const classes = label.classes().join(' ');
            expect(classes).toContain('peer-disabled:cursor-not-allowed');
            expect(classes).toContain('peer-disabled:opacity-50');
        });
    });

    describe('props', () => {
        it('should accept for prop', () => {
            const wrapper = mount(Label, {
                props: {
                    for: 'username-input',
                },
            });

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('for')).toBe('username-input');
        });

        it('should accept asChild prop', () => {
            const wrapper = mount(Label, {
                props: {
                    asChild: true,
                },
            });

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('asChild')).toBe(true);
        });
    });

    describe('slot content', () => {
        it('should render text content', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'First Name',
                },
            });

            expect(wrapper.text()).toBe('First Name');
        });

        it('should render with icon', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: '<svg class="icon">icon</svg><span>Label with icon</span>',
                },
            });

            expect(wrapper.find('.icon').exists()).toBe(true);
            expect(wrapper.text()).toContain('Label with icon');
        });

        it('should render with required indicator', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Email <span class="text-destructive">*</span>',
                },
            });

            expect(wrapper.text()).toContain('Email');
            expect(wrapper.find('.text-destructive').exists()).toBe(true);
        });

        it('should handle empty slot', () => {
            const wrapper = mount(Label);
            expect(wrapper.html()).toBeTruthy();
        });

        it('should render complex slot content', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: `
                        <span>Name</span>
                        <span class="optional">(optional)</span>
                    `,
                },
            });

            expect(wrapper.text()).toContain('Name');
            expect(wrapper.text()).toContain('(optional)');
            expect(wrapper.find('.optional').exists()).toBe(true);
        });
    });

    describe('reactivity', () => {
        it('should render updated slot content when remounted', () => {
            const initialWrapper = mount(Label, {
                slots: {
                    default: 'Initial Label',
                },
            });

            expect(initialWrapper.text()).toBe('Initial Label');

            const updatedWrapper = mount(Label, {
                slots: {
                    default: 'Updated Label',
                },
            });

            expect(updatedWrapper.text()).toBe('Updated Label');
        });

        it('should update class reactively', async () => {
            const wrapper = mount(Label, {
                props: {
                    class: 'initial-class',
                },
                slots: {
                    default: 'Label',
                },
            });

            expect(wrapper.find('[data-slot="label"]').classes()).toContain('initial-class');

            await wrapper.setProps({ class: 'updated-class' });

            expect(wrapper.find('[data-slot="label"]').classes()).toContain('updated-class');
        });
    });

    describe('accessibility', () => {
        it('should be keyboard accessible', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Label',
                },
            });

            const label = wrapper.find('[data-slot="label"]');
            expect(label.element.tagName).toBe('LABEL');
        });

        it('should work with form controls via for prop', () => {
            const wrapper = mount(Label, {
                props: {
                    for: 'input-id',
                },
                slots: {
                    default: 'Input Label',
                },
            });

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('for')).toBe('input-id');
        });

        it('should support screen reader text', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: '<span class="sr-only">Screen reader only</span>',
                },
            });

            expect(wrapper.find('.sr-only').exists()).toBe(true);
        });
    });

    describe('edge cases', () => {
        it('should handle multiple spaces in text', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Label    with    spaces',
                },
            });

            expect(wrapper.text()).toContain('Label');
            expect(wrapper.text()).toContain('spaces');
        });

        it('should handle special characters', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Label & Special <> Characters',
                },
            });

            expect(wrapper.text()).toContain('Label');
        });

        it('should handle unicode characters', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: '用户名 👤',
                },
            });

            expect(wrapper.text()).toBe('用户名 👤');
        });

        it('should handle very long text', () => {
            const longText = 'Very long label text '.repeat(20);
            const wrapper = mount(Label, {
                slots: {
                    default: longText,
                },
            });

            expect(wrapper.text()).toContain('Very long label text');
        });

        it('should handle undefined class', () => {
            const wrapper = mount(Label, {
                props: {
                    class: undefined,
                },
                slots: {
                    default: 'Label',
                },
            });

            expect(wrapper.find('[data-slot="label"]').exists()).toBe(true);
        });

        it('should handle empty string class', () => {
            const wrapper = mount(Label, {
                props: {
                    class: '',
                },
                slots: {
                    default: 'Label',
                },
            });

            expect(wrapper.find('[data-slot="label"]').exists()).toBe(true);
        });
    });

    describe('structure', () => {
        it('should have correct component structure', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Label Text',
                },
            });

            const label = wrapper.find('[data-slot="label"]');
            expect(label.exists()).toBe(true);
            expect(label.element.tagName).toBe('LABEL');
        });

        it('should properly delegate props to reka-ui Label', () => {
            const wrapper = mount(Label, {
                props: {
                    for: 'my-input',
                    class: 'custom',
                },
                slots: {
                    default: 'Label',
                },
            });

            const rekaiLabel = wrapper.findComponent({ name: 'Label' });
            expect(rekaiLabel.exists()).toBe(true);
            expect(rekaiLabel.props('for')).toBe('my-input');
        });
    });

    describe('gap utility', () => {
        it('should have gap-2 class for spacing', () => {
            const wrapper = mount(Label);
            const label = wrapper.find('[data-slot="label"]');
            expect(label.classes().join(' ')).toContain('gap-2');
        });

        it('should align items with gap when multiple children', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: '<span>Icon</span><span>Text</span>',
                },
            });

            const label = wrapper.find('[data-slot="label"]');
            const classes = label.classes().join(' ');
            expect(classes).toContain('flex');
            expect(classes).toContain('items-center');
            expect(classes).toContain('gap-2');
        });
    });

    describe('TypeScript props', () => {
        it('should accept string class prop', () => {
            const wrapper = mount(Label, {
                props: {
                    class: 'text-red-500',
                },
            });

            expect(wrapper.props('class')).toBe('text-red-500');
        });

        it('should accept for prop as string', () => {
            const wrapper = mount(Label, {
                props: {
                    for: 'element-id',
                },
            });

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('for')).toBe('element-id');
        });

        it('should allow all props to be omitted', () => {
            const wrapper = mount(Label, {
                slots: {
                    default: 'Label',
                },
            });

            expect(wrapper.exists()).toBe(true);
        });
    });
});
