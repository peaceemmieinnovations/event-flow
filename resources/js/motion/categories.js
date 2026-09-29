export function setupCategoriesMotion(motion) {
    const section = motion.root.querySelector('[data-categories]');

    if (!section) {
        return;
    }

    const eyebrow = section.querySelector('[data-categories-eyebrow]');
    const heading = section.querySelector('[data-categories-heading]');
    const copy = section.querySelector('[data-categories-copy]');
    const cta = section.querySelector('[data-categories-cta]');
    const cards = Array.from(section.querySelectorAll('[data-category-card]'));
    const leadCard = cards[0];
    const leadMedia = leadCard?.querySelector('[data-category-media]');
    const leadMediaInner = leadCard?.querySelector('[data-category-media-inner]');
    const leadContent = leadCard?.querySelector('.ef-category-card__content');
    const supportingCards = cards.slice(1);
    const images = Array.from(section.querySelectorAll('img'));

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
            timeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 44 }, { autoAlpha: 1, yPercent: 0, duration: 0.4 });
        }

        if (heading) {
            timeline.fromTo(heading, { autoAlpha: 0, yPercent: 34 }, { autoAlpha: 1, yPercent: 0, duration: 0.68 }, '-=0.16');
        }

        if (copy) {
            timeline.fromTo(copy, { autoAlpha: 0, yPercent: 20 }, { autoAlpha: 1, yPercent: 0, duration: 0.44 }, '-=0.3');
        }

        if (leadMedia) {
            timeline.fromTo(
                leadMedia,
                { clipPath: 'inset(0 0 100% 0)', scale: 1.03 },
                { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 0.98, ease: 'power4.inOut' },
                '-=0.08',
            );
        }

        if (leadContent) {
            timeline.fromTo(leadContent, { autoAlpha: 0, yPercent: 24 }, { autoAlpha: 1, yPercent: 0, duration: 0.5 }, '-=0.46');
        }

        if (supportingCards.length) {
            timeline.fromTo(
                supportingCards,
                { autoAlpha: 0, yPercent: 14 },
                { autoAlpha: 1, yPercent: 0, duration: 0.46, stagger: 0.08 },
                '-=0.3',
            );
        }

        if (cta) {
            timeline.fromTo(cta, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.4 }, '-=0.22');
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
