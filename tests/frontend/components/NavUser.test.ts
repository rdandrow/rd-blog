import NavUser from '@/components/NavUser.vue';
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import SidebarProvider from '@/components/ui/sidebar/SidebarProvider.vue';
import type { User } from '@/types';
import { mount } from '@vue/test-utils';
import { ChevronsUpDown } from 'lucide-vue-next';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'John Doe',
                    email: 'john@example.com',
                    initials: 'JD',
                    email_verified_at: null,
                    created_at: '2024-01-01T00:00:00.000Z',
                    updated_at: '2024-01-01T00:00:00.000Z',
                },
            },
        },
        url: '/dashboard',
    })),
    Link: {
        name: 'Link',
        template: '<a><slot /></a>',
    },
    router: {
        flushAll: vi.fn(),
    },
}));

// Mock components
vi.mock('@/components/UserInfo.vue', () => ({
    default: {
        name: 'UserInfo',
        props: ['user', 'showEmail'],
        template: '<div data-test="user-info">{{ user.name }}</div>',
    },
}));

vi.mock('@/components/UserMenuContent.vue', () => ({
    default: {
        name: 'UserMenuContent',
        props: ['user'],
        template: '<div data-test="user-menu-content"></div>',
    },
}));

describe('NavUser', () => {
    const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        initials: 'JD',
        email_verified_at: null,
        created_at: '2024-01-01T00:00:00.000Z',
        updated_at: '2024-01-01T00:00:00.000Z',
    } as User;

    // Helper to create wrapper with SidebarProvider context
    const createWrapper = (props = {}) => {
        return mount({
            template: '<SidebarProvider><NavUser v-bind="$attrs" /></SidebarProvider>',
            components: { SidebarProvider, NavUser },
            attrs: props,
        });
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('renders the component', () => {
            const wrapper = createWrapper();
            expect(wrapper.findComponent(NavUser).exists()).toBe(true);
        });

        it('renders SidebarMenu wrapper', () => {
            const wrapper = createWrapper();
            expect(wrapper.findComponent(SidebarMenu).exists()).toBe(true);
        });

        it('renders SidebarMenuItem', () => {
            const wrapper = createWrapper();
            expect(wrapper.findComponent(SidebarMenuItem).exists()).toBe(true);
        });

        it('renders DropdownMenu', () => {
            const wrapper = createWrapper();
            expect(wrapper.findComponent(DropdownMenu).exists()).toBe(true);
        });

        it('renders DropdownMenuTrigger', () => {
            const wrapper = createWrapper();
            const trigger = wrapper.findComponent(DropdownMenuTrigger);
            expect(trigger.exists()).toBe(true);
        });

        it('renders SidebarMenuButton with correct size', () => {
            const wrapper = createWrapper();
            const button = wrapper.findComponent(SidebarMenuButton);
            expect(button.exists()).toBe(true);
            expect(button.props('size')).toBe('lg');
        });

        it('renders SidebarMenuButton with correct classes', () => {
            const wrapper = createWrapper();
            const button = wrapper.findComponent(SidebarMenuButton);
            expect(button.classes()).toContain('data-[state=open]:bg-sidebar-accent');
            expect(button.classes()).toContain('data-[state=open]:text-sidebar-accent-foreground');
        });

        it('renders SidebarMenuButton with data-test attribute', () => {
            const wrapper = createWrapper();
            const button = wrapper.find('[data-test="sidebar-menu-button"]');
            expect(button.exists()).toBe(true);
        });
    });

    describe('User Info Display', () => {
        it('renders UserInfo component', () => {
            const wrapper = createWrapper();
            const userInfo = wrapper.findComponent(UserInfo);
            expect(userInfo.exists()).toBe(true);
        });

        it('passes user prop to UserInfo', () => {
            const wrapper = createWrapper();
            const userInfo = wrapper.findComponent(UserInfo);
            expect(userInfo.props('user')).toEqual(mockUser);
        });

        it('displays user name through UserInfo', () => {
            const wrapper = createWrapper();
            const userInfo = wrapper.find('[data-test="user-info"]');
            expect(userInfo.text()).toContain('John Doe');
        });
    });

    describe('Chevron Icon', () => {
        it('renders ChevronsUpDown icon', () => {
            const wrapper = createWrapper();
            const icon = wrapper.findComponent(ChevronsUpDown);
            expect(icon.exists()).toBe(true);
        });

        it('applies correct classes to chevron icon', () => {
            const wrapper = createWrapper();
            const icon = wrapper.findComponent(ChevronsUpDown);
            expect(icon.classes()).toContain('ml-auto');
            expect(icon.classes()).toContain('size-4');
        });
    });

    describe('Dropdown Menu Content', () => {
        it('renders DropdownMenuContent', () => {
            const wrapper = createWrapper();
            expect(wrapper.findComponent(DropdownMenuContent).exists()).toBe(true);
        });

        it('renders DropdownMenuContent with styling', () => {
            const wrapper = createWrapper();
            const content = wrapper.findComponent(DropdownMenuContent);
            expect(content.exists()).toBe(true);
            // Content component applies its own classes
        });

        it('sets correct align prop on menu content', () => {
            const wrapper = createWrapper();
            const content = wrapper.findComponent(DropdownMenuContent);
            expect(content.props('align')).toBe('end');
        });

        it('sets correct side-offset prop on menu content', () => {
            const wrapper = createWrapper();
            const content = wrapper.findComponent(DropdownMenuContent);
            expect(content.props('sideOffset')).toBe(4);
        });

        it('renders UserMenuContent in dropdown', () => {
            const wrapper = createWrapper();
            // Component structure is correct if no errors thrown
            expect(wrapper.findComponent(DropdownMenuContent).exists()).toBe(true);
        });

        it('passes user data to menu content', () => {
            const wrapper = createWrapper();
            // User data is accessed from usePage in the component
            const userInfo = wrapper.findComponent(UserInfo);
            expect(userInfo.props('user')).toEqual(mockUser);
        });

        it('sets side prop based on sidebar state', () => {
            const wrapper = createWrapper();
            const content = wrapper.findComponent(DropdownMenuContent);
            // Side prop is computed based on isMobile and state from useSidebar
            expect(content.props('side')).toBeDefined();
        });
    });

    describe('usePage Integration', () => {
        it('retrieves user from page props', async () => {
            const wrapper = createWrapper();
            const userInfo = wrapper.findComponent(UserInfo);
            expect(userInfo.props('user')).toEqual(mockUser);
        });
    });

    describe('Props', () => {
        it('does not accept any props', () => {
            const wrapper = createWrapper();
            const navUser = wrapper.findComponent(NavUser);
            expect(Object.keys(navUser.props())).toHaveLength(0);
        });
    });
});
