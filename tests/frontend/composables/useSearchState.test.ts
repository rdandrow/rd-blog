import { describe, expect, it } from 'vitest';
import { useSearchState } from '@/composables/useSearchState';

describe('useSearchState', () => {
    it('should initialize with search closed', () => {
        const { isSearchOpen } = useSearchState();
        
        expect(isSearchOpen.value).toBe(false);
    });

    it('should open search', () => {
        const { isSearchOpen, openSearch } = useSearchState();
        
        expect(isSearchOpen.value).toBe(false);
        
        openSearch();
        
        expect(isSearchOpen.value).toBe(true);
    });

    it('should close search', () => {
        const { isSearchOpen, openSearch, closeSearch } = useSearchState();
        
        openSearch();
        expect(isSearchOpen.value).toBe(true);
        
        closeSearch();
        
        expect(isSearchOpen.value).toBe(false);
    });

    it('should toggle search state', () => {
        const { isSearchOpen, toggleSearch } = useSearchState();
        
        expect(isSearchOpen.value).toBe(false);
        
        toggleSearch();
        expect(isSearchOpen.value).toBe(true);
        
        toggleSearch();
        expect(isSearchOpen.value).toBe(false);
        
        toggleSearch();
        expect(isSearchOpen.value).toBe(true);
    });

    it('should handle multiple consecutive opens', () => {
        const { isSearchOpen, openSearch } = useSearchState();
        
        openSearch();
        openSearch();
        openSearch();
        
        expect(isSearchOpen.value).toBe(true);
    });

    it('should handle multiple consecutive closes', () => {
        const { isSearchOpen, openSearch, closeSearch } = useSearchState();
        
        openSearch();
        closeSearch();
        closeSearch();
        closeSearch();
        
        expect(isSearchOpen.value).toBe(false);
    });

    it('should maintain independent state across instances', () => {
        const search1 = useSearchState();
        const search2 = useSearchState();
        
        search1.openSearch();
        
        expect(search1.isSearchOpen.value).toBe(true);
        expect(search2.isSearchOpen.value).toBe(false);
    });

    it('should be reactive', () => {
        const { isSearchOpen, toggleSearch } = useSearchState();
        
        toggleSearch();
        expect(isSearchOpen.value).toBe(true);
        
        toggleSearch();
        expect(isSearchOpen.value).toBe(false);
    });
});
