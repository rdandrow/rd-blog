import { computed, ref } from 'vue';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { formatDate, useActiveFilters } from '@/composables/useBlogUtils';

describe('useBlogUtils', () => {
    describe('formatDate', () => {
        it('should format date string correctly', () => {
            // Use UTC timezone (Z suffix) to avoid timezone issues
            const result = formatDate('2024-01-15T12:00:00Z');
            expect(result).toBe('January 15, 2024');
        });

        it('should handle different month', () => {
            const result = formatDate('2024-12-25T12:00:00Z');
            expect(result).toBe('December 25, 2024');
        });

        it('should handle single digit day', () => {
            const result = formatDate('2024-03-05T12:00:00Z');
            expect(result).toBe('March 5, 2024');
        });

        it('should handle different year', () => {
            const result = formatDate('2023-06-10T12:00:00Z');
            expect(result).toBe('June 10, 2023');
        });

        it('should handle ISO 8601 format with time', () => {
            const result = formatDate('2024-01-15T14:30:00Z');
            // Result may vary based on timezone, but should be a valid date
            expect(result).toMatch(/January \d{1,2}, 2024/);
        });

        it('should handle timestamp format', () => {
            const result = formatDate('2024-01-15T12:00:00.000Z');
            expect(result).toBe('January 15, 2024');
        });

        it('should handle leap year date', () => {
            const result = formatDate('2024-02-29T12:00:00Z');
            expect(result).toBe('February 29, 2024');
        });

        it('should handle first day of year', () => {
            const result = formatDate('2024-01-01T12:00:00Z');
            expect(result).toBe('January 1, 2024');
        });

        it('should handle last day of year', () => {
            const result = formatDate('2024-12-31T12:00:00Z');
            expect(result).toBe('December 31, 2024');
        });

        it('should handle date object conversion', () => {
            // Test that Date objects work correctly
            const date = new Date('2024-06-15T12:00:00Z');
            const result = formatDate(date.toISOString());
            expect(result).toBe('June 15, 2024');
        });
    });

    describe('useActiveFilters', () => {
        beforeEach(() => {
            vi.clearAllMocks();
        });

        it('should return false when all filters are empty (using refs)', () => {
            const searchInput = ref('');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(false);
        });

        it('should return true when search input has value', () => {
            const searchInput = ref('test query');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should return true when tag is selected', () => {
            const searchInput = ref('');
            const selectedTag = ref('javascript');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should return true when author is selected', () => {
            const searchInput = ref('');
            const selectedTag = ref('');
            const selectedAuthor = ref('John Doe');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should return true when multiple filters are active', () => {
            const searchInput = ref('test');
            const selectedTag = ref('vue');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should return true when all filters are active', () => {
            const searchInput = ref('search term');
            const selectedTag = ref('typescript');
            const selectedAuthor = ref('Jane Smith');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should be reactive to search input changes', () => {
            const searchInput = ref('');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(false);

            // Change search input
            searchInput.value = 'test';
            expect(hasActiveFilters.value).toBe(true);

            // Clear search input
            searchInput.value = '';
            expect(hasActiveFilters.value).toBe(false);
        });

        it('should be reactive to tag changes', () => {
            const searchInput = ref('');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(false);

            // Select a tag
            selectedTag.value = 'react';
            expect(hasActiveFilters.value).toBe(true);

            // Clear tag
            selectedTag.value = '';
            expect(hasActiveFilters.value).toBe(false);
        });

        it('should be reactive to author changes', () => {
            const searchInput = ref('');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(false);

            // Select an author
            selectedAuthor.value = 'Author Name';
            expect(hasActiveFilters.value).toBe(true);

            // Clear author
            selectedAuthor.value = '';
            expect(hasActiveFilters.value).toBe(false);
        });

        it('should handle computed refs', () => {
            const searchInput = ref('test');
            const selectedTag = ref('tag');
            const selectedAuthor = ref('author');

            // Create computed refs from the base refs
            const computedSearch = computed(() => searchInput.value);
            const computedTag = computed(() => selectedTag.value);
            const computedAuthor = computed(() => selectedAuthor.value);

            const hasActiveFilters = useActiveFilters(computedSearch, computedTag, computedAuthor);

            expect(hasActiveFilters.value).toBe(true);

            // Update base refs
            searchInput.value = '';
            selectedTag.value = '';
            selectedAuthor.value = '';

            expect(hasActiveFilters.value).toBe(false);
        });

        it('should handle whitespace-only values as active', () => {
            const searchInput = ref('   ');
            const selectedTag = ref('');
            const selectedAuthor = ref('');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            // Whitespace is truthy, so it counts as active
            expect(hasActiveFilters.value).toBe(true);
        });

        it('should handle mixed ref and plain object inputs', () => {
            const searchInput = { value: 'test' };
            const selectedTag = ref('');
            const selectedAuthor = { value: '' };

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should work with plain ComputedRef values (non-reactive branch)', () => {
            const searchInput = computed(() => 'test query');
            const selectedTag = computed(() => '');
            const selectedAuthor = computed(() => '');

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should work with all plain object values (non-ref branch)', () => {
            const searchInput = { value: '' };
            const selectedTag = { value: 'javascript' };
            const selectedAuthor = { value: 'John Doe' };

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });

        it('should correctly detect no filters with all plain objects', () => {
            const searchInput = { value: '' };
            const selectedTag = { value: '' };
            const selectedAuthor = { value: '' };

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(false);
        });

        it('should handle mixed ref and plain value types', () => {
            const searchInput = ref('test search');
            const selectedTag = { value: 'javascript' }; // Plain object
            const selectedAuthor = computed(() => 'John'); // Computed ref

            const hasActiveFilters = useActiveFilters(searchInput, selectedTag, selectedAuthor);

            expect(hasActiveFilters.value).toBe(true);
        });
    });
});
