const focusableSelector = [
    'a[href]:not([aria-disabled="true"])',
    'button:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const desktopNavigationQuery = '(min-width: 64rem)';

export function initializeNavigation({ root, gsap, isReducedMotion }) {
    const header = root.querySelector('[data-site-header]');
    const menuToggle = root.querySelector('[data-menu-toggle]');
    const mobileMenu = root.querySelector('[data-mobile-menu]');
    const menuPanel = root.querySelector('[data-mobile-menu-panel]');
    const menuClose = root.querySelector('[data-mobile-menu-close]');

    if (!header || !menuToggle || !mobileMenu || !menuPanel) {
        return () => {};
    }

    const mobileMenuLinks = Array.from(root.querySelectorAll('[data-mobile-menu-link]'));
    const desktopMedia = window.matchMedia(desktopNavigationQuery);
    let bodyStyles;
    let menuTimeline;
    let scrollPosition = 0;
    let scrollFrame;
    let lastFocusedElement;

    const isMenuOpen = () => !mobileMenu.hidden;

    const updateHeaderState = () => {
        scrollFrame = undefined;
        header.classList.toggle('is-scrolled', window.scrollY > 24);
    };

    const scheduleHeaderState = () => {
        if (scrollFrame) {
            return;
        }

        scrollFrame = window.requestAnimationFrame(updateHeaderState);
    };

    const lockBodyScroll = () => {
        if (bodyStyles !== undefined) {
            return;
        }

        scrollPosition = window.scrollY;
        bodyStyles = document.body.getAttribute('style');
        document.body.classList.add('ef-menu-is-open');
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';
    };

    const unlockBodyScroll = () => {
        if (bodyStyles === undefined) {
            return;
        }

        document.body.classList.remove('ef-menu-is-open');

        if (bodyStyles === null) {
            document.body.removeAttribute('style');
        } else {
            document.body.setAttribute('style', bodyStyles);
        }

        bodyStyles = undefined;
        window.scrollTo(0, scrollPosition);
    };

    const getFocusableElements = () => Array.from(mobileMenu.querySelectorAll(focusableSelector));

    const focusMenu = () => {
        const firstMenuLink = mobileMenu.querySelector('[data-mobile-menu-link]');
        const firstFocusableElement = getFocusableElements()[0];

        (firstMenuLink ?? firstFocusableElement)?.focus();
    };

    const finishOpeningMenu = () => {
        mobileMenu.classList.add('is-open');
        menuToggle.setAttribute('aria-label', 'Close navigation menu');
        focusMenu();
    };

    const finishClosingMenu = (restoreFocus) => {
        mobileMenu.classList.remove('is-open');
        mobileMenu.hidden = true;
        mobileMenu.inert = true;
        mobileMenu.setAttribute('aria-hidden', 'true');
        header.classList.remove('is-menu-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open navigation menu');
        unlockBodyScroll();

        if (restoreFocus) {
            (lastFocusedElement instanceof HTMLElement ? lastFocusedElement : menuToggle).focus();
        }
    };

    const openMenu = () => {
        if (isMenuOpen()) {
            return;
        }

        lastFocusedElement = document.activeElement;
        mobileMenu.hidden = false;
        mobileMenu.inert = false;
        mobileMenu.setAttribute('aria-hidden', 'false');
        header.classList.add('is-menu-open');
        menuToggle.setAttribute('aria-expanded', 'true');
        lockBodyScroll();

        if (isReducedMotion()) {
            finishOpeningMenu();
            return;
        }

        menuTimeline?.kill();
        menuTimeline = gsap.timeline({
            defaults: { ease: 'power3.out' },
            onComplete: finishOpeningMenu,
        });

        menuTimeline
            .fromTo(mobileMenu, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.2 })
            .fromTo(menuPanel, { autoAlpha: 0, yPercent: -3 }, { autoAlpha: 1, yPercent: 0, duration: 0.36 }, '<')
            .fromTo(mobileMenuLinks, { autoAlpha: 0, yPercent: 18 }, { autoAlpha: 1, yPercent: 0, duration: 0.36, stagger: 0.055 }, '-=0.16');
    };

    const closeMenu = ({ restoreFocus = true } = {}) => {
        if (!isMenuOpen()) {
            return;
        }

        menuTimeline?.kill();

        if (isReducedMotion()) {
            finishClosingMenu(restoreFocus);
            return;
        }

        menuTimeline = gsap.timeline({
            defaults: { ease: 'power2.in' },
            onComplete: () => finishClosingMenu(restoreFocus),
        });

        menuTimeline
            .to(mobileMenuLinks, { autoAlpha: 0, yPercent: -10, duration: 0.14, stagger: 0.025 })
            .to(menuPanel, { autoAlpha: 0, yPercent: -2, duration: 0.2 }, '<')
            .to(mobileMenu, { autoAlpha: 0, duration: 0.16 }, '<0.05');
    };

    const handleToggle = () => {
        if (isMenuOpen()) {
            closeMenu();
            return;
        }

        openMenu();
    };

    const handleKeydown = (event) => {
        if (!isMenuOpen()) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = getFocusableElements();
        const firstElement = focusableElements[0];
        const lastElement = focusableElements.at(-1);

        if (!firstElement || !lastElement) {
            event.preventDefault();
            return;
        }

        if (!mobileMenu.contains(document.activeElement)) {
            event.preventDefault();
            firstElement.focus();
            return;
        }

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        }

        if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    };

    const handleBackdropClick = (event) => {
        if (event.target === mobileMenu) {
            closeMenu();
        }
    };

    const handleDesktopChange = (event) => {
        if (event.matches) {
            closeMenu({ restoreFocus: false });
        }
    };

    const handleMenuClose = () => closeMenu();
    const handleMenuLinkClick = () => closeMenu({ restoreFocus: false });

    menuToggle.addEventListener('click', handleToggle);
    menuClose?.addEventListener('click', handleMenuClose);
    mobileMenu.addEventListener('click', handleBackdropClick);
    mobileMenuLinks.forEach((link) => link.addEventListener('click', handleMenuLinkClick));
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('scroll', scheduleHeaderState, { passive: true });
    desktopMedia.addEventListener('change', handleDesktopChange);
    updateHeaderState();

    return () => {
        menuTimeline?.kill();
        finishClosingMenu(false);
        menuToggle.removeEventListener('click', handleToggle);
        menuClose?.removeEventListener('click', handleMenuClose);
        mobileMenu.removeEventListener('click', handleBackdropClick);
        mobileMenuLinks.forEach((link) => link.removeEventListener('click', handleMenuLinkClick));
        document.removeEventListener('keydown', handleKeydown);
        window.removeEventListener('scroll', scheduleHeaderState);
        desktopMedia.removeEventListener('change', handleDesktopChange);

        if (scrollFrame) {
            window.cancelAnimationFrame(scrollFrame);
        }
    };
}
