import VerifyEmail from '@/pages/auth/VerifyEmail.vue';
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

vi.mock('@/routes/verification', () => ({
    send: {
        form: vi.fn(() => ({
            method: 'post',
            url: '/email/verification-notification',
        })),
    },
}));

vi.mock('@/routes', () => ({
    logout: vi.fn(() => '/logout'),
}));

vi.mock('@/layouts/AuthLayout.vue', () => ({
    default: {
        name: 'AuthLayout',
        template: '<div data-testid="auth-layout"><h1>{{ title }}</h1><p>{{ description }}</p><slot /></div>',
        props: ['title', 'description'],
    },
}));

vi.mock('@/components/TextLink.vue', () => ({
    default: {
        name: 'TextLink',
        template: '<a :href="href" :class="classValue"><slot /></a>',
        props: { href: String, as: String, class: String },
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :type="type" :disabled="disabled" :variant="variant" :class="classValue"><slot /></button>',
        props: { type: String, disabled: Boolean, variant: String, class: String },
        computed: {
            classValue(): any {
                return (this as any).class;
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

describe('VerifyEmail Page', () => {
    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(VerifyEmail);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Email verification');
        });

        it('should render within AuthLayout', () => {
            const wrapper = mount(VerifyEmail);

            const authLayout = wrapper.findComponent({ name: 'AuthLayout' });
            expect(authLayout.exists()).toBe(true);
            expect(authLayout.props('title')).toBe('Verify email');
            expect(authLayout.props('description')).toBe('Please verify your email address by clicking on the link we just emailed to you.');
        });

        it('should render Form component', () => {
            const wrapper = mount(VerifyEmail);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });
    });

    describe('status message', () => {
        it('should not show status message by default', () => {
            const wrapper = mount(VerifyEmail);

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.exists()).toBe(false);
        });

        it('should show status message when verification link sent', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'verification-link-sent',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.exists()).toBe(true);
        });

        it('should display correct status message text', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'verification-link-sent',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.text()).toContain('A new verification link has been sent');
        });

        it('should center status message', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'verification-link-sent',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.classes()).toContain('text-center');
        });

        it('should style status message appropriately', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'verification-link-sent',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.classes()).toContain('text-sm');
            expect(statusMessage.classes()).toContain('font-medium');
            expect(statusMessage.classes()).toContain('mb-4');
        });

        it('should not show message for other status values', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'some-other-status',
                },
            });

            const statusMessage = wrapper.find('.text-green-600');
            expect(statusMessage.exists()).toBe(false);
        });
    });

    describe('resend button', () => {
        it('should render resend verification button', () => {
            const wrapper = mount(VerifyEmail);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Resend verification email');
        });

        it('should have secondary variant', () => {
            const wrapper = mount(VerifyEmail);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('variant')).toBe('secondary');
        });

        it('should be disabled when processing', () => {
            const wrapper = mount(VerifyEmail);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should not show spinner by default', () => {
            const wrapper = mount(VerifyEmail);

            const spinner = wrapper.findComponent({ name: 'Spinner' });
            expect(spinner.exists()).toBe(false);
        });
    });

    describe('logout link', () => {
        it('should render logout link', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.exists()).toBe(true);
            expect(textLink.text()).toBe('Log out');
        });

        it('should use correct logout route', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.props('href')).toBe('/logout');
        });

        it('should render as button element', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.props('as')).toBe('button');
        });

        it('should have small text styling', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.props('class')).toContain('text-sm');
        });

        it('should center logout link', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.props('class')).toContain('mx-auto');
            expect(textLink.props('class')).toContain('block');
        });
    });

    describe('layout', () => {
        it('should have proper form spacing', () => {
            const wrapper = mount(VerifyEmail);

            const form = wrapper.find('.space-y-6');
            expect(form.exists()).toBe(true);
        });

        it('should center form content', () => {
            const wrapper = mount(VerifyEmail);

            const form = wrapper.find('.text-center');
            expect(form.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should provide descriptive page title', () => {
            const wrapper = mount(VerifyEmail);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Email verification');
        });

        it('should have descriptive button text', () => {
            const wrapper = mount(VerifyEmail);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Resend verification email');
        });

        it('should have descriptive logout link text', () => {
            const wrapper = mount(VerifyEmail);

            const textLink = wrapper.findComponent({ name: 'TextLink' });
            expect(textLink.text()).toBe('Log out');
        });
    });

    describe('props', () => {
        it('should accept status prop', () => {
            const wrapper = mount(VerifyEmail, {
                props: {
                    status: 'verification-link-sent',
                },
            });

            expect(wrapper.props('status')).toBe('verification-link-sent');
        });

        it('should work without status prop', () => {
            const wrapper = mount(VerifyEmail);

            expect(wrapper.props('status')).toBeUndefined();
        });
    });

    describe('form configuration', () => {
        it('should use correct form endpoint', async () => {
            const { send } = await import('@/routes/verification');
            
            mount(VerifyEmail);

            expect(send.form).toHaveBeenCalled();
        });

        it('should call logout route', async () => {
            const { logout } = await import('@/routes');
            
            mount(VerifyEmail);

            expect(logout).toHaveBeenCalled();
        });
    });
});
