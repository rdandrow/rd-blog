import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Appearance from '@/pages/settings/Appearance.vue';

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<head><title>{{ title }}</title></head>',
        props: ['title'],
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

vi.mock('@/routes/appearance', () => ({
    edit: vi.fn(() => ({ url: '/settings/appearance' })),
}));

vi.mock('@/components/AppearanceTabs.vue', () => ({
    default: {
        name: 'AppearanceTabs',
        template: '<div data-testid="appearance-tabs">Appearance Tabs</div>',
    },
}));

vi.mock('@/components/HeadingSmall.vue', () => ({
    default: {
        name: 'HeadingSmall',
        template: '<div><h2>{{ title }}</h2><p>{{ description }}</p></div>',
        props: ['title', 'description'],
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

describe('Appearance Settings Page', () => {
    describe('rendering', () => {
        it('should render page title', () => {
            const wrapper = mount(Appearance);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.exists()).toBe(true);
            expect(head.props('title')).toBe('Appearance settings');
        });

        it('should render within AppLayout', () => {
            const wrapper = mount(Appearance);

            const appLayout = wrapper.find('[data-testid="app-layout"]');
            expect(appLayout.exists()).toBe(true);
        });

        it('should render within SettingsLayout', () => {
            const wrapper = mount(Appearance);

            const settingsLayout = wrapper.find('[data-testid="settings-layout"]');
            expect(settingsLayout.exists()).toBe(true);
        });

        it('should render heading with title and description', () => {
            const wrapper = mount(Appearance);

            const heading = wrapper.findComponent({ name: 'HeadingSmall' });
            expect(heading.exists()).toBe(true);
            expect(heading.props('title')).toBe('Appearance settings');
            expect(heading.props('description')).toBe("Update your account's appearance settings");
        });

        it('should render AppearanceTabs component', () => {
            const wrapper = mount(Appearance);

            const tabs = wrapper.findComponent({ name: 'AppearanceTabs' });
            expect(tabs.exists()).toBe(true);
        });
    });

    describe('layout', () => {
        it('should have proper spacing', () => {
            const wrapper = mount(Appearance);

            const container = wrapper.find('.space-y-6');
            expect(container.exists()).toBe(true);
        });

        it('should pass breadcrumbs to AppLayout', () => {
            const wrapper = mount(Appearance);

            const appLayout = wrapper.findComponent({ name: 'AppLayout' });
            expect(appLayout.props('breadcrumbs')).toEqual([
                {
                    title: 'Appearance settings',
                    href: '/settings/appearance',
                },
            ]);
        });
    });

    describe('structure', () => {
        it('should render heading before tabs', () => {
            const wrapper = mount(Appearance);

            const html = wrapper.html();
            const headingIndex = html.indexOf('Appearance settings');
            const tabsIndex = html.indexOf('appearance-tabs');

            expect(headingIndex).toBeLessThan(tabsIndex);
        });

        it('should nest layouts correctly', () => {
            const wrapper = mount(Appearance);

            // SettingsLayout should be inside AppLayout
            const appLayout = wrapper.find('[data-testid="app-layout"]');
            const settingsLayout = appLayout.find('[data-testid="settings-layout"]');
            
            expect(settingsLayout.exists()).toBe(true);
        });
    });

    describe('accessibility', () => {
        it('should have semantic heading structure', () => {
            const wrapper = mount(Appearance);

            const heading = wrapper.findComponent({ name: 'HeadingSmall' });
            expect(heading.exists()).toBe(true);
        });

        it('should provide descriptive page title', () => {
            const wrapper = mount(Appearance);

            const head = wrapper.findComponent({ name: 'Head' });
            expect(head.props('title')).toBe('Appearance settings');
        });
    });
});
