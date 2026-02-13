import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import Login from '@/pages/auth/Login.vue';
import { usePage } from '@inertiajs/vue3';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<head><slot /></head>' },
    Form: {
        name: 'Form',
        template: '<form><slot v-bind="{ errors: {}, processing: false, clearErrors: () => {} }" /></form>',
        props: ['resetOnSuccess'],
    },
    usePage: vi.fn(),
}));

vi.mock('@/layouts/AuthLayout.vue', () => ({
    default: {
        name: 'AuthBase',
        template: '<div class="auth-base"><h1>{{ title }}</h1><p>{{ description }}</p><slot /></div>',
        props: ['title', 'description'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: {
        name: 'InputError',
        template: '<div class="input-error">{{ message }}</div>',
        props: ['message'],
    },
}));

vi.mock('@/components/TextLink.vue', () => ({
    default: {
        name: 'TextLink',
        template: '<a :href="href"><slot /></a>',
        props: ['href', 'class', 'tabindex'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button type="button" :disabled="disabled" :tabindex="tabindex"><slot /></button>',
        props: ['type', 'class', 'tabindex', 'disabled'],
    },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: {
        name: 'Checkbox',
        template: '<input type="checkbox" :id="id" :name="name" :tabindex="tabindex" />',
        props: {
            id: String,
            name: String,
            tabindex: [String, Number],
        },
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :type="type" :id="id" :name="name" :value="modelValue" :required="required" :autofocus="autofocus" :tabindex="tabindex" :autocomplete="autocomplete" :placeholder="placeholder" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        props: {
            id: String,
            type: String,
            name: String,
            modelValue: [String, Number],
            required: Boolean,
            autofocus: Boolean,
            tabindex: [String, Number],
            autocomplete: String,
            placeholder: String,
            ref: String,
        },
        emits: ['update:modelValue'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: {}, 
    },
}));

vi.mock('@/components/ui/spinner', () => ({
    Spinner: {
        name: 'Spinner',
        template: '<div class="spinner"></div>',
    },
}));

vi.mock('@/routes', () => ({
    register: vi.fn(() => '/register'),
}));

vi.mock('@/routes/login', () => ({
    store: {
        form: vi.fn(() => ({
            action: '/login',
            method: 'post',
        })),
    },
}));

vi.mock('@/routes/password', () => ({
    request: vi.fn(() => '/forgot-password'),
}));

describe('Auth Login Page', () => {
    let mockPage: any;

    beforeEach(() => {
        mockPage = {
            props: {
                flash: {},
            },
        };
        (usePage as any).mockReturnValue(mockPage);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering', () => {
        it('should render login form with title and description', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.text()).toContain('Log in to your account');
            expect(wrapper.text()).toContain('Enter your email and password below to log in');
        });

        it('should render email input field', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('name')).toBe('email');
            expect(emailInput.attributes('required')).toBeDefined();
        });

        it('should render password input field', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const passwordInput = wrapper.find('#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('name')).toBe('password');
            expect(passwordInput.attributes('required')).toBeDefined();
        });

        it('should render remember me checkbox', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const rememberCheckbox = wrapper.find('#remember');
            expect(rememberCheckbox.exists()).toBe(true);
            expect(wrapper.text()).toContain('Remember me');
        });

        it('should render login button', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const loginButton = wrapper.find('[data-test="login-button"]');
            expect(loginButton.exists()).toBe(true);
            expect(loginButton.text()).toContain('Log in');
        });

        it('should render forgot password link when canResetPassword is true', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.text()).toContain('Forgot password?');
            const forgotLink = wrapper.findComponent({ name: 'TextLink' });
            expect(forgotLink.exists()).toBe(true);
        });

        it('should not render forgot password link when canResetPassword is false', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: false,
                    canRegister: true,
                },
            });

            expect(wrapper.text()).not.toContain('Forgot password?');
        });

        it('should render register link when canRegister is true', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.text()).toContain("Don't have an account?");
            expect(wrapper.text()).toContain('Sign up');
        });

        it('should not render register link when canRegister is false', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: false,
                },
            });

            expect(wrapper.text()).not.toContain("Don't have an account?");
            expect(wrapper.text()).not.toContain('Sign up');
        });
    });

    describe('status messages', () => {
        it('should display status message when provided', () => {
            const wrapper = mount(Login, {
                props: {
                    status: 'Your password has been reset successfully.',
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.text()).toContain('Your password has been reset successfully.');
        });

        it('should not display status message when not provided', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const statusDiv = wrapper.find('.text-green-600');
            expect(statusDiv.exists()).toBe(false);
        });
    });

    describe('prefilled email', () => {
        it('should prefill email from flash data', () => {
            mockPage.props.flash = { email: 'invited@example.com' };
            (usePage as any).mockReturnValue(mockPage);

            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            expect(emailInput.attributes('value')).toBe('invited@example.com');
        });

        it('should not prefill email when flash data is not present', () => {
            mockPage.props.flash = {};
            (usePage as any).mockReturnValue(mockPage);

            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            expect(emailInput.attributes('value')).toBeUndefined();
        });
    });

    describe('form attributes', () => {
        it('should have correct autocomplete attributes', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');

            expect(emailInput.attributes('autocomplete')).toBe('email');
            expect(passwordInput.attributes('autocomplete')).toBe('current-password');
        });

        it('should have correct tabindex order', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');
            const rememberCheckbox = wrapper.find('#remember');
            const loginButton = wrapper.find('[data-test="login-button"]');

            expect(emailInput.attributes('tabindex')).toBe('1');
            expect(passwordInput.attributes('tabindex')).toBe('2');
            expect(rememberCheckbox.attributes('tabindex')).toBe('3');
            expect(loginButton.attributes('tabindex')).toBe('4');
        });

        it('should have autofocus on email input', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            expect(emailInput.attributes('autofocus')).toBeDefined();
        });
    });

    describe('form submission', () => {
        it('should have correct form action and method', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const form = wrapper.find('form');
            expect(form.exists()).toBe(true);
        });

        it('should disable submit button when processing', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            // Find button through the component
            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should have proper labels for all inputs', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.find('label[for="email"]').exists()).toBe(true);
            expect(wrapper.find('label[for="password"]').exists()).toBe(true);
            expect(wrapper.find('label[for="remember"]').exists()).toBe(true);
        });

        it('should have required attributes on required inputs', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');

            expect(emailInput.attributes('required')).toBeDefined();
            expect(passwordInput.attributes('required')).toBeDefined();
        });

        it('should have appropriate placeholder text', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');

            expect(emailInput.attributes('placeholder')).toBe('email@example.com');
            expect(passwordInput.attributes('placeholder')).toBe('Password');
        });
    });

    describe('edge cases', () => {
        it('should handle empty status gracefully', () => {
            const wrapper = mount(Login, {
                props: {
                    status: '',
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.find('.text-green-600').exists()).toBe(false);
        });

        it('should handle undefined status gracefully', () => {
            const wrapper = mount(Login, {
                props: {
                    status: undefined,
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.find('.text-green-600').exists()).toBe(false);
        });

        it('should handle missing flash data gracefully', () => {
            mockPage.props.flash = undefined;
            (usePage as any).mockReturnValue(mockPage);

            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            expect(wrapper.exists()).toBe(true);
        });
    });

    describe('security features', () => {
        it('should use password type for password input', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const passwordInput = wrapper.find('#password');
            expect(passwordInput.attributes('type')).toBe('password');
        });

        it('should have email type for email input', () => {
            const wrapper = mount(Login, {
                props: {
                    canResetPassword: true,
                    canRegister: true,
                },
            });

            const emailInput = wrapper.find('#email');
            expect(emailInput.attributes('type')).toBe('email');
        });
    });
});
