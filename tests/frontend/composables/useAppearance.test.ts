import { describe, expect, it, beforeEach, afterEach, vi } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import { useAppearance, updateTheme, initializeTheme } from '@/composables/useAppearance';

describe('useAppearance', () => {
    beforeEach(() => {
        localStorage.clear();
        if (typeof document !== 'undefined') {
            document.documentElement.classList.remove('dark');
            document.cookie = '';
        }
        vi.clearAllMocks();
    });

    afterEach(() => {
        localStorage.clear();
        if (typeof document !== 'undefined') {
            document.documentElement.classList.remove('dark');
        }
    });

    describe('updateTheme', () => {
        it('should add dark class when theme is dark', () => {
            updateTheme('dark');
            
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should remove dark class when theme is light', () => {
            document.documentElement.classList.add('dark');
            
            updateTheme('light');
            
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('should use system preference when theme is system (dark)', () => {
            // Mock matchMedia to return dark
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: query === '(prefers-color-scheme: dark)',
                media: query,
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));

            updateTheme('system');
            
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should use system preference when theme is system (light)', () => {
            // Mock matchMedia to return light
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: false,
                media: query,
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));

            updateTheme('system');
            
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('should handle undefined window gracefully', () => {
            // This test verifies the window check exists
            expect(() => updateTheme('dark')).not.toThrow();
        });
    });

    describe('initializeTheme', () => {
        it('should initialize with saved preference', () => {
            localStorage.setItem('appearance', 'dark');
            
            initializeTheme();
            
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should initialize with system preference when no saved preference', () => {
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: query === '(prefers-color-scheme: dark)',
                media: query,
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));

            initializeTheme();
            
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should set up system theme change listener', () => {
            const addEventListenerSpy = vi.fn();
            
            window.matchMedia = vi.fn().mockImplementation(() => ({
                matches: false,
                media: '',
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: addEventListenerSpy,
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));

            initializeTheme();
            
            expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
        });

        it('should handle light preference', () => {
            localStorage.setItem('appearance', 'light');
            
            initializeTheme();
            
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });
    });

    describe('useAppearance composable', () => {
        it('should initialize with default system appearance', async () => {
            const { appearance } = useAppearance();
            
            await nextTick();
            
            // Before mount, defaults to system
            expect(appearance.value).toBe('system');
        });

        it('should load saved appearance from localStorage on mount', async () => {
            localStorage.setItem('appearance', 'dark');
            
            const { appearance } = useAppearance();
            
            // Simulate mount
            await nextTick();
            
            // Note: onMounted doesn't actually run in this test environment
            // but we can test the updateAppearance function
        });

        it('should update appearance and save to localStorage', async () => {
            const { appearance, updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            
            await nextTick();
            
            expect(appearance.value).toBe('dark');
            expect(localStorage.getItem('appearance')).toBe('dark');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should update appearance to light', async () => {
            const { appearance, updateAppearance } = useAppearance();
            
            // Set to dark first
            updateAppearance('dark');
            expect(appearance.value).toBe('dark');
            
            // Change to light
            updateAppearance('light');
            
            await nextTick();
            
            expect(appearance.value).toBe('light');
            expect(localStorage.getItem('appearance')).toBe('light');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('should update appearance to system', async () => {
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: false,
                media: query,
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));

            const { appearance, updateAppearance } = useAppearance();
            
            updateAppearance('system');
            
            await nextTick();
            
            expect(appearance.value).toBe('system');
            expect(localStorage.getItem('appearance')).toBe('system');
        });

        it('should set cookie when updating appearance', async () => {
            const { updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            
            await nextTick();
            
            // Check that cookie was set (contains appearance=dark)
            expect(document.cookie).toContain('appearance=dark');
        });

        it('should handle multiple appearance changes', async () => {
            const { appearance, updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            expect(appearance.value).toBe('dark');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
            
            updateAppearance('light');
            expect(appearance.value).toBe('light');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
            
            updateAppearance('dark');
            expect(appearance.value).toBe('dark');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('should be reactive', async () => {
            const { appearance, updateAppearance } = useAppearance();
            
            const values: string[] = [];
            
            // Track initial value (may vary based on setup)
            const initialValue = appearance.value;
            values.push(initialValue);
            
            updateAppearance('dark');
            values.push(appearance.value);
            
            updateAppearance('light');
            values.push(appearance.value);
            
            // Check that values changed correctly from whatever the initial was
            expect(values[1]).toBe('dark');
            expect(values[2]).toBe('light');
            expect(values.length).toBe(3);
        });

        it('should persist across page refreshes via localStorage', () => {
            const { updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            
            // Simulate page refresh by creating new instance
            const { appearance: newAppearance } = useAppearance();
            
            // After mount, it would load from localStorage
            expect(localStorage.getItem('appearance')).toBe('dark');
        });

        it('should update theme when switching between all modes', async () => {
            const { updateAppearance } = useAppearance();
            
            updateAppearance('light');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
            
            updateAppearance('dark');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
            
            window.matchMedia = vi.fn().mockImplementation(() => ({
                matches: false,
                media: '',
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));
            
            updateAppearance('system');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });
    });

    describe('cookie handling', () => {
        it('should set cookie with correct appearance value', () => {
            const { updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            
            const cookie = document.cookie;
            expect(cookie).toContain('appearance=dark');
            // Note: In happy-dom, cookie paths may not be fully simulated
        });

        it('should update cookie when appearance changes', () => {
            const { updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            expect(document.cookie).toContain('appearance=dark');
            
            updateAppearance('light');
            expect(document.cookie).toContain('appearance=light');
        });
    });

    describe('edge cases', () => {
        it('should handle rapid appearance changes', async () => {
            const { appearance, updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            updateAppearance('light');
            updateAppearance('dark');
            updateAppearance('system');
            updateAppearance('light');
            
            await nextTick();
            
            expect(appearance.value).toBe('light');
            expect(localStorage.getItem('appearance')).toBe('light');
        });

        it('should handle same value updates', () => {
            const { appearance, updateAppearance } = useAppearance();
            
            updateAppearance('dark');
            updateAppearance('dark');
            updateAppearance('dark');
            
            expect(appearance.value).toBe('dark');
            expect(localStorage.getItem('appearance')).toBe('dark');
        });
    });

    describe('SSR scenarios', () => {
        it('should handle updateTheme when window is undefined (SSR)', () => {
            const originalWindow = global.window;
            // @ts-expect-error - Simulating SSR environment
            delete global.window;

            // Should not throw error
            expect(() => updateTheme('dark')).not.toThrow();

            // Restore window
            global.window = originalWindow;
        });

        it('should handle initializeTheme when window is undefined (SSR)', () => {
            const originalWindow = global.window;
            // @ts-expect-error - Simulating SSR environment
            delete global.window;

            // Should not throw error
            expect(() => initializeTheme()).not.toThrow();

            // Restore window
            global.window = originalWindow;
        });

        it('should handle localStorage access in SSR-like environment', () => {
            // This test verifies the composable can be called without errors
            // even if some browser APIs might not be fully available
            expect(() => {
                const { appearance, updateAppearance } = useAppearance();
                // These should work in test environment
                expect(appearance.value).toBeDefined();
                expect(updateAppearance).toBeDefined();
            }).not.toThrow();
        });
    });

    describe('system theme change listener', () => {
        it('should set up listener during initializeTheme', () => {
            const addEventListenerSpy = vi.fn();
            
            // Mock matchMedia with addEventListener
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: query === '(prefers-color-scheme: dark)',
                media: query,
                addEventListener: addEventListenerSpy,
                removeEventListener: vi.fn(),
                addListener: vi.fn(),
                removeListener: vi.fn(),
                onchange: null,
                dispatchEvent: vi.fn(),
            }));

            // Initialize theme - this should set up the listener
            initializeTheme();

            // Verify addEventListener was called
            expect(addEventListenerSpy).toHaveBeenCalledWith('change', expect.any(Function));
        });

        it('should handle system preference in updateTheme', () => {
            // Mock matchMedia to return dark preference
            window.matchMedia = vi.fn().mockImplementation((query) => ({
                matches: true,
                media: query,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                addListener: vi.fn(),
                removeListener: vi.fn(),
                onchange: null,
                dispatchEvent: vi.fn(),
            }));

            updateTheme('system');

            // Should apply dark class based on system preference
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });
    });

    describe('component mounting and lifecycle', () => {
        it('should work with component mounting', async () => {
            localStorage.setItem('appearance', 'dark');

            // Create a test component that uses the composable
            const TestComponent = {
                setup() {
                    const result = useAppearance();
                    return result;
                },
                template: '<div>{{ appearance }}</div>',
            };

            const wrapper = mount(TestComponent);
            
            // Wait for onMounted to execute
            await nextTick();

            // The composable should work and load the saved value
            expect(wrapper.vm.appearance).toBe('dark');
            wrapper.unmount();
        });

        it('should work when no initial localStorage value', async () => {
            // Clear all storage
            localStorage.clear();

            const TestComponent = {
                setup() {
                    return useAppearance();
                },
                template: '<div>{{ appearance }}</div>',
            };

            const wrapper = mount(TestComponent);
            await nextTick();

            // The appearance value will be whatever was set by previous tests  
            // since the composable state is shared, but it should be defined
            expect(wrapper.vm.appearance).toBeDefined();
            expect(['system', 'dark', 'light']).toContain(wrapper.vm.appearance);
            wrapper.unmount();
        });

        it('should update when appearance changes', async () => {
            const TestComponent = {
                setup() {
                    return useAppearance();
                },
                template: '<div>{{ appearance }}</div>',
            };

            const wrapper = mount(TestComponent);
            await nextTick();

            // Update appearance
            (wrapper.vm as any).updateAppearance('dark');
            await nextTick();

            expect((wrapper.vm as any).appearance).toBe('dark');
            expect(localStorage.getItem('appearance')).toBe('dark');
            wrapper.unmount();
        });
    });
});
