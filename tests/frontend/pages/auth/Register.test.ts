import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Register from '@/pages/auth/Register.vue';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<head><slot /></head>' },
    Form: {
        name: 'Form',
        template: '<form><slot v-bind="{ errors: {}, processing: false }" /></form>',
        props: ['resetOnSuccess'],
    },
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

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :type="type" :id="id" :name="name" :required="required" :autofocus="autofocus" :tabindex="tabindex" :autocomplete="autocomplete" :placeholder="placeholder" />',
        props: {
            id: String,
            type: String,
            name: String,
            required: Boolean,
            autofocus: Boolean,
            tabindex: [String, Number],
            autocomplete: String,
            placeholder: String,
        },
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
    login: vi.fn(() => '/login'),
}));

vi.mock('@/routes/register', () => ({
    store: {
        form: vi.fn(() => ({
            action: '/register',
            method: 'post',
        })),
    },
}));

describe('Auth Register Page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering', () => {
        it('should render registration form with title and description', () => {
            const wrapper = mount(Register);

            expect(wrapper.text()).toContain('Create an account');
            expect(wrapper.text()).toContain('Enter your details below to create your account');
        });

        it('should render name input field', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            expect(nameInput.exists()).toBe(true);
            expect(nameInput.attributes('type')).toBe('text');
            expect(nameInput.attributes('name')).toBe('name');
            expect(nameInput.attributes('required')).toBeDefined();
        });

        it('should render email input field', () => {
            const wrapper = mount(Register);

            const emailInput = wrapper.find('#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('name')).toBe('email');
            expect(emailInput.attributes('required')).toBeDefined();
        });

        it('should render password input field', () => {
            const wrapper = mount(Register);

            const passwordInput = wrapper.find('#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('name')).toBe('password');
            expect(passwordInput.attributes('required')).toBeDefined();
        });

        it('should render password confirmation input field', () => {
            const wrapper = mount(Register);

            const confirmPasswordInput = wrapper.find('#password_confirmation');
            expect(confirmPasswordInput.exists()).toBe(true);
            expect(confirmPasswordInput.attributes('type')).toBe('password');
            expect(confirmPasswordInput.attributes('name')).toBe('password_confirmation');
            expect(confirmPasswordInput.attributes('required')).toBeDefined();
        });

        it('should render register button', () => {
            const wrapper = mount(Register);

            const registerButton = wrapper.find('[data-test="register-user-button"]');
            expect(registerButton.exists()).toBe(true);
            expect(registerButton.text()).toContain('Create account');
        });

        it('should render login link', () => {
            const wrapper = mount(Register);

            expect(wrapper.text()).toContain('Already have an account?');
            expect(wrapper.text()).toContain('Log in');
        });
    });

    describe('two-factor authentication notice', () => {
        it('should display 2FA requirement notice', () => {
            const wrapper = mount(Register);

            expect(wrapper.text()).toContain('Two-Factor Authentication Required');
            expect(wrapper.text()).toContain("For your security, you'll be asked to set up two-factor authentication after creating your account.");
        });

        it('should have styled 2FA notice box', () => {
            const wrapper = mount(Register);

            const noticeBox = wrapper.find('.bg-blue-50');
            expect(noticeBox.exists()).toBe(true);
        });
    });

    describe('password requirements', () => {
        it('should display password length requirement', () => {
            const wrapper = mount(Register);

            expect(wrapper.text()).toContain('Must be at least 14 characters long');
        });

        it('should have password placeholder with minimum length hint', () => {
            const wrapper = mount(Register);

            const passwordInput = wrapper.find('#password');
            expect(passwordInput.attributes('placeholder')).toContain('minimum 14 characters');
        });
    });

    describe('form attributes', () => {
        it('should have correct autocomplete attributes', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');

            expect(nameInput.attributes('autocomplete')).toBe('name');
            expect(emailInput.attributes('autocomplete')).toBe('email');
            expect(passwordInput.attributes('autocomplete')).toBe('new-password');
            expect(confirmPasswordInput.attributes('autocomplete')).toBe('new-password');
        });

        it('should have correct tabindex order', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');
            const registerButton = wrapper.find('[data-test="register-user-button"]');

            expect(nameInput.attributes('tabindex')).toBe('1');
            expect(emailInput.attributes('tabindex')).toBe('2');
            expect(passwordInput.attributes('tabindex')).toBe('3');
            expect(confirmPasswordInput.attributes('tabindex')).toBe('4');
            expect(registerButton.attributes('tabindex')).toBe('5');
        });

        it('should have autofocus on name input', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            expect(nameInput.attributes('autofocus')).toBeDefined();
        });
    });

    describe('form submission', () => {
        it('should have correct form structure', () => {
            const wrapper = mount(Register);

            const form = wrapper.find('form');
            expect(form.exists()).toBe(true);
        });

        it('should reset password fields on successful submission', () => {
            const wrapper = mount(Register);

            const formComponent = wrapper.findComponent({ name: 'Form' });
            expect(formComponent.props('resetOnSuccess')).toEqual(['password', 'password_confirmation']);
        });
    });

    describe('accessibility', () => {
        it('should have proper labels for all inputs', () => {
            const wrapper = mount(Register);

            expect(wrapper.find('label[for="name"]').exists()).toBe(true);
            expect(wrapper.find('label[for="email"]').exists()).toBe(true);
            expect(wrapper.find('label[for="password"]').exists()).toBe(true);
            expect(wrapper.find('label[for="password_confirmation"]').exists()).toBe(true);
        });

        it('should have label text matching input purpose', () => {
            const wrapper = mount(Register);

            expect(wrapper.find('label[for="name"]').text()).toBe('Name');
            expect(wrapper.find('label[for="email"]').text()).toBe('Email address');
            expect(wrapper.find('label[for="password"]').text()).toBe('Password');
            expect(wrapper.find('label[for="password_confirmation"]').text()).toBe('Confirm password');
        });

        it('should have required attributes on all inputs', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');

            expect(nameInput.attributes('required')).toBeDefined();
            expect(emailInput.attributes('required')).toBeDefined();
            expect(passwordInput.attributes('required')).toBeDefined();
            expect(confirmPasswordInput.attributes('required')).toBeDefined();
        });

        it('should have appropriate placeholder text', () => {
            const wrapper = mount(Register);

            const nameInput = wrapper.find('#name');
            const emailInput = wrapper.find('#email');
            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');

            expect(nameInput.attributes('placeholder')).toBe('Full name');
            expect(emailInput.attributes('placeholder')).toBe('email@example.com');
            expect(passwordInput.attributes('placeholder')).toContain('Password');
            expect(confirmPasswordInput.attributes('placeholder')).toBe('Confirm password');
        });
    });

    describe('security features', () => {
        it('should use password type for password inputs', () => {
            const wrapper = mount(Register);

            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');

            expect(passwordInput.attributes('type')).toBe('password');
            expect(confirmPasswordInput.attributes('type')).toBe('password');
        });

        it('should use email type for email input', () => {
            const wrapper = mount(Register);

            const emailInput = wrapper.find('#email');
            expect(emailInput.attributes('type')).toBe('email');
        });

        it('should have new-password autocomplete for password fields', () => {
            const wrapper = mount(Register);

            const passwordInput = wrapper.find('#password');
            const confirmPasswordInput = wrapper.find('#password_confirmation');

            expect(passwordInput.attributes('autocomplete')).toBe('new-password');
            expect(confirmPasswordInput.attributes('autocomplete')).toBe('new-password');
        });
    });

    describe('user experience', () => {
        it('should inform user about 2FA setup requirement before registration', () => {
            const wrapper = mount(Register);

            const text = wrapper.text();
            expect(text).toContain('Two-Factor Authentication Required');
            expect(text).toContain("you'll be asked to set up two-factor authentication after creating your account");
        });

        it('should provide clear password requirements', () => {
            const wrapper = mount(Register);

            expect(wrapper.text()).toContain('Must be at least 14 characters long');
        });

        it('should have login link for existing users', () => {
            const wrapper = mount(Register);

            const loginLink = wrapper.findComponent({ name: 'TextLink' });
            expect(loginLink.exists()).toBe(true);
            expect(loginLink.props('href')).toBe('/login');
        });
    });

    describe('edge cases', () => {
        it('should render correctly with all required props', () => {
            const wrapper = mount(Register);

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.find('form').exists()).toBe(true);
        });

        it('should have all input fields present', () => {
            const wrapper = mount(Register);

            expect(wrapper.find('#name').exists()).toBe(true);
            expect(wrapper.find('#email').exists()).toBe(true);
            expect(wrapper.find('#password').exists()).toBe(true);
            expect(wrapper.find('#password_confirmation').exists()).toBe(true);
        });
    });

    describe('validation feedback', () => {
        it('should have InputError components for each field', () => {
            const wrapper = mount(Register);

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBeGreaterThanOrEqual(4);
        });
    });
});
