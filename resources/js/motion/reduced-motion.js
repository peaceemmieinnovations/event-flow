export const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

function supportsMatchMedia() {
    return typeof window !== 'undefined' && typeof window.matchMedia === 'function';
}

export function prefersReducedMotion() {
    return supportsMatchMedia() && window.matchMedia(REDUCED_MOTION_QUERY).matches;
}

export function watchReducedMotion(callback) {
    if (!supportsMatchMedia()) {
        return () => {};
    }

    const mediaQuery = window.matchMedia(REDUCED_MOTION_QUERY);
    const handleChange = (event) => callback(event.matches);

    mediaQuery.addEventListener('change', handleChange);

    return () => mediaQuery.removeEventListener('change', handleChange);
}
