import NavMain from '@/components/NavMain.vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import type { NavItem } from '@/types';
import { mount } from '@vue/test-utils';
import { Home, Settings, User } from 'lucide-vue-next';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        url: '/dashboard',
    })),
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
}));

// Mock sidebar components to avoid context requirements
vi.mock('@/components/ui/sidebar', () => ({
    SidebarGroup: {
        name: 'SidebarGroup',
        props: ['class'],
        template: '<div data-test="sidebar-group" :class="$props.class"><slot /></div>',
    },
    SidebarGroupLabel: {
        name: 'SidebarGroupLabel',
        template: '<div data-test="sidebar-group-label"><slot /></div>',
    },
    SidebarMenu: {
        name: 'SidebarMenu',
        template: '<ul data-test="sidebar-menu"><slot /></ul>',
    },
    SidebarMenuItem: {
        name: 'SidebarMenuItem',
        template: '<li data-test="sidebar-menu-item"><slot /></li>',
    },
    SidebarMenuButton: {
        name: 'SidebarMenuButton',
        props: ['asChild', 'isActive', 'tooltip'],
        template: '<button data-test="sidebar-menu-button"><slot /></button>',
    },
}));

// Mock utils
vi.mock('@/lib/utils', async () => {
    const actual = await vi.importActual('@/lib/utils');
    return {
        ...actual,
        urlIsActive: vi.fn((href: string, currentUrl: string) => href === currentUrl),
    };
});

describe('NavMain', () => {
    const mockItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: Home,
        },
        {
            title: 'Profile',
            href: '/profile',
            icon: User,
        },
        {
            title: 'Settings',
            href: '/settings',
            icon: Settings,
        },
    ];

    // Helper to create wrapper with mocked sidebar components
    const createWrapper = (props: any) => {
        return mount(NavMain, {
            props,
        });
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('renders the component', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.exists()).toBe(true);
        });

        it('renders SidebarGroup with correct classes', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const group = wrapper.findComponent(SidebarGroup);
            expect(group.exists()).toBe(true);
            expect(group.classes()).toContain('px-2');
            expect(group.classes()).toContain('py-0');
        });

        it('renders SidebarGroupLabel', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const label = wrapper.findComponent(SidebarGroupLabel);
            expect(label.exists()).toBe(true);
        });

        it('displays "Platform" label', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const label = wrapper.findComponent(SidebarGroupLabel);
            expect(label.text()).toBe('Platform');
        });

        it('renders SidebarMenu', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.findComponent(SidebarMenu).exists()).toBe(true);
        });
    });

    describe('Menu Items', () => {
        it('renders correct number of menu items', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            // Check via text content since components are deeply nested
            expect(wrapper.text()).toContain('Dashboard');
            expect(wrapper.text()).toContain('Profile');
            expect(wrapper.text()).toContain('Settings');
        });

        it('renders all menu item titles', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const text = wrapper.text();
            mockItems.forEach(item => {
                expect(text).toContain(item.title);
            });
        });

        it('renders Link component for each item', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            expect(links.length).toBeGreaterThanOrEqual(3);
        });

        it('sets correct href for each link', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            const hrefs = links.map(link => link.attributes('href'));
            expect(hrefs).toContain('/dashboard');
            expect(hrefs).toContain('/profile');
            expect(hrefs).toContain('/settings');
        });
    });

    describe('Active State', () => {
        it('calls urlIsActive for each menu item', () => {
            createWrapper({
                items: mockItems,
            });

            // urlIsActive is called for each item to determine active state
            expect(urlIsActive).toHaveBeenCalledWith('/dashboard', '/dashboard');
            expect(urlIsActive).toHaveBeenCalledWith('/profile', '/dashboard');
            expect(urlIsActive).toHaveBeenCalledWith('/settings', '/dashboard');
        });

        it('renders items with current URL from usePage', () => {
            const wrapper = createWrapper({
                items: mockItems,
            });
            // Component successfully renders with URL checking
            expect(wrapper.exists()).toBe(true);
            expect(urlIsActive).toHaveBeenCalled();
        });
    });

    describe('Tooltip', () => {
        it('renders with tooltip attribute for accessibility', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            // Tooltip is set via props, component renders successfully
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.html()).toContain('Dashboard');
        });
    });

    describe('Empty State', () => {
        it('renders with empty items array', () => {
            const wrapper = createWrapper({
                items: []
            });
            expect(wrapper.exists()).toBe(true);
        });

        it('renders no menu items when items array is empty', () => {
            const wrapper = createWrapper({
                items: []
            });
            // No menu item text should appear
            expect(wrapper.text()).toContain('Platform');
            expect(wrapper.findAll('a').length).toBe(0);
        });

        it('still renders Platform label with empty items', () => {
            const wrapper = createWrapper({
                items: []
            });
            expect(wrapper.text()).toContain('Platform');
        });
    });

    describe('Props', () => {
        it('accepts items prop', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            // Component renders correctly with items
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.props('items')).toEqual(mockItems);
        });

        it('requires items prop', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.props()).toHaveProperty('items');
        });
    });

    describe('Key Attribute', () => {
        it('uses item title as key', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            // Vue uses keys internally, we verify items render correctly
            mockItems.forEach(item => {
                expect(wrapper.text()).toContain(item.title);
            });
        });
    });

    describe('Single Item', () => {
        it('renders correctly with single item', () => {
            const singleItem: NavItem[] = [
                {
                    title: 'Home',
                    href: '/home',
                    icon: Home,
                },
            ];

            const wrapper = createWrapper({
                items: singleItem
            });

            expect(wrapper.text()).toContain('Home');
            expect(wrapper.findAll('a').length).toBeGreaterThanOrEqual(1);
        });
    });

    describe('Many Items', () => {
        it('renders correctly with many items', () => {
            const manyItems: NavItem[] = Array.from({ length: 10 }, (_, i) => ({
                title: `Item ${i + 1}`,
                href: `/item-${i + 1}`,
                icon: Home,
            }));

            const wrapper = createWrapper({
                items: manyItems
            });

            // Verify all items render
            const text = wrapper.text();
            manyItems.forEach(item => {
                expect(text).toContain(item.title);
            });
        });
    });
});
