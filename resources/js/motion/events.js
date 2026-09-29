export function setupEventsMotion(motion) {
    const page = motion.root.querySelector('[data-events-page]');

    if (!page) {
        return;
    }

    const eyebrow = page.querySelector('[data-events-eyebrow]');
    const heading = page.querySelector('[data-events-heading]');
    const copy = page.querySelector('[data-events-copy]');
    const toolbar = page.querySelector('[data-events-toolbar]');
    const categories = page.querySelector('[data-events-categories]');
    const categoryItems = Array.from(page.querySelectorAll('[data-events-category]'));
    const resultsHeader = page.querySelector('[data-events-results-header]');
    const grid = page.querySelector('[data-events-grid]');
    const cards = Array.from(page.querySelectorAll('[data-events-card]'));
    const mediaFrames = cards.map((card) => card.querySelector('.ef-event-card__media')).filter(Boolean);
    const leadMediaInner = page.querySelector('[data-events-card="lead"] [data-event-card-media]');
    const emptyState = page.querySelector('[data-events-empty]');
    const images = Array.from(page.querySelectorAll('img'));

    return motion.createMotionContext(page, ({ createScrollTimeline, createScrollTween, createTimeline }) => {
        const heroTimeline = createTimeline({
            defaults: { ease: 'power3.out' },
        });

        if (eyebrow) {
            heroTimeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 44 }, { autoAlpha: 1, yPercent: 0, duration: 0.4 }, 0);
        }

        if (heading) {
            heroTimeline.fromTo(heading, { autoAlpha: 0, yPercent: 32 }, { autoAlpha: 1, yPercent: 0, duration: 0.7 }, 0.08);
        }

        if (copy) {
            heroTimeline.fromTo(copy, { autoAlpha: 0, yPercent: 20 }, { autoAlpha: 1, yPercent: 0, duration: 0.46 }, 0.22);
        }

        if (toolbar) {
            const toolbarTimeline = createScrollTimeline(
                {
                    trigger: toolbar,
                    start: 'top 82%',
                    once: true,
                },
                {
                    defaults: { ease: 'power3.out' },
                },
            );

            toolbarTimeline.fromTo(toolbar, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.54 }, 0);

            if (categoryItems.length) {
                toolbarTimeline.fromTo(
                    categoryItems,
                    { autoAlpha: 0, yPercent: 12 },
                    { autoAlpha: 1, yPercent: 0, duration: 0.36, stagger: 0.045 },
                    0.15,
                );
            }

            if (categories) {
                toolbarTimeline.fromTo(categories, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.01 }, 0.15);
            }

            if (resultsHeader) {
                toolbarTimeline.fromTo(resultsHeader, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.42 }, 0.32);
            }
        }

        if (grid && cards.length) {
            const gridTimeline = createScrollTimeline(
                {
                    trigger: grid,
                    start: 'top 80%',
                    once: true,
                },
                {
                    defaults: { ease: 'power3.out' },
                },
            );

            gridTimeline.fromTo(
                cards,
                { autoAlpha: 0, yPercent: 14 },
                { autoAlpha: 1, yPercent: 0, duration: 0.52, stagger: 0.075 },
                0,
            );

            if (mediaFrames.length) {
                gridTimeline.fromTo(
                    mediaFrames,
                    { clipPath: 'inset(0 0 100% 0)', scale: 1.025 },
                    { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 0.8, stagger: 0.075, ease: 'power4.inOut' },
                    0.04,
                );
            }
        } else if (emptyState) {
            const emptyTimeline = createScrollTimeline(
                {
                    trigger: emptyState,
                    start: 'top 82%',
                    once: true,
                },
                {
                    defaults: { ease: 'power3.out' },
                },
            );

            emptyTimeline.fromTo(emptyState, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.48 });
        }

        if (motion.supportsEnhancedPointerMotion && leadMediaInner && grid) {
            createScrollTween(
                leadMediaInner,
                { yPercent: 5, ease: 'none' },
                {
                    trigger: grid,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 0.55,
                },
            );
        }

        const refreshScrollTriggers = () => motion.refreshScrollTriggers();
        const pendingImages = images.filter((image) => !image.complete);
        let refreshFrame;

        if (pendingImages.length) {
            pendingImages.forEach((image) => image.addEventListener('load', refreshScrollTriggers, { once: true }));
        } else {
            refreshFrame = window.requestAnimationFrame(refreshScrollTriggers);
        }

        return () => {
            pendingImages.forEach((image) => image.removeEventListener('load', refreshScrollTriggers));

            if (refreshFrame !== undefined) {
                window.cancelAnimationFrame(refreshFrame);
            }
        };
    });
}
