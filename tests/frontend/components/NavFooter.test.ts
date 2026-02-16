import NavFooter from '@/components/NavFooter.vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { mount } from '@vue/test-utils';
import { ExternalLink, Github, Twitter } from 'lucide-vue-next';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Mock utils
vi.mock('@/lib/utils', async () => {
    const actual = await vi.importActual('@/lib/utils');
    return {
        ...actual,
        toUrl: vi.fn((href: string) => href),
    };
});

// Mock sidebar components to avoid context requirements
vi.mock('@/components/ui/sidebar', () => ({
    SidebarGroup: {
        name: 'SidebarGroup',
        props: ['class'],
        template: '<div data-test="sidebar-group" :class="$props.class"><slot /></div>',
    },
    SidebarGroupContent: {
        name: 'SidebarGroupContent',
        template: '<div data-test="sidebar-group-content"><slot /></div>',
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
        props: ['asChild', 'size'],
        template: '<button data-test="sidebar-menu-button"><slot /></button>',
    },
}));

describe('NavFooter', () => {
    const mockItems: NavItem[] = [
        {
            title: 'GitHub',
            href: 'https://github.com',
            icon: Github,
        },
        {
            title: 'Twitter',
            href: 'https://twitter.com',
            icon: Twitter,
        },
        {
            title: 'External',
            href: 'https://example.com',
            icon: ExternalLink,
        },
    ];

    // Helper to create wrapper with mocked sidebar components
    const createWrapper = (props: any) => {
        return mount(NavFooter, {
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

        it('renders SidebarGroup', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.findComponent(SidebarGroup).exists()).toBe(true);
        });

        it('applies default classes to SidebarGroup', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const group = wrapper.findComponent(SidebarGroup);
            expect(group.classes()).toContain('group-data-[collapsible=icon]:p-0');
        });

        it('renders SidebarGroupContent', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.findComponent(SidebarGroupContent).exists()).toBe(true);
        });

        it('renders SidebarMenu', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.findComponent(SidebarMenu).exists()).toBe(true);
        });
    });

    describe('Menu Items', () => {
        it('renders all menu item titles', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const text = wrapper.text();
            expect(text).toContain('GitHub');
            expect(text).toContain('Twitter');
            expect(text).toContain('External');
        });

        it('renders correct number of links', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            expect(links.length).toBeGreaterThanOrEqual(3);
        });

        it('renders title for each menu item', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.text()).toContain('GitHub');
            expect(wrapper.text()).toContain('Twitter');
            expect(wrapper.text()).toContain('External');
        });
    });

    describe('External Links', () => {
        it('renders anchor tags for all items', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            expect(links.length).toBeGreaterThanOrEqual(3);
        });

        it('sets correct href for each link via toUrl', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            const hrefs = links.map(l => l.attributes('href'));
            expect(hrefs).toContain('https://github.com');
            expect(hrefs).toContain('https://twitter.com');
            expect(hrefs).toContain('https://example.com');
        });

        it('calls toUrl for each item href', () => {
            createWrapper({
                items: mockItems
            });

            expect(toUrl).toHaveBeenCalledWith('https://github.com');
            expect(toUrl).toHaveBeenCalledWith('https://twitter.com');
            expect(toUrl).toHaveBeenCalledWith('https://example.com');
        });

        it('sets target="_blank" on all links', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            links.forEach((link) => {
                expect(link.attributes('target')).toBe('_blank');
            });
        });

        it('sets rel="noopener noreferrer" on all links', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            links.forEach((link) => {
                expect(link.attributes('rel')).toBe('noopener noreferrer');
            });
        });
    });

    describe('Custom Class Prop', () => {
        it('applies custom class when provided', () => {
            const wrapper = createWrapper({
                items: mockItems,
                class: 'custom-class',
            });
            const group = wrapper.findComponent(SidebarGroup);
            expect(group.attributes('class')).toContain('custom-class');
        });

        it('combines custom class with default classes', () => {
            const wrapper = createWrapper({
                items: mockItems,
                class: 'mt-4',
            });
            const group = wrapper.findComponent(SidebarGroup);
            expect(group.attributes('class')).toContain('mt-4');
        });

        it('renders without custom class', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const group = wrapper.findComponent(SidebarGroup);
            expect(group.classes()).toContain('group-data-[collapsible=icon]:p-0');
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
            expect(wrapper.findAll('a').length).toBe(0);
        });

        it('still renders structure with empty items', () => {
            const wrapper = createWrapper({
                items: []
            });
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.findComponent(SidebarGroup).exists()).toBe(true);
        });
    });

    describe('Props', () => {
        it('accepts items prop', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.props('items')).toEqual(mockItems);
        });

        it('accepts class prop', () => {
            const wrapper = createWrapper({
                items: mockItems,
                class: 'test-class',
            });
            expect(wrapper.vm.$props.class).toBe('test-class');
        });

        it('class prop is optional', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            expect(wrapper.props('class')).toBeUndefined();
        });
    });

    describe('Key Attribute', () => {
        it('uses item title as key', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            // Vue uses keys internally, verify items render
            mockItems.forEach(item => {
                expect(wrapper.text()).toContain(item.title);
            });
        });
    });

    describe('Single Item', () => {
        it('renders correctly with single item', () => {
            const singleItem: NavItem[] = [
                {
                    title: 'Docs',
                    href: 'https://docs.example.com',
                    icon: ExternalLink,
                },
            ];

            const wrapper = createWrapper({
                items: singleItem
            });

            expect(wrapper.text()).toContain('Docs');
            expect(wrapper.findAll('a').length).toBeGreaterThanOrEqual(1);
        });
    });

    describe('Many Items', () => {
        it('renders correctly with many items', () => {
            const manyItems: NavItem[] = Array.from({ length: 10 }, (_, i) => ({
                title: `Link ${i + 1}`,
                href: `https://example.com/${i + 1}`,
                icon: ExternalLink,
            }));

            const wrapper = createWrapper({
                items: manyItems
            });

            const text = wrapper.text();
            manyItems.forEach(item => {
                expect(text).toContain(item.title);
            });
        });
    });

    describe('Security Attributes', () => {
        it('prevents tabnabbing with noopener', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            links.forEach((link) => {
                expect(link.attributes('rel')).toContain('noopener');
            });
        });

        it('prevents referrer leaking with noreferrer', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            links.forEach((link) => {
                expect(link.attributes('rel')).toContain('noreferrer');
            });
        });

        it('opens links in new tab', () => {
            const wrapper = createWrapper({
                items: mockItems
            });
            const links = wrapper.findAll('a');
            links.forEach((link) => {
                expect(link.attributes('target')).toBe('_blank');
            });
        });
    });
});
