import { computed, type ComputedRef } from 'vue';

/**
 * Format a date string to a localized date format
 */
export function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

/**
 * Check if any search filters are active
 */
export function useActiveFilters(
    searchInput: ComputedRef<string> | { value: string },
    selectedTag: ComputedRef<string> | { value: string },
    selectedAuthor: ComputedRef<string> | { value: string }
): ComputedRef<boolean> {
    return computed(() => {
        const search = 'value' in searchInput ? searchInput.value : searchInput;
        const tag = 'value' in selectedTag ? selectedTag.value : selectedTag;
        const author = 'value' in selectedAuthor ? selectedAuthor.value : selectedAuthor;
        return !!(search || tag || author);
    });
}
