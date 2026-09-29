export function setupFeaturedEventsMotion(motion) {
    const section = motion.root.querySelector('[data-featured-events]');

    if (!section) {
        return;
    }

    const eyebrow = section.querySelector('[data-featured-events-eyebrow]');
    const heading = section.querySelector('[data-featured-events-heading]');
    const copy = section.querySelector('[data-featured-events-copy]');
    const cta = section.querySelector('[data-featured-events-cta]');
    const leadCard = section.querySelector('[data-featured-event-card="lead"]');
    const leadMedia = leadCard?.querySelector('.ef-event-card__media');
    const leadMediaInner = leadCard?.querySelector('[data-event-card-media]');
    const leadContent = leadCard?.querySelector('.ef-event-card__content');
    const leadImage = leadCard?.querySelector('img');
    const supportingCards = Array.from(section.querySelectorAll('[data-featured-event-card="supporting"]'));
    const emptyState = section.querySelector('[data-featured-events-empty]');

    return motion.createMotionContext(section, ({ createScrollTimeline, createScrollTween }) => {
        const timeline = createScrollTimeline(
            {
                trigger: section,
                start: 'top 74%',
                once: true,
            },
            {
                defaults: { ease: 'power3.out' },
            },
        );

        if (eyebrow) {
            timeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 48 }, { autoAlpha: 1, yPercent: 0, duration: 0.42 });
        }

        if (heading) {
            timeline.fromTo(heading, { autoAlpha: 0, yPercent: 34 }, { autoAlpha: 1, yPercent: 0, duration: 0.68 }, '-=0.18');
        }

        if (copy) {
            timeline.fromTo(copy, { autoAlpha: 0, yPercent: 20 }, { autoAlpha: 1, yPercent: 0, duration: 0.44 }, '-=0.32');
        }

        if (cta) {
            timeline.fromTo(cta, { autoAlpha: 0, yPercent: 16 }, { autoAlpha: 1, yPercent: 0, duration: 0.4 }, '-=0.28');
        }

        if (leadMedia) {
            timeline.fromTo(
                leadMedia,
                { clipPath: 'inset(0 0 100% 0)', scale: 1.035 },
                { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 0.98, ease: 'power4.inOut' },
                '-=0.12',
            );
        }

        if (leadContent) {
            timeline.fromTo(leadContent, { autoAlpha: 0, yPercent: 24 }, { autoAlpha: 1, yPercent: 0, duration: 0.52 }, '-=0.5');
        }

        if (supportingCards.length) {
            timeline.fromTo(
                supportingCards,
                { autoAlpha: 0, yPercent: 18 },
                { autoAlpha: 1, yPercent: 0, duration: 0.48, stagger: 0.12 },
                '-=0.32',
            );
        }

        if (emptyState) {
            timeline.fromTo(emptyState, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.48 }, '-=0.22');
        }

        if (motion.supportsEnhancedPointerMotion && leadMediaInner) {
            createScrollTween(
                leadMediaInner,
                { yPercent: 6, ease: 'none' },
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

        if (leadImage && !leadImage.complete) {
            leadImage.addEventListener('load', refreshScrollTriggers, { once: true });
        } else {
            refreshFrame = window.requestAnimationFrame(refreshScrollTriggers);
        }

        return () => {
            leadImage?.removeEventListener('load', refreshScrollTriggers);

            if (refreshFrame !== undefined) {
                window.cancelAnimationFrame(refreshFrame);
            }
        };
    });
}
