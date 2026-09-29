export function setupEventDetailMotion(motion) {
    const page = motion.root.querySelector('[data-event-detail]');

    if (!page) {
        return;
    }

    const back = page.querySelector('[data-event-detail-back]');
    const media = page.querySelector('[data-event-detail-media]');
    const mediaInner = page.querySelector('[data-event-detail-media-inner]');
    const image = page.querySelector('[data-event-detail-image]');
    const eyebrow = page.querySelector('[data-event-detail-eyebrow]');
    const title = page.querySelector('[data-event-detail-title]');
    const meta = page.querySelector('[data-event-detail-meta]');
    const action = page.querySelector('[data-event-detail-action]');
    const content = page.querySelector('[data-event-detail-content]');
    const supportingContent = [
        page.querySelector('[data-event-detail-description]'),
        page.querySelector('[data-event-detail-information]'),
        page.querySelector('[data-event-detail-footer]'),
    ].filter(Boolean);

    return motion.createMotionContext(page, ({ createScrollTimeline, createScrollTween, createTimeline }) => {
        const introTimeline = createTimeline({
            defaults: { ease: 'power3.out' },
        });

        if (back) {
            introTimeline.fromTo(back, { autoAlpha: 0, yPercent: 24 }, { autoAlpha: 1, yPercent: 0, duration: 0.38 }, 0);
        }

        if (media) {
            introTimeline.fromTo(
                media,
                { clipPath: 'inset(0 0 100% 0)', scale: 1.018 },
                { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 0.96, ease: 'power4.inOut' },
                0.06,
            );
        }

        if (eyebrow) {
            introTimeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 44 }, { autoAlpha: 1, yPercent: 0, duration: 0.38 }, 0.3);
        }

        if (title) {
            introTimeline.fromTo(title, { autoAlpha: 0, yPercent: 26 }, { autoAlpha: 1, yPercent: 0, duration: 0.68 }, 0.38);
        }

        if (meta) {
            introTimeline.fromTo(meta, { autoAlpha: 0, yPercent: 18 }, { autoAlpha: 1, yPercent: 0, duration: 0.46 }, 0.58);
        }

        if (action) {
            introTimeline.fromTo(action, { autoAlpha: 0, yPercent: 16 }, { autoAlpha: 1, yPercent: 0, duration: 0.42 }, 0.72);
        }

        if (content && supportingContent.length) {
            const contentTimeline = createScrollTimeline(
                {
                    trigger: content,
                    start: 'top 80%',
                    once: true,
                },
                {
                    defaults: { ease: 'power3.out' },
                },
            );

            contentTimeline.fromTo(
                supportingContent,
                { autoAlpha: 0, yPercent: 14 },
                { autoAlpha: 1, yPercent: 0, duration: 0.5, stagger: 0.1 },
            );
        }

        if (motion.supportsEnhancedPointerMotion && mediaInner) {
            createScrollTween(
                mediaInner,
                { yPercent: 5, ease: 'none' },
                {
                    trigger: page,
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
