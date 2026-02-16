import UserMenuContent from '@/components/UserMenuContent.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock routes
vi.mock('@/routes', () => ({
    logout: vi.fn(() => '/logout'),
}));

vi.mock('@/routes/profile', () => ({
    edit: vi.fn(() => '/profile/edit'),
}));

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        template: '<a :href="href" @click="$emit(\'click\', $event)"><slot /></a>',
        props: ['href', 'as', 'prefetch'],
        emits: ['click'],
    },
    router: {
        flushAll: vi.fn(),
    },
}));

// Mock UserInfo component
vi.mock('@/components/UserInfo.vue', () => ({
    default: {
        name: 'UserInfo',
        props: ['user', 'showEmail'],
        template: '<div data-test="user-info">{{ user.name }} <span v-if="showEmail">{{ user.email }}</span></div>',
    },
}));

// Mock DropdownMenu components to avoid MenuRoot context requirements
vi.mock('@/components/ui/dropdown-menu', () => ({
    DropdownMenuLabel: {
        name: 'DropdownMenuLabel',
        template: '<div data-test="dropdown-menu-label"><slot /></div>',
    },
    DropdownMenuSeparator: {
        name: 'DropdownMenuSeparator',
        template: '<div data-test="dropdown-menu-separator"></div>',
    },
    DropdownMenuGroup: {
        name: 'DropdownMenuGroup',
        template: '<div data-test="dropdown-menu-group"><slot /></div>',
    },
    DropdownMenuItem: {
        name: 'DropdownMenuItem',
        props: ['asChild'],
        template: '<div data-test="dropdown-menu-item"><slot /></div>',
    },
}));

describe('UserMenuContent', () => {
    const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        initials: 'JD',
        email_verified_at: null,
        created_at: '2024-01-01T00:00:00.000Z',
        updated_at: '2024-01-01T00:00:00.000Z',
    } as User;

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('renders the component', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.exists()).toBe(true);
        });

        it('renders user information', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('John Doe');
        });

        it('renders Settings menu item', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('Settings');
        });

        it('renders Logout menu item', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('Log out');
        });
    });

    describe('User Info Section', () => {
        it('displays user name', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('John Doe');
        });

        it('displays user email', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('john@example.com');
        });

        it('renders user info with showEmail enabled', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const userInfo = wrapper.find('[data-test="user-info"]');
            expect(userInfo.exists()).toBe(true);
        });
    });

    describe('Settings Menu Item', () => {
        it('renders Settings menu item', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('Settings');
        });

        it('renders Settings link with correct href', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const links = wrapper.findAll('a');
            const settingsLink = links.find(l => l.text().includes('Settings'));
            expect(settingsLink?.attributes('href')).toBe('/profile/edit');
        });

        it('calls edit route helper', () => {
            mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(edit).toHaveBeenCalled();
        });
    });

    describe('Logout Menu Item', () => {
        it('renders Logout menu item', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.text()).toContain('Log out');
        });

        it('renders Logout link with correct href', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const links = wrapper.findAll('a');
            const logoutLink = links.find(l => l.text().includes('Log out'));
            expect(logoutLink?.attributes('href')).toBe('/logout');
        });

        it('calls logout route helper', () => {
            mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(logout).toHaveBeenCalled();
        });

        it('has data-test attribute on logout button', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const logoutButton = wrapper.find('[data-test="logout-button"]');
            expect(logoutButton.exists()).toBe(true);
        });
    });

    describe('Logout Handler', () => {
        it('calls router.flushAll when logout is clicked', async () => {
            const { router } = await import('@inertiajs/vue3');
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const logoutButton = wrapper.find('[data-test="logout-button"]');
            await logoutButton.trigger('click');
            expect(router.flushAll).toHaveBeenCalled();
        });
    });

    describe('Menu Structure', () => {
        it('renders Settings before Logout', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            const text = wrapper.text();
            const settingsIndex = text.indexOf('Settings');
            const logoutIndex = text.indexOf('Log out');
            expect(settingsIndex).toBeLessThan(logoutIndex);
        });
    });

    describe('Props', () => {
        it('accepts user prop', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.props('user')).toEqual(mockUser);
        });

        it('requires user prop', () => {
            const wrapper = mount(UserMenuContent, {
                props: { user: mockUser },
            });
            expect(wrapper.props()).toHaveProperty('user');
        });
    });

    describe('Different User Data', () => {
        it('renders with different user name', () => {
            const differentUser: User = {
                ...mockUser,
                name: 'Jane Smith',
            };

            const wrapper = mount(UserMenuContent, {
                props: { user: differentUser },
            });

            expect(wrapper.text()).toContain('Jane Smith');
        });

        it('renders with different user email', () => {
            const differentUser: User = {
                ...mockUser,
                email: 'jane@example.com',
            };

            const wrapper = mount(UserMenuContent, {
                props: { user: differentUser },
            });

            expect(wrapper.text()).toContain('jane@example.com');
        });
    });
});
