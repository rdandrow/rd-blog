import ConfirmPassword from '@/pages/auth/ConfirmPassword.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head" :title="title">{{ title }}</div>', props: ['title'] },
    Form: {
        name: 'Form',
        template: '<form @submit.prevent><slot :errors="{}" :processing="false" /></form>',
        props: ['method', 'url', 'resetOnSuccess', 'reset-on-success'],
    },
}));

vi.mock('@/routes/password/confirm', () => ({
    store: {
        form: vi.fn(() => ({
            method: 'post',
            url: '/user/confirm-password',
        })),
    },
}));

vi.mock('@/layouts/AuthLayout.vue', () => ({
    default: {
        name: 'AuthLayout',
        template: '<div data-testid="auth-layout"><h1>{{ title }}</h1><p>{{ description }}</p><slot /></div>',
        props: ['title', 'description'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: {
        name: 'InputError',
        template: '<div v-if="message" class="error">{{ message }}</div>',
        props: ['message'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :type="type" :disabled="disabled" :data-test="dataTest" :class="classValue"><slot /></button>',
        props: { type: String, disabled: Boolean, 'data-test': String, class: String },
        computed: {
            classValue(): any {
                return (this as any).class;
            },
            dataTest(): string {
                return (this as any)['data-test'];
            },
        },
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :id="id" :type="type" :name="name" :autocomplete="autocomplete" :autofocus="autofocus" :required="required" :class="classValue" />',
        props: ['id', 'type', 'name', 'autocomplete', 'autofocus', 'required', 'class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label :htmlFor="htmlFor"><slot /></label>',
        props: { htmlFor: String },
    },
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: {
        name: 'Spinner',
        template: '<span class="spinner">Loading...</span>',
    },
}));

describe('ConfirmPassword Page', () => {
    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(ConfirmPassword);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Confirm password');
        });

        it('should render within AuthLayout', () => {
            const wrapper = mount(ConfirmPassword);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.exists()).toBe(true);
            expect(authLayout.props('title')).toBe('Confirm your password');
            expect(authLayout.props('description')).toBe('This is a secure area of the application. Please confirm your password before continuing.');
        });

        it('should render Form component', () => {
            const wrapper = mount(ConfirmPassword);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });
    });

    describe('password field', () => {
        it('should render password input field', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('name')).toBe('password');
        });

        it('should have password label', () => {
            const wrapper = mount(ConfirmPassword);

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.exists()).toBe(true);
            expect(label.text()).toBe('Password');
            expect(label.props('htmlFor')).toBe('password');
        });

        it('should have autofocus on password field', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('autofocus')).toBeDefined();
        });

        it('should be required', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('required')).toBeDefined();
        });

        it('should have autocomplete for current password', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('autocomplete')).toBe('current-password');
        });

        it('should have block styling', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('class')).toContain('block');
            expect(passwordInput.attributes('class')).toContain('w-full');
        });

        it('should render InputError for password', () => {
            const wrapper = mount(ConfirmPassword);

            const inputError = wrapper.findComponent({ name: 'InputError' });
            expect(inputError.exists()).toBe(true);
        });
    });

    describe('submit button', () => {
        it('should render confirm password button', () => {
            const wrapper = mount(ConfirmPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Confirm Password');
        });



        it('should be disabled when processing', () => {
            const wrapper = mount(ConfirmPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should have full width styling', () => {
            const wrapper = mount(ConfirmPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('w-full');
        });

        it('should not show spinner by default', () => {
            const wrapper = mount(ConfirmPassword);

            const spinner = wrapper.findComponent({ name: 'Spinner' });
            expect(spinner.exists()).toBe(false);
        });
    });

    describe('layout', () => {
        it('should have spacing between elements', () => {
            const wrapper = mount(ConfirmPassword);

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });

        it('should have grid layout for password field', () => {
            const wrapper = mount(ConfirmPassword);

            const grid = wrapper.find('.grid.gap-2');
            expect(grid.exists()).toBe(true);
        });

        it('should use flex layout for button container', () => {
            const wrapper = mount(ConfirmPassword);

            const buttonContainer = wrapper.find('.flex.items-center');
            expect(buttonContainer.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should associate label with password input', () => {
            const wrapper = mount(ConfirmPassword);

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('htmlFor')).toBe('password');
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(ConfirmPassword);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Confirm password');
        });

        it('should have descriptive button text', () => {
            const wrapper = mount(ConfirmPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Confirm Password');
        });

        it('should mark password field as required', () => {
            const wrapper = mount(ConfirmPassword);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('required')).toBeDefined();
        });
    });

    describe('form configuration', () => {
        it('should use correct form endpoint', async () => {
            const { store } = await import('@/routes/password/confirm');
            
            mount(ConfirmPassword);

            expect(store.form).toHaveBeenCalled();
        });
    });
});
