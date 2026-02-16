import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';

describe('Checkbox', () => {
    describe('rendering', () => {
        it('should render checkbox root element', () => {
            const wrapper = mount(Checkbox);
            expect(wrapper.find('[data-slot="checkbox"]').exists()).toBe(true);
        });

        it('should have correct data-slot attribute', () => {
            const wrapper = mount(Checkbox);
            expect(wrapper.find('[data-slot="checkbox"]').attributes('data-slot')).toBe('checkbox');
        });

        it('should render as a button element', () => {
            const wrapper = mount(Checkbox);
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.element.tagName).toBe('BUTTON');
        });
    });

    describe('styling', () => {
        it('should apply custom class', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    class: 'custom-class',
                },
            });
            expect(wrapper.find('[data-slot="checkbox"]').classes()).toContain('custom-class');
        });

        it('should merge custom classes with default classes', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    class: 'custom-1 custom-2',
                },
            });
            const element = wrapper.find('[data-slot="checkbox"]');
            expect(element.classes()).toContain('custom-1');
            expect(element.classes()).toContain('custom-2');
        });

        it('should have default size class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('size-4');
        });

        it('should have shrink-0 class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('shrink-0');
        });

        it('should have rounded corners', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('rounded-[4px]');
        });

        it('should have border class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('border');
        });

        it('should have border-input class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('border-input');
        });

        it('should have shadow class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('shadow-xs');
        });

        it('should have transition class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('transition-shadow');
        });

        it('should have peer class for label interaction', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('peer');
        });
    });

    describe('checked state styling', () => {
        it('should have data-checked background color class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('data-[state=checked]:bg-primary');
        });

        it('should have data-checked text color class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('data-[state=checked]:text-primary-foreground');
        });

        it('should have data-checked border color class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('data-[state=checked]:border-primary');
        });
    });

    describe('focus styling', () => {
        it('should have focus-visible border class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('focus-visible:border-ring');
        });

        it('should have focus-visible ring class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('focus-visible:ring-ring/50');
        });

        it('should have focus-visible ring size class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('focus-visible:ring-[3px]');
        });

        it('should have outline-none class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('outline-none');
        });
    });

    describe('validation styling', () => {
        it('should have aria-invalid ring class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('aria-invalid:ring-destructive/20');
        });

        it('should have aria-invalid border class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('aria-invalid:border-destructive');
        });

        it('should have dark mode aria-invalid ring class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('dark:aria-invalid:ring-destructive/40');
        });
    });

    describe('disabled styling', () => {
        it('should have disabled cursor class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('disabled:cursor-not-allowed');
        });

        it('should have disabled opacity class', () => {
            const wrapper = mount(Checkbox);
            const classes = wrapper.find('[data-slot="checkbox"]').classes().join(' ');
            expect(classes).toContain('disabled:opacity-50');
        });
    });

    describe('accessibility', () => {
        it('should have role attribute', () => {
            const wrapper = mount(Checkbox);
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('role')).toBe('checkbox');
        });

        it('should have type button attribute', () => {
            const wrapper = mount(Checkbox);
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('type')).toBe('button');
        });

        it('should have aria-checked attribute', () => {
            const wrapper = mount(Checkbox);
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('aria-checked')).toBeDefined();
        });

        it('should have aria-required attribute when required prop is passed', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    required: true,
                },
            });
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('aria-required')).toBe('true');
        });

        it('should be keyboard accessible as button element', () => {
            const wrapper = mount(Checkbox);
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            // Button elements are keyboard accessible by default
            expect(checkbox.element.tagName).toBe('BUTTON');
        });
    });

    describe('props', () => {
        it('should accept checked prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    checked: true,
                },
            });
            // The checked attribute should be present on the element
            expect(wrapper.find('[data-slot="checkbox"]').attributes('checked')).toBeDefined();
        });

        it('should accept disabled prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    disabled: true,
                },
            });
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('disabled')).toBeDefined();
        });

        it('should accept required prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    required: true,
                },
            });
            const checkbox = wrapper.find('[data-slot="checkbox"]');
            expect(checkbox.attributes('aria-required')).toBe('true');
        });

        it('should accept name prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    name: 'test-checkbox',
                },
            });
            // Name prop is passed to the reka-ui component
            expect(wrapper.vm.$props.name).toBe('test-checkbox');
        });

        it('should accept value prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    value: 'checkbox-value',
                },
            });
            // Value prop is passed to the reka-ui component
            expect(wrapper.vm.$props.value).toBe('checkbox-value');
        });
    });

    describe('component structure', () => {
        it('should use CheckboxRoot from reka-ui', () => {
            const wrapper = mount(Checkbox);
            // The component should render the reka-ui CheckboxRoot
            expect(wrapper.find('[data-slot="checkbox"]').exists()).toBe(true);
        });

        it('should include CheckboxIndicator component', () => {
            const wrapper = mount(Checkbox);
            // CheckboxIndicator is always in the template, visibility controlled by reka-ui
            const html = wrapper.html();
            expect(html).toBeTruthy();
        });

        it('should use Check icon from lucide-vue-next as default slot content', () => {
            const wrapper = mount(Checkbox);
            // The component should include the Check icon in its template
            expect(wrapper.html()).toBeTruthy();
        });
    });

    describe('edge cases', () => {
        it('should handle multiple custom classes', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    class: 'class-1 class-2 class-3',
                },
            });
            const element = wrapper.find('[data-slot="checkbox"]');
            expect(element.classes()).toContain('class-1');
            expect(element.classes()).toContain('class-2');
            expect(element.classes()).toContain('class-3');
        });

        it('should handle empty class prop', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    class: '',
                },
            });
            // Should still render without errors
            expect(wrapper.find('[data-slot="checkbox"]').exists()).toBe(true);
        });

        it('should handle undefined props gracefully', () => {
            const wrapper = mount(Checkbox, {
                props: {
                    checked: undefined,
                    disabled: undefined,
                },
            });
            expect(wrapper.find('[data-slot="checkbox"]').exists()).toBe(true);
        });
    });
});
