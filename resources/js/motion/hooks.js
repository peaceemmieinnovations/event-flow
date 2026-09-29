export const motionSelectors = Object.freeze({
    reveal: '[data-reveal]',
    scrollSection: '[data-scroll-section]',
    parallax: '[data-parallax]',
    eventCard: '[data-event-card]',
    magnetic: '[data-magnetic]',
});

export function collectMotionTargets(root) {
    const scope = root ?? (typeof document !== 'undefined' ? document : null);

    return Object.fromEntries(
        Object.entries(motionSelectors).map(([name, selector]) => [
            name,
            scope ? Array.from(scope.querySelectorAll(selector)) : [],
        ]),
    );
}
