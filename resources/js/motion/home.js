export function setupHomeMotion(motion) {
    const hero = motion.root.querySelector('[data-home-hero]');

    if (!hero) {
        return;
    }

    const header = motion.root.querySelector('[data-site-header]');
    const visual = hero.querySelector('[data-hero-visual]');
    const media = hero.querySelector('[data-hero-media]');
    const eyebrow = hero.querySelector('[data-hero-eyebrow]');
    const titleLines = Array.from(hero.querySelectorAll('[data-hero-title-line]'));
    const copy = hero.querySelector('[data-hero-copy]');
    const actions = hero.querySelector('[data-hero-actions]');
    const detail = hero.querySelector('[data-hero-detail]');
    const scrollIndicator = hero.querySelector('[data-hero-scroll-indicator]');

    return motion.createMotionContext(hero, ({ gsap, createScrollTween }) => {
        const timeline = gsap.timeline({
            defaults: { ease: 'power3.out' },
        });

        if (header) {
            timeline.fromTo(header, { autoAlpha: 0, yPercent: -80 }, { autoAlpha: 1, yPercent: 0, duration: 0.62 });
        }

        if (visual) {
            timeline.fromTo(
                visual,
                { clipPath: 'inset(0 0 100% 0)', scale: 1.035 },
                { clipPath: 'inset(0 0 0% 0)', scale: 1, duration: 1.14, ease: 'power4.inOut' },
                header ? '-=0.28' : 0,
            );
        }

        if (eyebrow) {
            timeline.fromTo(eyebrow, { autoAlpha: 0, yPercent: 65 }, { autoAlpha: 1, yPercent: 0, duration: 0.48 }, '-=0.48');
        }

        if (titleLines.length) {
            timeline.fromTo(titleLines, { autoAlpha: 0, yPercent: 112 }, { autoAlpha: 1, yPercent: 0, duration: 0.82, stagger: 0.1 }, '-=0.28');
        }

        if (copy) {
            timeline.fromTo(copy, { autoAlpha: 0, yPercent: 28 }, { autoAlpha: 1, yPercent: 0, duration: 0.5 }, '-=0.28');
        }

        if (actions) {
            timeline.fromTo(actions, { autoAlpha: 0, yPercent: 22 }, { autoAlpha: 1, yPercent: 0, duration: 0.5 }, '-=0.28');
        }

        if (detail) {
            timeline.fromTo(detail, { autoAlpha: 0, yPercent: 18 }, { autoAlpha: 1, yPercent: 0, duration: 0.45 }, '-=0.26');
        }

        if (scrollIndicator) {
            timeline.fromTo(scrollIndicator, { autoAlpha: 0, yPercent: 14 }, { autoAlpha: 1, yPercent: 0, duration: 0.42 }, '-=0.2');
        }

        if (motion.supportsEnhancedPointerMotion && media) {
            createScrollTween(
                media,
                { yPercent: 7, ease: 'none' },
                {
                    trigger: hero,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 0.5,
                },
            );
        }

        const refreshScrollTriggers = () => motion.refreshScrollTriggers();

        let refreshFrame;

        if (media && !media.complete) {
            media.addEventListener('load', refreshScrollTriggers, { once: true });
        } else {
            refreshFrame = window.requestAnimationFrame(refreshScrollTriggers);
        }

        return () => {
            media?.removeEventListener('load', refreshScrollTriggers);

            if (refreshFrame) {
                window.cancelAnimationFrame(refreshFrame);
            }
        };
    });
}
