export function getInitials(fullName?: string): string {
    if (!fullName) return '';

    const names = fullName.trim().split(' ');

    // Note: names.length will always be >= 1 after split, so this check is defensive programming
    // for potential future refactoring or edge cases. Could be removed if preferred.
    if (names.length === 0 || names[0] === '') return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

export function useInitials() {
    return { getInitials };
}
