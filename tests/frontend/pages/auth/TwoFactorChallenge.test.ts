import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import TwoFactorChallenge from '@/pages/auth/TwoFactorChallenge.vue';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<head><slot /></head>' },
    Form: {
        name: 'Form',
        template: '<form><slot v-bind="{ errors: {}, processing: false, clearErrors: () => {} }" /></form>',
        props: {
            resetOnError: Boolean,
        },
        emits: ['error'],
    },
}));

vi.mock('@/layouts/AuthLayout.vue', () => ({
    default: {
        name: 'AuthLayout',
        template: '<div class="auth-layout"><h1>{{ title }}</h1><p>{{ description }}</p><slot /></div>',
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

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :type="type" :disabled="disabled"><slot /></button>',
        props: ['type', 'class', 'disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :type="type" :name="name" :placeholder="placeholder" :autofocus="autofocus" :required="required" />',
        props: {
            type: String,
            name: String,
            placeholder: String,
            autofocus: Boolean,
            required: Boolean,
        },
    },
}));

vi.mock('@/components/ui/pin-input', () => ({
    PinInput: {
        name: 'PinInput',
        template: '<div class="pin-input"><slot /></div>',
        props: {
            id: String,
            placeholder: String,
            type: String,
            otp: Boolean,
            modelValue: Array,
        },
        emits: ['update:modelValue'],
    },
    PinInputGroup: {
        name: 'PinInputGroup',
        template: '<div class="pin-input-group"><slot /></div>',
    },
    PinInputSlot: {
        name: 'PinInputSlot',
        template: '<input class="pin-slot" />',
        props: {
            index: Number,
            disabled: Boolean,
            autofocus: Boolean,
        },
    },
}));

vi.mock('@/routes/two-factor/login', () => ({
    store: {
        form: vi.fn(() => ({
            action: '/two-factor-challenge',
            method: 'post',
        })),
    },
}));

describe('Auth TwoFactorChallenge Page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('rendering - authentication code mode', () => {
        it('should render with authentication code title and description by default', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.text()).toContain('Authentication Code');
            expect(wrapper.text()).toContain('Enter the authentication code provided by your authenticator application.');
        });

        it('should render PIN input for authentication code', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.exists()).toBe(true);
        });

        it('should render 6 PIN input slots', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinSlots = wrapper.findAllComponents({ name: 'PinInputSlot' });
            expect(pinSlots.length).toBe(6);
        });

        it('should have PIN input with correct attributes', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.props('type')).toBe('number');
            expect(pinInput.props('otp')).toBe(true);
            expect(pinInput.props('placeholder')).toBe('○');
        });

        it('should render Continue button', () => {
            const wrapper = mount(TwoFactorChallenge);

            const continueButton = wrapper.findComponent({ name: 'Button' });
            expect(continueButton.exists()).toBe(true);
            expect(continueButton.text()).toBe('Continue');
        });

        it('should render toggle link to recovery code mode', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.text()).toContain('or you can');
            expect(wrapper.text()).toContain('login using a recovery code');
        });
    });

    describe('rendering - recovery code mode', () => {
        it('should switch to recovery code mode when toggle is clicked', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('Recovery Code');
            expect(wrapper.text()).toContain('Please confirm access to your account by entering one of your emergency recovery codes.');
        });

        it('should render text input for recovery code', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            const recoveryInput = wrapper.findComponent({ name: 'Input' });
            expect(recoveryInput.exists()).toBe(true);
            expect(recoveryInput.props('name')).toBe('recovery_code');
            expect(recoveryInput.props('type')).toBe('text');
        });

        it('should have autofocus on recovery code input', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            const recoveryInput = wrapper.findComponent({ name: 'Input' });
            expect(recoveryInput.props('autofocus')).toBe(true);
        });

        it('should render toggle link to authentication code mode', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('login using an authentication code');
        });
    });

    describe('mode toggling', () => {
        it('should toggle between authentication code and recovery code modes', async () => {
            const wrapper = mount(TwoFactorChallenge);

            // Start in auth code mode
            expect(wrapper.text()).toContain('Authentication Code');

            // Toggle to recovery mode
            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('Recovery Code');

            // Toggle back to auth code mode
            const toggleButtonAgain = wrapper.find('button[type="button"]');
            await toggleButtonAgain.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('Authentication Code');
        });

        it('should hide PIN input when in recovery code mode', async () => {
            const wrapper = mount(TwoFactorChallenge);

            let pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.exists()).toBe(true);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.exists()).toBe(false);
        });

        it('should hide recovery input when in authentication code mode', () => {
            const wrapper = mount(TwoFactorChallenge);

            const recoveryInput = wrapper.find('input[name="recovery_code"]');
            expect(recoveryInput.exists()).toBe(false);
        });
    });

    describe('form submission', () => {
        it('should have hidden input for code value in auth mode', () => {
            const wrapper = mount(TwoFactorChallenge);

            const hiddenInput = wrapper.find('input[type="hidden"][name="code"]');
            expect(hiddenInput.exists()).toBe(true);
        });

        it('should have Continue button as submit button', () => {
            const wrapper = mount(TwoFactorChallenge);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('type')).toBe('submit');
        });

        it('should have form with correct structure', () => {
            const wrapper = mount(TwoFactorChallenge);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should have descriptive title and description', () => {
            const wrapper = mount(TwoFactorChallenge);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.props('title')).toBe('Authentication Code');
            expect(authLayout.props('description')).toContain('authentication code provided by your authenticator application');
        });

        it('should have autofocus on first PIN slot', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinSlots = wrapper.findAllComponents({ name: 'PinInputSlot' });
            expect(pinSlots[0].props('autofocus')).toBe(true);
        });

        it('should have required attribute on recovery code input', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            const recoveryInput = wrapper.findComponent({ name: 'Input' });
            expect(recoveryInput.props('required')).toBe(true);
        });

        it('should have appropriate placeholder for recovery code', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            const recoveryInput = wrapper.findComponent({ name: 'Input' });
            expect(recoveryInput.props('placeholder')).toBe('Enter recovery code');
        });
    });

    describe('error handling', () => {
        it('should have InputError component for code errors', () => {
            const wrapper = mount(TwoFactorChallenge);

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBeGreaterThan(0);
        });

        it('should have InputError component for recovery code errors', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            const inputErrors = wrapper.findAllComponents({ name: 'InputError' });
            expect(inputErrors.length).toBeGreaterThan(0);
        });
    });

    describe('user experience', () => {
        it('should provide clear instructions for authentication code', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.text()).toContain('Enter the authentication code provided by your authenticator application');
        });

        it('should provide clear instructions for recovery code', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('Please confirm access to your account by entering one of your emergency recovery codes');
        });

        it('should have toggle button with underlined text', () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            expect(toggleButton.classes()).toContain('underline');
        });

        it('should show alternative login method option', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.text()).toContain('or you can');
        });
    });

    describe('security features', () => {
        it('should use number type for PIN input', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.props('type')).toBe('number');
        });

        it('should use OTP mode for PIN input', () => {
            const wrapper = mount(TwoFactorChallenge);

            const pinInput = wrapper.findComponent({ name: 'PinInput' });
            expect(pinInput.props('otp')).toBe(true);
        });

        it('should reset form on error', () => {
            const wrapper = mount(TwoFactorChallenge);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.props('resetOnError')).toBe(true);
        });
    });

    describe('edge cases', () => {
        it('should handle empty code array', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.exists()).toBe(true);
            const hiddenInput = wrapper.find('input[type="hidden"][name="code"]');
            expect(hiddenInput.attributes('value')).toBe('');
        });

        it('should render correctly without errors', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.find('form').exists()).toBe(true);
        });

        it('should handle multiple toggles correctly', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const toggleButton = wrapper.find('button[type="button"]');
            
            // Toggle multiple times
            for (let i = 0; i < 5; i++) {
                await toggleButton.trigger('click');
                await nextTick();
            }

            // Should be in recovery mode after odd number (5) of toggles
            expect(wrapper.text()).toContain('Recovery Code');
        });
    });

    describe('component structure', () => {
        it('should have AuthLayout as wrapper', () => {
            const wrapper = mount(TwoFactorChallenge);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.exists()).toBe(true);
        });

        it('should have Form component', () => {
            const wrapper = mount(TwoFactorChallenge);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });

        it('should have proper spacing classes', () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.html()).toContain('space-y-');
        });
    });

    describe('dynamic content', () => {
        it('should update title based on mode', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.props('title')).toBe('Authentication Code');

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(authLayout.props('title')).toBe('Recovery Code');
        });

        it('should update description based on mode', async () => {
            const wrapper = mount(TwoFactorChallenge);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.props('description')).toContain('authentication code provided by your authenticator application');

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(authLayout.props('description')).toContain('emergency recovery codes');
        });

        it('should update toggle text based on mode', async () => {
            const wrapper = mount(TwoFactorChallenge);

            expect(wrapper.text()).toContain('login using a recovery code');

            const toggleButton = wrapper.find('button[type="button"]');
            await toggleButton.trigger('click');
            await nextTick();

            expect(wrapper.text()).toContain('login using an authentication code');
        });
    });
});
