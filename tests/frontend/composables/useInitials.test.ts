import { describe, expect, it } from 'vitest';
import { getInitials, useInitials } from '@/composables/useInitials';

describe('useInitials', () => {
    describe('getInitials function', () => {
        it('should return empty string for undefined', () => {
            expect(getInitials(undefined)).toBe('');
        });

        it('should return empty string for empty string', () => {
            expect(getInitials('')).toBe('');
        });

        it('should return empty string for whitespace only', () => {
            expect(getInitials('   ')).toBe('');
        });

        it('should return single uppercase letter for single name', () => {
            expect(getInitials('John')).toBe('J');
        });

        it('should return first letter uppercase for lowercase single name', () => {
            expect(getInitials('john')).toBe('J');
        });

        it('should return first and last initials for full name', () => {
            expect(getInitials('John Doe')).toBe('JD');
        });

        it('should return uppercase initials for lowercase names', () => {
            expect(getInitials('john doe')).toBe('JD');
        });

        it('should handle three names (first and last only)', () => {
            expect(getInitials('John Michael Doe')).toBe('JD');
        });

        it('should handle four names (first and last only)', () => {
            expect(getInitials('John Michael Andrew Doe')).toBe('JD');
        });

        it('should trim extra whitespace', () => {
            expect(getInitials('  John   Doe  ')).toBe('JD');
        });

        it('should handle multiple spaces between names', () => {
            expect(getInitials('John    Doe')).toBe('JD');
        });

        it('should handle names with special characters', () => {
            expect(getInitials("O'Brien Smith")).toBe('OS');
        });

        it('should handle hyphenated last names', () => {
            expect(getInitials('Mary Anne Smith-Jones')).toBe('MS');
        });

        it('should handle single character name', () => {
            expect(getInitials('J')).toBe('J');
        });

        it('should handle names with numbers', () => {
            expect(getInitials('John 2nd')).toBe('J2');
        });
    });

    describe('useInitials composable', () => {
        it('should return object with getInitials function', () => {
            const { getInitials: fn } = useInitials();
            expect(fn).toBeDefined();
            expect(typeof fn).toBe('function');
        });

        it('should provide working getInitials function', () => {
            const { getInitials: fn } = useInitials();
            expect(fn('Test User')).toBe('TU');
        });
    });
});
