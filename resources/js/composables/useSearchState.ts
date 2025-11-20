import { ref } from 'vue';

/**
 * Composable for managing search sidebar state
 */
export function useSearchState() {
    const isSearchOpen = ref(false);

    const openSearch = () => {
        isSearchOpen.value = true;
    };

    const closeSearch = () => {
        isSearchOpen.value = false;
    };

    const toggleSearch = () => {
        isSearchOpen.value = !isSearchOpen.value;
    };

    return {
        isSearchOpen,
        openSearch,
        closeSearch,
        toggleSearch,
    };
}
