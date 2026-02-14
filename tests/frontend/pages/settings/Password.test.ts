import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Password from '@/pages/settings/Password.vue';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<head><title>{{ title }}</title></head>',
        props: ['title'],
    },
    Form: {
        name: 'Form',
        template: '<form><slot v-bind="{ errors: {}, processing: false, recentlySuccessful: false }" /></form>',
        props: ['options', 'resetOnSuccess', 'resetOnError'],
    },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                },
            },
        },
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Settings/PasswordController', () => ({
    default: {
        update: {
            form: vi.fn(() => ({
                action: '/settings/password',
                method: 'PUT',
            })),
        },
    },
}));

vi.mock('@/routes/user-password', () => ({
    edit: vi.fn(() => ({ url: '/settings/password' })),
}));

vi.mock('@/components/InputError.vue', () => ({
    default: {
        name: 'InputError',
        template: '<div v-if="message" class="input-error">{{ message }}</div>',
        props: ['message'],
    },
}));

vi.mock('@/components/HeadingSmall.vue', () => ({
    default: {
        name: 'HeadingSmall',
        template: '<div><h2>{{ title }}</h2><p>{{ description }}</p></div>',
        props: ['title', 'description'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :disabled="disabled"><slot /></button>',
        props: ['disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input :id="id" :name="name" :type="type" :placeholder="placeholder" :autocomplete="autocomplete" />',
        props: ['id', 'name', 'type', 'placeholder', 'autocomplete'],
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

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div data-testid="app-layout"><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/layouts/settings/Layout.vue', () => ({
    default: {
        name: 'SettingsLayout',
        template: '<div data-testid="settings-layout"><slot /></div>',
    },
}));

describe('Password Settings Page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(Password);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.props('title')).toBe('Password settings');
        });

        it('should render within AppLayout', () => {
            const wrapper = mount(Password);

            const appLayout = wrapper.find('[data-testid="app-layout"]');
            expect(appLayout.exists()).toBe(true);
        });

        it('should render within SettingsLayout', () => {
            const wrapper = mount(Password);

            const settingsLayout = wrapper.find('[data-testid="settings-layout"]');
            expect(settingsLayout.exists()).toBe(true);
        });

        it('should render heading with title and description', () => {
            const wrapper = mount(Password);

            const heading = wrapper.findComponent({ name: 'HeadingSmall' });
            expect(heading.exists()).toBe(true);
            expect(heading.props('title')).toBe('Update password');
            expect(heading.props('description')).toBe('Ensure your account is using a long, random password to stay secure');
        });

        it('should render Form component', () => {
            const wrapper = mount(Password);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });
    });

    describe('form fields', () => {
        it('should render current password field', () => {
            const wrapper = mount(Password);

            const currentPasswordInput = wrapper.find('input#current_password');
            expect(currentPasswordInput.exists()).toBe(true);
            expect(currentPasswordInput.attributes('type')).toBe('password');
            expect(currentPasswordInput.attributes('name')).toBe('current_password');
            expect(currentPasswordInput.attributes('autocomplete')).toBe('current-password');
        });

        it('should render new password field', () => {
            const wrapper = mount(Password);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.exists()).toBe(true);
            expect(passwordInput.attributes('type')).toBe('password');
            expect(passwordInput.attributes('name')).toBe('password');
            expect(passwordInput.attributes('autocomplete')).toBe('new-password');
        });

        it('should render password confirmation field', () => {
            const wrapper = mount(Password);

            const confirmInput = wrapper.find('input#password_confirmation');
            expect(confirmInput.exists()).toBe(true);
            expect(confirmInput.attributes('type')).toBe('password');
            expect(confirmInput.attributes('name')).toBe('password_confirmation');
        });

        it('should have labels for all password fields', () => {
            const wrapper = mount(Password);

            const labels = wrapper.findAllComponents({ name: 'Label' });
            expect(labels).toHaveLength(3);
            
            expect(labels[0].props('for')).toBe('current_password');
            expect(labels[1].props('for')).toBe('password');
            expect(labels[2].props('for')).toBe('password_confirmation');
        });

        it('should display password requirement hint', () => {
            const wrapper = mount(Password);

            expect(wrapper.text()).toContain('Must be at least 14 characters long');
        });

        it('should have placeholder text for new password', () => {
            const wrapper = mount(Password);

            const passwordInput = wrapper.find('input#password');
            expect(passwordInput.attributes('placeholder')).toContain('minimum 14 characters');
        });
    });

    describe('form configuration', () => {
        it('should configure Form with preserveScroll option', () => {
            const wrapper = mount(Password);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.props('options')).toEqual({
                preserveScroll: true,
            });
        });

        it('should reset form on success', () => {
            const wrapper = mount(Password);

            const form = wrapper.findComponent({ name: 'Form' });
            // reset-on-success attribute is defined
            expect(form.props('resetOnSuccess')).toBeDefined();
        });

        it('should reset specific fields on error', () => {
            const wrapper = mount(Password);

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.props('resetOnError')).toEqual([
                'password',
                'password_confirmation',
                'current_password',
            ]);
        });
    });

    describe('submit button', () => {
        it('should render save button', () => {
            const wrapper = mount(Password);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toBe('Save password');
        });

        it('should not disable button by default', () => {
            const wrapper = mount(Password);

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should have test attribute for e2e tests', () => {
            const wrapper = mount(Password);

            const button = wrapper.find('[data-test="update-password-button"]');
            expect(button.exists()).toBe(true);
        });
    });

    describe('success feedback', () => {
        it('should have success message element', () => {
            const wrapper = mount(Password);

            // Success message exists in template but hidden by v-show
            expect(wrapper.html()).toContain('Saved.');
        });
    });

    describe('error handling', () => {
        it('should render InputError for current password', () => {
            const wrapper = mount(Password);

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });

        it('should render InputError for new password', () => {
            const wrapper = mount(Password);

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });

        it('should render InputError for password confirmation', () => {
            const wrapper = mount(Password);

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('layout', () => {
        it('should have proper spacing', () => {
            const wrapper = mount(Password);

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });

        it('should pass breadcrumbs to AppLayout', () => {
            const wrapper = mount(Password);

            const appLayout = wrapper.findComponent({ name: 'AppLayout' });
            expect(appLayout.props('breadcrumbs')).toEqual([
                {
                    title: 'Password settings',
                    href: '/settings/password',
                },
            ]);
        });

        it('should have grid layout for form fields', () => {
            const wrapper = mount(Password);

            const grids = wrapper.findAll('.grid.gap-2');
            expect(grids.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('accessibility', () => {
        it('should associate labels with inputs', () => {
            const wrapper = mount(Password);

            const currentPasswordLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'current_password');
            expect(currentPasswordLabel).toBeDefined();

            const passwordLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'password');
            expect(passwordLabel).toBeDefined();

            const confirmLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'password_confirmation');
            expect(confirmLabel).toBeDefined();
        });

        it('should have autocomplete attributes for password fields', () => {
            const wrapper = mount(Password);

            const currentPassword = wrapper.find('input#current_password');
            expect(currentPassword.attributes('autocomplete')).toBe('current-password');

            const newPassword = wrapper.find('input#password');
            expect(newPassword.attributes('autocomplete')).toBe('new-password');

            const confirmPassword = wrapper.find('input#password_confirmation');
            expect(confirmPassword.attributes('autocomplete')).toBe('new-password');
        });

        it('should have descriptive placeholders', () => {
            const wrapper = mount(Password);

            const inputs = wrapper.findAll('input');
            inputs.forEach(input => {
                expect(input.attributes('placeholder')).toBeTruthy();
            });
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(Password);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.props('title')).toBe('Password settings');
        });
    });

    describe('security', () => {
        it('should use password type for all password fields', () => {
            const wrapper = mount(Password);

            const inputs = wrapper.findAll('input');
            inputs.forEach(input => {
                expect(input.attributes('type')).toBe('password');
            });
        });

        it('should require current password for password changes', () => {
            const wrapper = mount(Password);

            const currentPassword = wrapper.find('input#current_password');
            expect(currentPassword.exists()).toBe(true);
        });

        it('should require password confirmation', () => {
            const wrapper = mount(Password);

            const confirmPassword = wrapper.find('input#password_confirmation');
            expect(confirmPassword.exists()).toBe(true);
        });
    });
});
