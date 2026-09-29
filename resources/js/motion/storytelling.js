export function setupStorytellingMotion(motion) {
    const section = motion.root.querySelector('[data-storytelling]');
    const viewport = section?.querySelector('[data-storytelling-viewport]');

    if (!section || !viewport) {
        return;
    }

    const intro = section.querySelector('[data-storytelling-intro]');
    const visual = section.querySelector('[data-storytelling-visual]');
    const mediaFrame = section.querySelector('[data-storytelling-media]');
    const mediaInner = section.querySelector('[data-storytelling-media-inner]');
    const image = section.querySelector('[data-storytelling-image]');
    const statementOne = section.querySelector('[data-storytelling-statement="one"]');
    const statementTwo = section.querySelector('[data-storytelling-statement="two"]');
    const statementThree = section.querySelector('[data-storytelling-statement="three"]');
    const supporting = section.querySelector('[data-storytelling-supporting]');
    const progressFill = section.querySelector('[data-storytelling-progress-fill]');

    return motion.createMotionContext(section, ({ gsap, createScrollTimeline }) => {
        const desktopMedia = gsap.matchMedia();

        desktopMedia.add('(min-width: 64rem) and (hover: hover) and (pointer: fine)', () => {
            viewport.dataset.storytellingReady = 'true';

            const timeline = createScrollTimeline(
                {
                    trigger: viewport,
                    pin: viewport,
                    start: 'top top',
                    end: () => `+=${Math.round(window.innerHeight * 1.7)}`,
                    scrub: 0.65,
                    pinSpacing: true,
                    anticipatePin: 1,
                },
                {
                    defaults: { ease: 'none' },
                },
            );

            gsap.set([statementTwo, statementThree, supporting].filter(Boolean), { autoAlpha: 0, yPercent: 14 });

            if (progressFill) {
                gsap.set(progressFill, { scaleX: 0, transformOrigin: 'left center' });
            }

            if (intro) {
                timeline.fromTo(intro, { autoAlpha: 0, yPercent: -18 }, { autoAlpha: 1, yPercent: 0, duration: 0.12 }, 0);
            }

            if (visual) {
                timeline.fromTo(
                    visual,
                    { autoAlpha: 0, yPercent: 4 },
                    { autoAlpha: 1, yPercent: 0, duration: 0.14 },
                    0,
                );
            }

            if (mediaFrame) {
                timeline.fromTo(
                    mediaFrame,
                    { clipPath: 'inset(8% 8% 8% 8%)', scale: 0.98 },
                    { clipPath: 'inset(0% 0% 0% 0%)', scale: 1, duration: 0.2, ease: 'power2.out' },
                    0,
                );
            }

            if (statementOne) {
                timeline.fromTo(statementOne, { autoAlpha: 0, yPercent: 16 }, { autoAlpha: 1, yPercent: 0, duration: 0.16 }, 0.06);
            }

            if (progressFill) {
                timeline.to(progressFill, { scaleX: 0.33, duration: 0.3 }, 0.04);
            }

            if (mediaInner) {
                timeline.to(mediaInner, { scale: 1.045, yPercent: -1, duration: 0.34 }, 0.06);
            }

            if (statementOne) {
                timeline.to(statementOne, { autoAlpha: 0, yPercent: -14, duration: 0.12 }, 0.34);
            }

            if (statementTwo) {
                timeline.to(statementTwo, { autoAlpha: 1, yPercent: 0, duration: 0.14 }, 0.39);
            }

            if (progressFill) {
                timeline.to(progressFill, { scaleX: 0.66, duration: 0.26 }, 0.4);
            }

            if (mediaInner) {
                timeline.to(mediaInner, { scale: 1.085, yPercent: -4, duration: 0.3 }, 0.39);
            }

            if (statementTwo) {
                timeline.to(statementTwo, { autoAlpha: 0, yPercent: -14, duration: 0.12 }, 0.66);
            }

            if (statementThree) {
                timeline.to(statementThree, { autoAlpha: 1, yPercent: 0, duration: 0.14 }, 0.71);
            }

            if (supporting) {
                timeline.to(supporting, { autoAlpha: 1, yPercent: 0, duration: 0.14 }, 0.78);
            }

            if (progressFill) {
                timeline.to(progressFill, { scaleX: 1, duration: 0.28 }, 0.71);
            }

            if (mediaInner) {
                timeline.to(mediaInner, { scale: 1.11, yPercent: -6, duration: 0.29 }, 0.71);
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

                delete viewport.dataset.storytellingReady;
            };
        });

        return () => desktopMedia.revert();
    });
}
