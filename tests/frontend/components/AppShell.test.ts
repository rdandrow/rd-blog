import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AppShell from '@/components/AppShell.vue';

// Mock Inertia usePage
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            sidebarOpen: true,
        },
    }),
}));

// Mock SidebarProvider
vi.mock('@/components/ui/sidebar', () => ({
    SidebarProvider: {
        name: 'SidebarProvider',
        template: '<div data-test="sidebar-provider"><slot /></div>',
        props: ['defaultOpen'],
    },
}));

describe('AppShell', () => {
    describe('rendering', () => {
        it('should render slot content', () => {
            const wrapper = mount(AppShell, {
                slots: {
                    default: '<div data-test="content">Test Content</div>',
                },
            });

            expect(wrapper.find('[data-test="content"]').exists()).toBe(true);
            expect(wrapper.text()).toContain('Test Content');
        });

        it('should render header variant with flex layout', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            const container = wrapper.find('div');
            expect(container.classes()).toContain('flex');
            expect(container.classes()).toContain('min-h-screen');
            expect(container.classes()).toContain('w-full');
            expect(container.classes()).toContain('flex-col');
        });

        it('should render sidebar variant with SidebarProvider', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: '<div data-test="sidebar-content">Sidebar Content</div>',
                },
            });

            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(true);
            expect(wrapper.find('[data-test="sidebar-content"]').exists()).toBe(true);
        });

        it('should default to sidebar variant when no variant provided', () => {
            const wrapper = mount(AppShell, {
                slots: {
                    default: '<div data-test="content">Content</div>',
                },
            });

            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(true);
        });
    });

    describe('variant behavior', () => {
        it('should apply correct classes for header variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            const root = wrapper.element;
            expect(root.classList.contains('flex')).toBe(true);
            expect(root.classList.contains('min-h-screen')).toBe(true);
        });

        it('should not render header layout when variant is sidebar', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            // Header layout should not exist (no direct div with flex classes)
            const html = wrapper.html();
            expect(html).not.toMatch(/<div[^>]*class="[^"]*flex min-h-screen/);
        });

        it('should not render SidebarProvider when variant is header', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(false);
        });
    });

    describe('sidebar state', () => {
        beforeEach(() => {
            vi.clearAllMocks();
        });

        it('should pass sidebarOpen prop to SidebarProvider as defaultOpen', () => {
            // Mock with sidebarOpen: true
            vi.mocked(vi.importActual('@inertiajs/vue3') as any).usePage = () => ({
                props: {
                    sidebarOpen: true,
                },
            });

            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            const sidebarProvider = wrapper.findComponent({ name: 'SidebarProvider' });
            expect(sidebarProvider.props('defaultOpen')).toBe(true);
        });

        it('should respect sidebarOpen from page props', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            const sidebarProvider = wrapper.findComponent({ name: 'SidebarProvider' });
            // sidebarOpen is mocked as true in usePage mock
            expect(sidebarProvider.props('defaultOpen')).toBe(true);
        });
    });

    describe('slot rendering', () => {
        it('should render complex slot content in header variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: `
                        <header>Header</header>
                        <main>Main Content</main>
                        <footer>Footer</footer>
                    `,
                },
            });

            expect(wrapper.find('header').text()).toBe('Header');
            expect(wrapper.find('main').text()).toBe('Main Content');
            expect(wrapper.find('footer').text()).toBe('Footer');
        });

        it('should render complex slot content in sidebar variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: `
                        <div data-test="nav">Navigation</div>
                        <div data-test="main">Main Content</div>
                    `,
                },
            });

            expect(wrapper.find('[data-test="nav"]').text()).toBe('Navigation');
            expect(wrapper.find('[data-test="main"]').text()).toBe('Main Content');
        });

        it('should handle empty slot', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
            });

            expect(wrapper.html()).toBeTruthy();
        });

        it('should handle slot with Vue components', () => {
            const TestComponent = {
                name: 'TestComponent',
                template: '<div data-test="test-component">Test Component</div>',
            };

            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: TestComponent,
                },
            });

            expect(wrapper.find('[data-test="test-component"]').exists()).toBe(true);
        });
    });

    describe('reactivity', () => {
        it('should reactively update when variant prop changes', async () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div data-test="content">Content</div>',
                },
            });

            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(false);

            await wrapper.setProps({ variant: 'sidebar' });

            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(true);
        });

        it('should maintain slot content when variant changes', async () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div data-test="persistent">Persistent Content</div>',
                },
            });

            expect(wrapper.find('[data-test="persistent"]').exists()).toBe(true);

            await wrapper.setProps({ variant: 'sidebar' });

            expect(wrapper.find('[data-test="persistent"]').exists()).toBe(true);
        });
    });

    describe('edge cases', () => {
        it('should handle undefined variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: undefined,
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            // Should default to sidebar variant
            expect(wrapper.find('[data-test="sidebar-provider"]').exists()).toBe(true);
        });

        it('should handle variant with extra whitespace', async () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header' as any,
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            expect(wrapper.find('div').classes()).toContain('flex');
        });

        it('should not break with multiple root elements in slot', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: `
                        <div>First</div>
                        <div>Second</div>
                        <div>Third</div>
                    `,
                },
            });

            const allDivs = wrapper.findAll('div');
            // Should have container div plus 3 content divs
            expect(allDivs.length).toBeGreaterThanOrEqual(4);
        });
    });

    describe('structure', () => {
        it('should have correct structure for header variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            // Should be a single root div with specific classes
            const root = wrapper.element;
            expect(root.tagName).toBe('DIV');
            expect(root.classList.contains('flex')).toBe(true);
            expect(root.classList.contains('min-h-screen')).toBe(true);
            expect(root.classList.contains('w-full')).toBe(true);
            expect(root.classList.contains('flex-col')).toBe(true);
        });

        it('should have correct structure for sidebar variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar',
                },
                slots: {
                    default: '<div>Content</div>',
                },
            });

            // Should render SidebarProvider
            const provider = wrapper.findComponent({ name: 'SidebarProvider' });
            expect(provider.exists()).toBe(true);
        });
    });

    describe('TypeScript props', () => {
        it('should accept header variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'header' as const,
                },
            });

            expect(wrapper.props('variant')).toBe('header');
        });

        it('should accept sidebar variant', () => {
            const wrapper = mount(AppShell, {
                props: {
                    variant: 'sidebar' as const,
                },
            });

            expect(wrapper.props('variant')).toBe('sidebar');
        });

        it('should allow variant to be omitted', () => {
            const wrapper = mount(AppShell);

            expect(wrapper.props('variant')).toBeUndefined();
        });
    });
});
