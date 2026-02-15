import AcceptInvitation from '@/pages/auth/AcceptInvitation.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head" :title="title">{{ title }}</div>', props: ['title'] },
    router: {
        post: vi.fn(),
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :type="type" :disabled="disabled" :class="classValue"><slot /></button>',
        props: ['type', 'disabled', 'class'],
        computed: {
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :id="id" :type="type" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" :disabled="disabled" :required="required" :autocomplete="autocomplete" :placeholder="placeholder" :class="classValue" />',
        props: ['id', 'type', 'modelValue', 'disabled', 'required', 'autocomplete', 'placeholder', 'class'],
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
        template: '<label :for="forAttr" :class="classValue"><slot /></label>',
        props: { for: String, class: String },
        computed: {
            forAttr(): string {
                return (this as any).for;
            },
            classValue(): any {
                return (this as any).class;
            },
        },
    },
}));

describe('AcceptInvitation Page', () => {
    const defaultProps = {
        token: 'test-token-123',
        email: 'test@example.com',
        name: 'Test User',
    };

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: {
                            props: {
                                name: 'Test Platform',
                            },
                        },
                    },
                },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Accept Invitation');
        });

        it('should display welcome heading with platform name', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: {
                            props: {
                                name: 'My Blog Platform',
                            },
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Welcome to My Blog Platform');
        });

        it('should display welcome heading with default text when no platform name', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: {
                            props: {},
                        },
                    },
                },
            });

            expect(wrapper.text()).toContain('Welcome to the platform');
        });

        it('should display personalized greeting with user name', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect(wrapper.text()).toContain('Hi Test User, set up your password to get started');
        });

        it('should display expiration notice', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect(wrapper.text()).toContain('This invitation link will expire in 48 hours');
        });
    });

    describe('form fields', () => {
        it('should render email field as disabled', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('disabled')).toBeDefined();
        });

        it('should pre-fill email field with provided email', () => {
            const wrapper = mount(AcceptInvitation, {
                props: {
                    ...defaultProps,
                    email: 'custom@example.com',
                },
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const emailInput = wrapper.findComponent({ name: 'Input' });
            expect(emailInput.props('modelValue')).toBe('custom@example.com');
        });

        it('should render password field', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('required')).toBeDefined();
        });

        it('should render password confirmation field', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const confirmInput = wrapper.find('input#password_confirmation');
            expect(confirmInput.exists()).toBe(true);
            expect(confirmInput.attributes('type')).toBe('password');
            expect(confirmInput.attributes('required')).toBeDefined();
        });

        it('should have labels for all fields', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            expect(labels).toHaveLength(3);
            expect(labels[0].text()).toBe('Email');
            expect(labels[1].text()).toBe('Password');
            expect(labels[2].text()).toBe('Confirm Password');
        });

        it('should have autocomplete attributes for password fields', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            expect(passwordInput.attributes('autocomplete')).toBe('new-password');
            expect(confirmInput.attributes('autocomplete')).toBe('new-password');
        });

        it('should have descriptive placeholders', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            expect(passwordInput.attributes('placeholder')).toContain('minimum 14 characters');
            expect(confirmInput.attributes('placeholder')).toContain('Confirm your password');
        });
    });

    describe('password validation', () => {
        it('should show warning when password is too short', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            await passwordInput.setValue('short');

            expect(wrapper.text()).toContain('Password must be at least 14 characters');
        });

        it('should show success message when password meets requirements', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            await passwordInput.setValue('a-very-long-password-that-meets-requirements');

            expect(wrapper.text()).toContain('✓ Password meets requirements');
        });

        it('should show warning when passwords do not match', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            await passwordInput.setValue('a-very-long-password');
            await confirmInput.setValue('different-password');

            expect(wrapper.text()).toContain('Passwords do not match');
        });

        it('should show success message when passwords match', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            expect(wrapper.text()).toContain('✓ Passwords match');
        });

        it('should display password requirements info box', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect(wrapper.text()).toContain('Password Requirements');
            expect(wrapper.text()).toContain('At least 14 characters long');
        });
    });

    describe('submit button', () => {
        it('should render submit button', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Set Password & Continue');
        });

        it('should disable button when form is invalid', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(true);
        });

        it('should enable button when form is valid', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should show processing state when submitting', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            const form = wrapper.find('form');
            await form.trigger('submit.prevent');

            expect(wrapper.text()).toContain('Setting Password...');
        });
    });

    describe('form submission', () => {
        it('should submit form data to correct endpoint', async () => {
            const { router } = await import('@inertiajs/vue3');
            
            const wrapper = mount(AcceptInvitation, {
                props: {
                    token: 'custom-token-456',
                    email: 'user@example.com',
                    name: 'John Doe',
                },
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            const form = wrapper.find('form');
            await form.trigger('submit.prevent');

            expect(router.post).toHaveBeenCalledWith(
                '/invitation/accept/custom-token-456',
                expect.objectContaining({
                    password: validPassword,
                    password_confirmation: validPassword,
                }),
                expect.any(Object)
            );
        });

        it('should clear errors before submission', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            // Set some errors
            (wrapper.vm as any).errors = { password: 'Some error' };

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            const form = wrapper.find('form');
            await form.trigger('submit.prevent');

            expect((wrapper.vm as any).errors).toEqual({});
        });

        it('should set processing state during submission', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect((wrapper.vm as any).processing).toBe(false);

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);

            const form = wrapper.find('form');
            await form.trigger('submit.prevent');

            expect((wrapper.vm as any).processing).toBe(true);
        });
    });

    describe('error handling', () => {
        it('should display password error message', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            (wrapper.vm as any).errors = { password: 'Password is too weak' };
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Password is too weak');
        });

        it('should display password confirmation error message', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            (wrapper.vm as any).errors = { password_confirmation: 'Passwords must match' };
            await wrapper.vm.$nextTick();

            expect(wrapper.text()).toContain('Passwords must match');
        });

        it('should add error styling to password field with error', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            (wrapper.vm as any).errors = { password: 'Some error' };
            await wrapper.vm.$nextTick();

            const passwordInputs = wrapper.findAllComponents({ name: 'Input' });
            const passwordInput = passwordInputs.find(input => input.props('id') === 'password');
            
            expect(passwordInput?.props('class')).toContain('border-red-500');
        });

        it('should add error styling to confirmation field with error', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            (wrapper.vm as any).errors = { password_confirmation: 'Some error' };
            await wrapper.vm.$nextTick();

            const passwordInputs = wrapper.findAllComponents({ name: 'Input' });
            const confirmInput = passwordInputs.find(input => input.props('id') === 'password_confirmation');
            
            expect(confirmInput?.props('class')).toContain('border-red-500');
        });
    });

    describe('layout', () => {
        it('should have centered layout', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const mainContainer = wrapper.find('.flex.min-h-screen');
            expect(mainContainer.exists()).toBe(true);
            expect(mainContainer.classes()).toContain('items-center');
            expect(mainContainer.classes()).toContain('justify-center');
        });

        it('should have form spacing', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const form = wrapper.find('form');
            expect(form.classes()).toContain('space-y-6');
        });

        it('should have card styling', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const card = wrapper.find('.bg-white.dark\\:bg-gray-800');
            expect(card.exists()).toBe(true);
            expect(card.classes()).toContain('shadow-sm');
            expect(card.classes()).toContain('rounded-lg');
        });
    });

    describe('accessibility', () => {
        it('should associate labels with inputs', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            expect(labels[0].props('for')).toBe('email');
            expect(labels[1].props('for')).toBe('password');
            expect(labels[2].props('for')).toBe('password_confirmation');
        });

        it('should have submit button type', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('type')).toBe('submit');
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Accept Invitation');
        });

        it('should mark password fields as required', () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            expect(passwordInput.attributes('required')).toBeDefined();
            expect(confirmInput.attributes('required')).toBeDefined();
        });
    });

    describe('computed properties', () => {
        it('should correctly compute isPasswordValid', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect((wrapper.vm as any).isPasswordValid).toBe(false);

            const passwordInput = wrapper.find('input#password');
            await passwordInput.setValue('12345678901234'); // 14 chars
            
            expect((wrapper.vm as any).isPasswordValid).toBe(true);
        });

        it('should correctly compute isPasswordConfirmed', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect((wrapper.vm as any).isPasswordConfirmed).toBe(false);

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            await passwordInput.setValue('matching-password');
            await confirmInput.setValue('matching-password');
            
            expect((wrapper.vm as any).isPasswordConfirmed).toBe(true);
        });

        it('should correctly compute isFormValid', async () => {
            const wrapper = mount(AcceptInvitation, {
                props: defaultProps,
                global: {
                    mocks: {
                        $page: { props: {} },
                    },
                },
            });

            expect((wrapper.vm as any).isFormValid).toBe(false);

            const passwordInput = wrapper.find('input#password');
            const confirmInput = wrapper.find('input#password_confirmation');
            
            const validPassword = 'a-very-long-password-that-meets-requirements';
            await passwordInput.setValue(validPassword);
            await confirmInput.setValue(validPassword);
            
            expect((wrapper.vm as any).isFormValid).toBe(true);
        });
    });
});
