import { describe, expect, it } from 'vitest';

describe('Vitest Setup', () => {
    it('should run basic test', () => {
        expect(1 + 1).toBe(2);
    });

    it('should have access to globals', () => {
        expect(window).toBeDefined();
        expect(document).toBeDefined();
    });

    it('should have localStorage mocked', () => {
        localStorage.setItem('test', 'value');
        expect(localStorage.getItem('test')).toBe('value');
        localStorage.clear();
    });

    it('should have CSRF token available', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        expect(csrfToken).toBeDefined();
        expect(csrfToken?.getAttribute('content')).toBe('test-csrf-token');
    });
});
