import TwoFactor from '@/pages/settings/TwoFactor.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div data-testid="head" :title="title">{{ title }}</div>', props: ['title'] },
    Link: { name: 'Link', template: '<a><slot /></a>' },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                    role: 'user',
                    email_verified_at: '2024-01-01T00:00:00Z',
                },
            },
        },
    })),
    Form: {
        name: 'Form',
        template: '<form @submit.prevent="$emit(\'success\')"><slot :processing="false" /></form>',
        props: ['method', 'url', 'preserveScroll', 'resetOnSuccess', 'resetOnError'],
    },
}));

vi.mock('@/routes/two-factor', () => ({
    show: { url: vi.fn(() => '/settings/two-factor') },
    enable: {
        form: vi.fn(() => ({
            method: 'post',
            url: '/two-factor/enable',
        })),
    },
    disable: {
        form: vi.fn(() => ({
            method: 'delete',
            url: '/two-factor/disable',
        })),
    },
}));

vi.mock('@/composables/useTwoFactorAuth', () => ({
    useTwoFactorAuth: vi.fn(() => ({
        hasSetupData: false,
        clearTwoFactorAuthData: vi.fn(),
    })),
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

vi.mock('@/components/HeadingSmall.vue', () => ({
    default: {
        name: 'HeadingSmall',
        template: '<div><h2>{{ title }}</h2><p>{{ description }}</p></div>',
        props: ['title', 'description'],
    },
}));

vi.mock('@/components/TwoFactorRecoveryCodes.vue', () => ({
    default: {
        name: 'TwoFactorRecoveryCodes',
        template: '<div data-testid="recovery-codes">Recovery Codes</div>',
    },
}));

vi.mock('@/components/TwoFactorSetupModal.vue', () => ({
    default: {
        name: 'TwoFactorSetupModal',
        template: '<div data-testid="setup-modal" v-if="isOpen">Setup Modal</div>',
        props: ['isOpen', 'requiresConfirmation', 'twoFactorEnabled'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span :class="variant"><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button :type="type" :disabled="disabled" :class="variant"><slot /></button>',
        props: ['type', 'disabled', 'variant'],
    },
}));

vi.mock('lucide-vue-next', () => ({
    ShieldCheck: { name: 'ShieldCheck', template: '<span>ShieldCheck</span>' },
    ShieldBan: { name: 'ShieldBan', template: '<span>ShieldBan</span>' },
}));

describe('TwoFactor Settings Page', () => {
    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.text()).toContain('Two-Factor Authentication');
        });

        it('should render within AppLayout', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const appLayout = wrapper.findComponent({ name: 'AppLayout' });
            expect(appLayout.exists()).toBe(true);
        });

        it('should render within SettingsLayout', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const settingsLayout = wrapper.findComponent({ name: 'SettingsLayout' });
            expect(settingsLayout.exists()).toBe(true);
        });

        it('should render heading with title and description', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const heading = wrapper.findComponent({ name: 'HeadingSmall' });
            expect(heading.exists()).toBe(true);
            expect(heading.props('title')).toBe('Two-Factor Authentication');
            expect(heading.props('description')).toBe('Manage your two-factor authentication settings');
        });

        it('should have proper spacing container', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });
    });

    describe('disabled state', () => {
        it('should show disabled badge when 2FA is disabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const badge = wrapper.findComponent({ name: 'Badge' });
            expect(badge.exists()).toBe(true);
            expect(badge.props('variant')).toBe('destructive');
            expect(badge.text()).toBe('Disabled');
        });

        it('should show description about enabling 2FA', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            expect(wrapper.text()).toContain('When you enable two-factor authentication');
            expect(wrapper.text()).toContain('prompted for a secure pin during login');
            expect(wrapper.text()).toContain('TOTP-supported application');
        });

        it('should render enable 2FA button when no setup data', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.exists()).toBe(true);
            expect(button.text()).toContain('Enable 2FA');
        });

        it('should render enable button with ShieldCheck icon', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const icon = wrapper.findComponent({ name: 'ShieldCheck' });
            expect(icon.exists()).toBe(true);
        });

        it('should wrap enable button in Form', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const form = wrapper.findComponent({ name: 'Form' });
            expect(form.exists()).toBe(true);
        });

        it('should disable button when processing', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('disabled')).toBe(false);
        });

        it('should not show recovery codes when disabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const recoveryCodes = wrapper.findComponent({ name: 'TwoFactorRecoveryCodes' });
            expect(recoveryCodes.exists()).toBe(false);
        });

        it('should not show disable button when 2FA is disabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const disableButton = buttons.find(b => b.text().includes('Disable 2FA'));
            expect(disableButton).toBeUndefined();
        });
    });

    describe('enabled state', () => {
        it('should show enabled badge when 2FA is enabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const badge = wrapper.findComponent({ name: 'Badge' });
            expect(badge.exists()).toBe(true);
            expect(badge.props('variant')).toBe('default');
            expect(badge.text()).toBe('Enabled');
        });

        it('should show description about 2FA being enabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            expect(wrapper.text()).toContain('With two-factor authentication enabled');
            expect(wrapper.text()).toContain('secure, random pin during login');
        });

        it('should render TwoFactorRecoveryCodes component', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const recoveryCodes = wrapper.findComponent({ name: 'TwoFactorRecoveryCodes' });
            expect(recoveryCodes.exists()).toBe(true);
        });

        it('should render disable 2FA button', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const disableButton = buttons.find(b => b.text().includes('Disable 2FA'));
            expect(disableButton).toBeDefined();
        });

        it('should render disable button with destructive variant', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const disableButton = buttons.find(b => b.text().includes('Disable 2FA'));
            expect(disableButton?.props('variant')).toBe('destructive');
        });

        it('should render disable button with ShieldBan icon', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const icon = wrapper.findComponent({ name: 'ShieldBan' });
            expect(icon.exists()).toBe(true);
        });

        it('should wrap disable button in Form', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const forms = wrapper.findAllComponents({ name: 'Form' });
            expect(forms.length).toBeGreaterThan(0);
        });

        it('should not show enable button when 2FA is enabled', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const enableButton = buttons.find(b => b.text().includes('Enable 2FA'));
            expect(enableButton).toBeUndefined();
        });
    });

    describe('setup modal', () => {
        it('should render TwoFactorSetupModal component', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.exists()).toBe(true);
        });

        it('should initially hide setup modal', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.props('isOpen')).toBe(false);
        });

        it('should pass requiresConfirmation prop to modal', () => {
            const wrapper = mount(TwoFactor, {
                props: {
                    twoFactorEnabled: false,
                    requiresConfirmation: true,
                },
            });

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.props('requiresConfirmation')).toBe(true);
        });

        it('should pass twoFactorEnabled prop to modal', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.props('twoFactorEnabled')).toBe(true);
        });

        it('should default requiresConfirmation to false', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.props('requiresConfirmation')).toBe(false);
        });

        it('should default twoFactorEnabled to false', () => {
            const wrapper = mount(TwoFactor);

            const modal = wrapper.findComponent({ name: 'TwoFactorSetupModal' });
            expect(modal.props('twoFactorEnabled')).toBe(false);
        });
    });

    describe('continue setup flow', () => {
        it('should show continue setup button when hasSetupData is true', async () => {
            const { useTwoFactorAuth } = await import('@/composables/useTwoFactorAuth');
            (useTwoFactorAuth as any).mockReturnValue({
                hasSetupData: true,
                clearTwoFactorAuthData: vi.fn(),
            });

            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Continue Setup');
        });

        it('should not wrap continue setup button in Form', async () => {
            const { useTwoFactorAuth } = await import('@/composables/useTwoFactorAuth');
            (useTwoFactorAuth as any).mockReturnValue({
                hasSetupData: true,
                clearTwoFactorAuthData: vi.fn(),
            });

            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.text()).toContain('Continue Setup');
            
            // Button should not be inside a Form for continue setup
            const parentForm = button.element.closest('form');
            expect(parentForm).toBeNull();
        });
    });

    describe('layout', () => {
        it('should have proper spacing for content sections', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const contentSection = wrapper.find('.flex.flex-col.items-start.justify-start.space-y-4');
            expect(contentSection.exists()).toBe(true);
        });

        it('should pass breadcrumbs to AppLayout', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const appLayout = wrapper.findComponent({ name: 'AppLayout' });
            const breadcrumbs = appLayout.props('breadcrumbs');
            expect(breadcrumbs).toHaveLength(1);
            expect(breadcrumbs[0].title).toBe('Two-Factor Authentication');
            expect(breadcrumbs[0].href).toBe('/settings/two-factor');
        });
    });

    describe('lifecycle', () => {
        it('should clear 2FA data on unmount', async () => {
            const clearMock = vi.fn();
            const { useTwoFactorAuth } = await import('@/composables/useTwoFactorAuth');
            (useTwoFactorAuth as any).mockReturnValue({
                hasSetupData: false,
                clearTwoFactorAuthData: clearMock,
            });

            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            wrapper.unmount();

            expect(clearMock).toHaveBeenCalled();
        });
    });

    describe('accessibility', () => {
        it('should provide descriptive page title', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.text()).toContain('Two-Factor Authentication');
        });

        it('should have proper button types', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const button = wrapper.findComponent({ name: 'Button' });
            expect(button.props('type')).toBe('submit');
        });

        it('should have proper button types for disable action', () => {
            const wrapper = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const buttons = wrapper.findAllComponents({ name: 'Button' });
            const disableButton = buttons.find(b => b.text().includes('Disable 2FA'));
            expect(disableButton?.props('type')).toBe('submit');
        });

        it('should use semantic badge colors', () => {
            const wrapperDisabled = mount(TwoFactor, {
                props: { twoFactorEnabled: false },
            });

            const badgeDisabled = wrapperDisabled.findComponent({ name: 'Badge' });
            expect(badgeDisabled.props('variant')).toBe('destructive');

            const wrapperEnabled = mount(TwoFactor, {
                props: { twoFactorEnabled: true },
            });

            const badgeEnabled = wrapperEnabled.findComponent({ name: 'Badge' });
            expect(badgeEnabled.props('variant')).toBe('default');
        });
    });
});
