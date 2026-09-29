import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const defaultScrollTriggerOptions = Object.freeze({
    start: 'top bottom',
    end: 'bottom top',
    invalidateOnRefresh: true,
});

export { gsap, ScrollTrigger };

export function createTimeline(options = {}) {
    return gsap.timeline(options);
}

export function createTween(targets, animation) {
    return gsap.to(targets, animation);
}

export function createScrollTrigger(options = {}) {
    return ScrollTrigger.create(resolveScrollTriggerOptions(options));
}

export function createScrollTimeline(scrollTriggerOptions = {}, timelineOptions = {}) {
    return gsap.timeline({
        ...timelineOptions,
        scrollTrigger: resolveScrollTriggerOptions(scrollTriggerOptions),
    });
}

export function createScrollTween(targets, animation, scrollTriggerOptions = {}) {
    return gsap.to(targets, {
        ...animation,
        scrollTrigger: resolveScrollTriggerOptions(scrollTriggerOptions),
    });
}

export function createMotionContext(scope, setup) {
    let customCleanup;

    const context = gsap.context(() => {
        customCleanup = setup({
            gsap,
            ScrollTrigger,
            createTimeline,
            createTween,
            createScrollTrigger,
            createScrollTimeline,
            createScrollTween,
        });
    }, scope);

    return () => {
        customCleanup?.();
        context.revert();
    };
}

export function refreshScrollTriggers() {
    ScrollTrigger.refresh();
}

function resolveScrollTriggerOptions(options) {
    if (!options.trigger) {
        throw new Error('A ScrollTrigger requires a trigger element.');
    }

    return {
        ...defaultScrollTriggerOptions,
        ...options,
    };
}
