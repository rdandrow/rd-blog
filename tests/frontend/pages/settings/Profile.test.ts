import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Profile from '@/pages/settings/Profile.vue';

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
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href', 'as'],
    },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                    website: 'https://example.com',
                    bio: 'Test bio',
                    role: 'user',
                    email_verified_at: '2024-01-01T00:00:00Z',
                },
            },
        },
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Settings/ProfileController', () => ({
    default: {
        update: {
            form: vi.fn(() => ({
                action: '/settings/profile',
                method: 'PUT',
            })),
        },
    },
}));

vi.mock('@/routes/profile', () => ({
    edit: vi.fn(() => ({ url: '/settings/profile' })),
}));

vi.mock('@/routes/verification', () => ({
    send: vi.fn(() => '/email/verification-notification'),
}));

vi.mock('@/components/DeleteUser.vue', () => ({
    default: {
        name: 'DeleteUser',
        template: '<div data-testid="delete-user">Delete User Component</div>',
    },
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
        template: '<input :id="id" :name="name" :type="type" :placeholder="placeholder" :autocomplete="autocomplete" :default-value="defaultValue" :required="required" />',
        props: ['id', 'name', 'type', 'placeholder', 'autocomplete', 'defaultValue', 'required'],
    },
}));

vi.mock('@/components/ui/textarea', () => ({
    Textarea: {
        name: 'Textarea',
        template: '<textarea :id="id" :name="name" :rows="rows" :placeholder="placeholder" :default-value="defaultValue"></textarea>',
        props: ['id', 'name', 'rows', 'placeholder', 'defaultValue'],
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

describe('Profile Settings Page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.props('title')).toBe('Profile settings');
        });

        it('should render within AppLayout', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const appLayout = wrapper.find('[data-testid="app-layout"]');
            expect(appLayout.exists()).toBe(true);
        });

        it('should render within SettingsLayout', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const settingsLayout = wrapper.find('[data-testid="settings-layout"]');
            expect(settingsLayout.exists()).toBe(true);
        });

        it('should render heading with title and description', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const heading = wrapper.findComponent({ name: 'HeadingSmall' });
            expect(heading.exists()).toBe(true);
            expect(heading.props('title')).toBe('Profile information');
            expect(heading.props('description')).toBe('Update your name and email address');
        });

        it('should render Form component', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });

        it('should render DeleteUser component', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const deleteUser = wrapper.find('[data-testid="delete-user"]');
            expect(deleteUser.exists()).toBe(true);
        });
    });

    describe('form fields - basic user', () => {
        it('should render name field', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const nameInput = wrapper.find('input#name');
            expect(nameInput.exists()).toBe(true);
            expect(nameInput.attributes('name')).toBe('name');
            expect(nameInput.attributes('autocomplete')).toBe('name');
            expect(nameInput.attributes('required')).toBeDefined();
        });

        it('should pre-fill name with user data', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const nameInput = wrapper.find('input#name');
            expect(nameInput.attributes('default-value')).toBe('Test User');
        });

        it('should render email field', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.exists()).toBe(true);
            expect(emailInput.attributes('type')).toBe('email');
            expect(emailInput.attributes('name')).toBe('email');
            expect(emailInput.attributes('autocomplete')).toBe('username');
            expect(emailInput.attributes('required')).toBeDefined();
        });

        it('should pre-fill email with user data', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('default-value')).toBe('test@example.com');
        });

        it('should render website field', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const websiteInput = wrapper.find('input#website');
            expect(websiteInput.exists()).toBe(true);
            expect(websiteInput.attributes('type')).toBe('url');
            expect(websiteInput.attributes('name')).toBe('website');
        });

        it('should pre-fill website with user data', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const websiteInput = wrapper.find('input#website');
            expect(websiteInput.attributes('default-value')).toBe('https://example.com');
        });

        it('should show website helper text', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            expect(wrapper.text()).toContain('protocol is optional');
            expect(wrapper.text()).toContain('https:// will be added automatically');
        });

        it('should have labels for all basic fields', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const labels = wrapper.findAllComponents({ name: 'Label' });
            expect(labels.length).toBeGreaterThanOrEqual(3);
            
            const labelTexts = labels.map(l => l.text());
            expect(labelTexts).toContain('Name');
            expect(labelTexts).toContain('Email address');
            expect(labelTexts).toContain('Website');
        });
    });

    describe('form fields - admin bio', () => {
        it('should not render bio field for regular users', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const bioTextarea = wrapper.find('textarea#bio');
            expect(bioTextarea.exists()).toBe(false);
        });

        it('should render bio field for admin users', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Admin User',
                            email: 'admin@example.com',
                            role: 'admin',
                            email_verified_at: '2024-01-01T00:00:00Z',
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const bioTextarea = wrapper.find('textarea#bio');
            expect(bioTextarea.exists()).toBe(true);
        });

        it('should render bio field for master_admin users', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Master Admin',
                            email: 'master@example.com',
                            role: 'master_admin',
                            email_verified_at: '2024-01-01T00:00:00Z',
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const bioTextarea = wrapper.find('textarea#bio');
            expect(bioTextarea.exists()).toBe(true);
        });

        it('should show bio helper text for admins', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Admin User',
                            email: 'admin@example.com',
                            role: 'admin',
                            email_verified_at: '2024-01-01T00:00:00Z',
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            expect(wrapper.text()).toContain('This will be displayed on your author profile page');
        });
    });

    describe('email verification', () => {
        it('should not show verification message when email is verified', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: true },
            });

            expect(wrapper.text()).not.toContain('Your email address is unverified');
        });

        it('should show verification message for unverified email', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Test User',
                            email: 'test@example.com',
                            role: 'user',
                            email_verified_at: null,
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: true },
            });

            expect(wrapper.text()).toContain('Your email address is unverified');
        });

        it('should show resend verification link', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Test User',
                            email: 'test@example.com',
                            role: 'user',
                            email_verified_at: null,
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: true },
            });

            const link = wrapper.findComponent({ name: 'Link' });
            expect(link.exists()).toBe(true);
            expect(link.text()).toContain('Click here to resend the verification email');
        });

        it('should show verification sent status message', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Test User',
                            email: 'test@example.com',
                            role: 'user',
                            email_verified_at: null,
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: true, status: 'verification-link-sent' },
            });

            expect(wrapper.text()).toContain('A new verification link has been sent');
        });

        it('should not show verification sent message without status', async () => {
            const { usePage } = await import('@inertiajs/vue3');
            (usePage as any).mockReturnValue({
                props: {
                    auth: {
                        user: {
                            id: 1,
                            name: 'Test User',
                            email: 'test@example.com',
                            role: 'user',
                            email_verified_at: null,
                        },
                    },
                },
            });

            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: true },
            });

            expect(wrapper.text()).not.toContain('A new verification link has been sent');
        });
    });

    describe('submit button', () => {
        it('should render save button', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toBe('Save');
        });

        it('should not disable button by default', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should have test attribute for e2e tests', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const button = wrapper.find('[data-test="update-profile-button"]');
            expect(button.exists()).toBe(true);
        });
    });

    describe('success feedback', () => {
        it('should have success message element', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            expect(wrapper.html()).toContain('Saved.');
        });
    });

    describe('error handling', () => {
        it('should render InputError for name', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });

        it('should render InputError for email', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });

        it('should render InputError for website', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const errors = wrapper.findAllComponents({ name: 'InputError' });
            expect(errors.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('layout', () => {
        it('should have proper spacing', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });

        it('should pass breadcrumbs to AppLayout', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const appLayout = wrapper.findComponent({ name: 'AppLayout' });
            expect(appLayout.props('breadcrumbs')).toEqual([
                {
                    title: 'Profile settings',
                    href: '/settings/profile',
                },
            ]);
        });

        it('should have grid layout for form fields', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const grids = wrapper.findAll('.grid.gap-2');
            expect(grids.length).toBeGreaterThanOrEqual(3);
        });
    });

    describe('accessibility', () => {
        it('should associate labels with inputs', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const nameLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'name');
            expect(nameLabel).toBeDefined();

            const emailLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'email');
            expect(emailLabel).toBeDefined();

            const websiteLabel = wrapper.findAll('label').find(l => l.attributes('for') === 'website');
            expect(websiteLabel).toBeDefined();
        });

        it('should have autocomplete attributes', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const nameInput = wrapper.find('input#name');
            expect(nameInput.attributes('autocomplete')).toBe('name');

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('autocomplete')).toBe('username');

            const websiteInput = wrapper.find('input#website');
            expect(websiteInput.attributes('autocomplete')).toBe('url');
        });

        it('should have descriptive placeholders', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const inputs = wrapper.findAll('input');
            inputs.forEach(input => {
                expect(input.attributes('placeholder')).toBeTruthy();
            });
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.props('title')).toBe('Profile settings');
        });
    });

    describe('required fields', () => {
        it('should mark name as required', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const nameInput = wrapper.find('input#name');
            expect(nameInput.attributes('required')).toBeDefined();
        });

        it('should mark email as required', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('required')).toBeDefined();
        });

        it('should not mark website as required', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const websiteInput = wrapper.find('input#website');
            expect(websiteInput.attributes('required')).toBeUndefined();
        });
    });

    describe('field types', () => {
        it('should use email type for email field', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const emailInput = wrapper.find('input#email');
            expect(emailInput.attributes('type')).toBe('email');
        });

        it('should use url type for website field', () => {
            const wrapper = mount(Profile, {
                props: { mustVerifyEmail: false },
            });

            const websiteInput = wrapper.find('input#website');
            expect(websiteInput.attributes('type')).toBe('url');
        });
    });
});
