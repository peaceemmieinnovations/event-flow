import { collectMotionTargets } from './hooks';
import { prefersReducedMotion, REDUCED_MOTION_QUERY } from './reduced-motion';
import {
    createMotionContext,
    createScrollTimeline,
    createScrollTrigger,
    createScrollTween,
    createTimeline,
    createTween,
    gsap,
    refreshScrollTriggers,
    ScrollTrigger,
} from './scroll';

const motionMediaQueries = Object.freeze({
    all: 'all',
    reducedMotion: REDUCED_MOTION_QUERY,
    enhancedPointer: '(min-width: 64rem) and (hover: hover) and (pointer: fine)',
});

let activeMotionCleanup;

export function initializeMotion(options = {}) {
    activeMotionCleanup?.();

    const cleanup = createMotionEngine(options);
    const dispose = () => {
        cleanup();

        if (activeMotionCleanup === dispose) {
            activeMotionCleanup = undefined;
        }
    };

    activeMotionCleanup = dispose;

    return dispose;
}

export function createMotionEngine({ root, setup, setupInteractions } = {}) {
    const scope = root ?? (typeof document !== 'undefined' ? document : null);

    if (!scope || typeof window === 'undefined') {
        return () => {};
    }

    const interactionCleanup = setupInteractions?.({
        root: scope,
        gsap,
        isReducedMotion: prefersReducedMotion,
    });
    const media = gsap.matchMedia();
    let destroyed = false;

    media.add(motionMediaQueries, (context) => {
        const { enhancedPointer, reducedMotion } = context.conditions;
        const motionIsReduced = Boolean(reducedMotion ?? prefersReducedMotion());
        const motion = {
            root: scope,
            targets: collectMotionTargets(scope),
            gsap,
            ScrollTrigger,
            createMotionContext,
            createTimeline,
            createTween,
            createScrollTrigger,
            createScrollTimeline,
            createScrollTween,
            refreshScrollTriggers,
            prefersReducedMotion: motionIsReduced,
            canAnimate: !motionIsReduced,
            supportsEnhancedPointerMotion: Boolean(enhancedPointer) && !motionIsReduced,
        };

        if (!motion.canAnimate) {
            return;
        }

        return setup?.(motion);
    });

    return () => {
        if (destroyed) {
            return;
        }

        destroyed = true;
        interactionCleanup?.();
        media.revert();
    };
}

export { motionSelectors } from './hooks';
export { prefersReducedMotion, watchReducedMotion } from './reduced-motion';
export {
    createMotionContext,
    createScrollTimeline,
    createScrollTrigger,
    createScrollTween,
    createTimeline,
    createTween,
    gsap,
    refreshScrollTriggers,
    ScrollTrigger,
} from './scroll';
