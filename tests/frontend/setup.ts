import { vi } from 'vitest';

// Mock localStorage
const localStorageMock = (() => {
    let store: Record<string, string> = {};

    return {
        getItem: (key: string) => store[key] || null,
        setItem: (key: string, value: string) => {
            store[key] = value.toString();
        },
        removeItem: (key: string) => {
            delete store[key];
        },
        clear: () => {
            store = {};
        },
        get length() {
            return Object.keys(store).length;
        },
        key: (index: number) => {
            const keys = Object.keys(store);
            return keys[index] || null;
        },
    };
})();

Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
    writable: true,
});

// Mock sessionStorage
Object.defineProperty(window, 'sessionStorage', {
    value: localStorageMock,
    writable: true,
});

// Mock Inertia router
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        router: {
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            reload: vi.fn(),
            visit: vi.fn(),
            on: vi.fn(),
        },
        usePage: () => ({
            url: '/',
            component: 'Test',
            version: '1',
            props: {
                auth: {
                    user: {
                        id: 1,
                        name: 'Test User',
                        email: 'test@example.com',
                        role: 'admin',
                    },
                },
                flash: {},
                errors: {},
            },
        }),
    };
});

// Mock CSRF token
const metaTag = document.createElement('meta');
metaTag.setAttribute('name', 'csrf-token');
metaTag.setAttribute('content', 'test-csrf-token');
document.head.appendChild(metaTag);

// Mock window.matchMedia for theme detection
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

// Suppress console warnings in tests (optional)
global.console = {
    ...console,
    warn: vi.fn(),
    error: vi.fn(),
};
