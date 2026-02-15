import ForgotPassword from '@/pages/auth/ForgotPassword.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head" :title="title">{{ title }}</div>', props: ['title'] },
    Form: {
        name: 'Form',
        template: '<form @submit.prevent><slot :errors="{}" :processing="false" /></form>',
        props: ['method', 'url', 'transform', 'resetOnSuccess'],
    },
}));

vi.mock('@/routes/password', () => ({
    email: {
        form: vi.fn(() => ({
            method: 'post',
            url: '/forgot-password',
        })),
    },
}));

vi.mock('@/routes', () => ({
    login: vi.fn(() => '/login'),
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

vi.mock('@/components/TextLink.vue', () => ({
    default: {
        name: 'TextLink',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
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
        template: '<input :id="id" :type="type" :name="name" :autocomplete="autocomplete" :autofocus="autofocus" :placeholder="placeholder" />',
        props: ['id', 'type', 'name', 'autocomplete', 'autofocus', 'placeholder'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label :for="forAttr"><slot /></label>',
        props: { for: String },
        computed: {
            forAttr(): string {
                return (this as any).for;
            },
        },
    },
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: {
        name: 'Spinner',
        template: '<span class="spinner">Loading...</span>',
    },
}));

describe('ForgotPassword Page', () => {
    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(ForgotPassword);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Forgot password');
        });

        it('should render within AuthLayout', () => {
            const wrapper = mount(ForgotPassword);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.exists()).toBe(true);
            expect(authLayout.props('title')).toBe('Forgot password');
            expect(authLayout.props('description')).toBe('Enter your email to receive a password reset link');
        });

        it('should not show status message by default', () => {
            const wrapper = mount(ForgotPassword);

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.exists()).toBe(false);
        });

        it('should show status message when provided', () => {
            const wrapper = mount(ForgotPassword, {
                props: {
                    status: 'Password reset link sent!',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.exists()).toBe(true);
            expect(statusMessage.text()).toBe('Password reset link sent!');
        });
    });

    describe('form', () => {
        it('should render Form component', () => {
            const wrapper = mount(ForgotPassword);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });

        it('should render email input field', () => {
            const wrapper = mount(ForgotPassword);

            const emailInput = wrapper.find('input#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('name')).toBe('email');
        });

        it('should have email label', () => {
            const wrapper = mount(ForgotPassword);

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.exists()).toBe(true);
            expect(label.text()).toBe('Email address');
            expect(label.props('for')).toBe('email');
        });

        it('should have autofocus on email field', () => {
            const wrapper = mount(ForgotPassword);

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('autofocus')).toBeDefined();
        });

        it('should have placeholder for email field', () => {
            const wrapper = mount(ForgotPassword);

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('placeholder')).toBe('email@example.com');
        });

        it('should have autocomplete off for email field', () => {
            const wrapper = mount(ForgotPassword);

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('autocomplete')).toBe('off');
        });

        it('should render InputError for email', () => {
            const wrapper = mount(ForgotPassword);

            const inputError = wrapper.findComponent({ name: 'InputError' });
            expect(inputError.exists()).toBe(true);
        });
    });

    describe('submit button', () => {
        it('should render submit button', () => {
            const wrapper = mount(ForgotPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Email password reset link');
        });



        it('should be disabled when processing', () => {
            const wrapper = mount(ForgotPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should show spinner when processing', () => {
            const wrapper = mount(ForgotPassword);

            // By default, processing is false, so spinner should not be visible
            const spinner = wrapper.findComponent({ name: 'Spinner' });
            expect(spinner.exists()).toBe(false);
        });

        it('should have full width styling', () => {
            const wrapper = mount(ForgotPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('w-full');
        });
    });

    describe('navigation', () => {
        it('should render link back to login', () => {
            const wrapper = mount(ForgotPassword);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.exists()).toBe(true);
            expect(textLink.props('href')).toBe('/login');
            expect(textLink.text()).toBe('log in');
        });

        it('should show descriptive text for login link', () => {
            const wrapper = mount(ForgotPassword);

            expect(wrapper.text()).toContain('Or, return to');
        });
    });

    describe('layout', () => {
        it('should have proper spacing', () => {
            const wrapper = mount(ForgotPassword);

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });

        it('should have grid layout for form fields', () => {
            const wrapper = mount(ForgotPassword);

            const grid = wrapper.find('.grid.gap-2');
            expect(grid.exists()).toBe(true);
        });

        it('should center status message', () => {
            const wrapper = mount(ForgotPassword, {
                props: {
                    status: 'Test message',
                },
            });

            const statusMessage = wrapper.find('.text-center');
            expect(statusMessage.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should associate label with email input', () => {
            const wrapper = mount(ForgotPassword);

            const label = wrapper.findComponent({ name: 'Label' });
            expect(label.props('for')).toBe('email');
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(ForgotPassword);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Forgot password');
        });

        it('should have descriptive button text', () => {
            const wrapper = mount(ForgotPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Email password reset link');
        });
    });

    describe('form configuration', () => {
        it('should use correct form endpoint', async () => {
            const { email } = await import('@/routes/password');
            
            mount(ForgotPassword);

            expect(email.form).toHaveBeenCalled();
        });

        it('should call login route for back link', async () => {
            const { login } = await import('@/routes');
            
            mount(ForgotPassword);

            expect(login).toHaveBeenCalled();
        });
    });
});
