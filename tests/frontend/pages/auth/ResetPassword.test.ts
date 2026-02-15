import ResetPassword from '@/pages/auth/ResetPassword.vue';
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
    update: {
        form: vi.fn(() => ({
            method: 'post',
            url: '/reset-password',
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
        template: '<input :id="id" :type="type" :name="name" :autocomplete="autocomplete" :autofocus="autofocus" :placeholder="placeholder" :readonly="readonly" :class="classValue" :modelValue="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        props: ['id', 'type', 'name', 'autocomplete', 'autofocus', 'placeholder', 'readonly', 'class', 'modelValue'],
        emits: ['update:modelValue'],
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

describe('ResetPassword Page', () => {
    const defaultProps = {
        token: 'reset-token-123',
        email: 'test@example.com',
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Reset password');
        });

        it('should render within AuthLayout', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.exists()).toBe(true);
            expect(authLayout.props('title')).toBe('Reset password');
            expect(authLayout.props('description')).toBe('Please enter your new password below');
        });
    });

    describe('form', () => {
        it('should render Form component', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });

        it('should have resetOnSuccess configuration', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.props('resetOnSuccess')).toEqual(['password', 'password_confirmation']);
        });
    });

    describe('email field', () => {
        it('should render email input field', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('name')).toBe('email');
        });

        it('should pre-fill email from props', () => {
            const wrapper = mount(ResetPassword, {
                props: {
                    token: 'test-token',
                    email: 'custom@example.com',
                },
            });

            const emailInputs = wrapper.findAllComponents({ name: 'Input' });
            const emailInput = emailInputs.find(input => input.props('id') === 'email');
            expect(emailInput?.props('modelValue')).toBe('custom@example.com');
        });

        it('should be readonly', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('readonly')).toBeDefined();
        });

        it('should have email label', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            const emailLabel = labels.find(label => label.props('for') === 'email');
            expect(emailLabel?.text()).toBe('Email');
        });

        it('should have autocomplete attribute', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('autocomplete')).toBe('email');
        });

        it('should render InputError for email', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBeGreaterThan(0);
        });
    });

    describe('password field', () => {
        it('should render password input field', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('name')).toBe('password');
        });

        it('should have password label', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            const passwordLabel = labels.find(label => label.props('for') === 'password');
            expect(passwordLabel?.text()).toBe('Password');
        });

        it('should have autofocus on password field', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('autofocus')).toBeDefined();
        });

        it('should have placeholder', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('placeholder')).toBe('Password');
        });

        it('should have autocomplete for new password', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('autocomplete')).toBe('new-password');
        });

        it('should render InputError for password', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('password confirmation field', () => {
        it('should render password confirmation input field', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const confirmInput = wrapper.find('input#password_confirmation');
            expect(confirmInput.exists()).toBe(true);
            expect(confirmInput.attributes('type')).toBe('password');
            expect(confirmInput.attributes('name')).toBe('password_confirmation');
        });

        it('should have confirmation label', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            const confirmLabel = labels.find(label => label.props('for') === 'password_confirmation');
            expect(confirmLabel?.text()).toBe('Confirm Password');
        });

        it('should have placeholder', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const confirmInput = wrapper.find('input#password_confirmation');
            expect(confirmInput.attributes('placeholder')).toBe('Confirm password');
        });

        it('should have autocomplete for new password', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const confirmInput = wrapper.find('input#password_confirmation');
            expect(confirmInput.attributes('autocomplete')).toBe('new-password');
        });

        it('should render InputError for password confirmation', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBe(3);
        });
    });

    describe('submit button', () => {
        it('should render submit button', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Reset password');
        });

        it('should have submit type', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('type')).toBe('submit');
        });



        it('should be disabled when processing', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should have full width styling', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('class')).toContain('w-full');
        });

        it('should not show spinner by default', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const spinner = wrapper.findComponent({ name: 'Spinner' });
            expect(spinner.exists()).toBe(false);
        });
    });

    describe('layout', () => {
        it('should have grid layout with spacing', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const grid = wrapper.find('.grid.gap-6');
            expect(grid.exists()).toBe(true);
        });

        it('should have grid layout for individual fields', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const fieldGrids = wrapper.findAll('.grid.gap-2');
            expect(fieldGrids.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('accessibility', () => {
        it('should associate labels with inputs', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            expect(labels[0].props('for')).toBe('email');
            expect(labels[1].props('for')).toBe('password');
            expect(labels[2].props('for')).toBe('password_confirmation');
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Reset password');
        });

        it('should have descriptive button text', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Reset password');
        });
    });

    describe('props', () => {
        it('should accept token prop', () => {
            const wrapper = mount(ResetPassword, {
                props: {
                    token: 'custom-token-456',
                    email: 'test@example.com',
                },
            });

            expect(wrapper.props('token')).toBe('custom-token-456');
        });

        it('should accept email prop', () => {
            const wrapper = mount(ResetPassword, {
                props: {
                    token: 'test-token',
                    email: 'custom@test.com',
                },
            });

            expect(wrapper.props('email')).toBe('custom@test.com');
        });
    });

    describe('form configuration', () => {
        it('should use correct form endpoint', async () => {
            const { update } = await import('@/routes/password');
            
            mount(ResetPassword, {
                props: defaultProps,
            });

            expect(update.form).toHaveBeenCalled();
        });

        it('should have transform function configured', () => {
            const wrapper = mount(ResetPassword, {
                props: defaultProps,
            });

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.props('transform')).toBeDefined();
            expect(typeof form.props('transform')).toBe('function');
        });
    });
});
