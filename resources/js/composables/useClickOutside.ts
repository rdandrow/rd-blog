import { onMounted, onUnmounted, Ref } from 'vue';

/**
 * Composable to handle click outside events for dropdowns, modals, etc.
 * 
 * @param elementRef - Ref to the HTML element to monitor
 * @param callback - Function to call when clicking outside the element
 */
export function useClickOutside(
    elementRef: Ref<HTMLElement | null>,
    callback: () => void
) {
    const handleClickOutside = (event: MouseEvent) => {
        const target = event.target as Node;
        if (elementRef.value && !elementRef.value.contains(target)) {
            callback();
        }
    };

    onMounted(() => {
        // Use capture phase to ensure we catch the event before other handlers
        document.addEventListener('click', handleClickOutside, true);
    });

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside, true);
    });
}
