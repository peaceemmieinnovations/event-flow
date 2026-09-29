export function setupDiscoveryMotion(motion) {
    const section = motion.root.querySelector('[data-discovery]');

    if (!section) {
        return;
    }

    const eyebrow = section.querySelector('[data-discovery-eyebrow]');
    const heading = section.querySelector('[data-discovery-heading]');
    const copy = section.querySelector('[data-discovery-copy]');
    const console = section.querySelector('[data-discovery-console]');
    const filters = Array.from(section.querySelectorAll('[data-discovery-filter]'));
    const media = section.querySelector('[data-discovery-media]');
    const mediaInner = section.querySelector('[data-discovery-media-inner]');
    const image = section.querySelector('[data-discovery-image]');
    const details = section.querySelector('[data-discovery-details]');
    const railItems = Array.from(section.querySelectorAll('[data-discovery-rail-item]'));
    const emptyState = section.querySelector('[data-discovery-empty]');

    return motion.createMotionContext(section, ({ createScrollTimeline, createScrollTween }) => {
        const timeline = createScrollTimeline(
            {
                trigger: section,
                start: 'top 76%',
                once: true,
            },
            {
                defaults: { ease: 'power3.out' },
            },
        );

        if (eyebrow) {
            timeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 44 }, { autoAlpha: 1, yPercent: 0, duration: 0.4 }, 0);
        }

        if (heading) {
            timeline.fromTo(heading, { autoAlpha: 0, yPercent: 30 }, { autoAlpha: 1, yPercent: 0, duration: 0.66 }, 0.08);
        }

        if (copy) {
            timeline.fromTo(copy, { autoAlpha: 0, yPercent: 18 }, { autoAlpha: 1, yPercent: 0, duration: 0.44 }, 0.2);
        }

        if (console) {
            timeline.fromTo(console, { autoAlpha: 0, yPercent: 12 }, { autoAlpha: 1, yPercent: 0, duration: 0.58 }, 0.26);
        }

        if (filters.length) {
            timeline.fromTo(
                filters,
                { autoAlpha: 0, yPercent: 12 },
                { autoAlpha: 1, yPercent: 0, duration: 0.34, stagger: 0.045 },
                0.5,
            );
        }

        if (media) {
            timeline.fromTo(
                media,
                { clipPath: 'inset(0 0 100% 0)', scale: 1.025 },
                { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 0.92, ease: 'power4.inOut' },
                0.54,
            );
        }

        if (details) {
            timeline.fromTo(details, { autoAlpha: 0, yPercent: 20 }, { autoAlpha: 1, yPercent: 0, duration: 0.5 }, 0.88);
        }

        if (railItems.length) {
            timeline.fromTo(
                railItems,
                { autoAlpha: 0, yPercent: 14 },
                { autoAlpha: 1, yPercent: 0, duration: 0.38, stagger: 0.1 },
                0.98,
            );
        }

        if (emptyState) {
            timeline.fromTo(emptyState, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.48 }, 0.58);
        }

        if (motion.supportsEnhancedPointerMotion && mediaInner) {
            createScrollTween(
                mediaInner,
                { yPercent: 5, ease: 'none' },
                {
                    trigger: section,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.55,
                },
            );
        }

        const refreshScrollTriggers = () => motion.refreshScrollTriggers();
        let refreshFrame;

        if (image && !image.complete) {
            image.addEventListener('load', refreshScrollTriggers, { once: true });
        } else {
            refreshFrame = window.requestAnimationFrame(refreshScrollTriggers);
        }

        return () => {
            image?.removeEventListener('load', refreshScrollTriggers);

            if (refreshFrame !== undefined) {
                window.cancelAnimationFrame(refreshFrame);
            }
        };
    });
}
