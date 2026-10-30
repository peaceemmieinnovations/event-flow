import { initializeMotion } from './motion';
import { setupCategoriesMotion } from './motion/categories';
import { setupDiscoveryMotion } from './motion/discovery';
import { setupEventDetailMotion } from './motion/event-detail';
import { setupEventsMotion } from './motion/events';
import { setupFeaturedEventsMotion } from './motion/featured-events';
import { setupHomeMotion } from './motion/home';
import { initializeNavigation } from './motion/navigation';
import { setupStorytellingMotion } from './motion/storytelling';
import { setupAdminShell } from './admin-shell';
import { setupCheckIn } from './check-in';
import { setupAutoSubmit, setupImagePreview, setupPricingForm, setupSlugField } from './forms';
import { initializeTicketQr } from './ticket-qr';

const setupPageMotion = (motion) => {
    const cleanups = [
        setupHomeMotion(motion),
        setupFeaturedEventsMotion(motion),
        setupCategoriesMotion(motion),
        setupStorytellingMotion(motion),
        setupDiscoveryMotion(motion),
        setupEventsMotion(motion),
        setupEventDetailMotion(motion),
    ].filter(Boolean);

    return () => {
        cleanups.reverse().forEach((cleanup) => cleanup());
    };
};

const setupWorkspace = () => {
    const cleanups = [
        setupAdminShell(),
        setupAutoSubmit(),
        setupPricingForm(),
        setupSlugField(),
        setupImagePreview(),
        setupCheckIn(),
    ].filter(Boolean);

    return () => cleanups.forEach((cleanup) => cleanup());
};

const boot = () => {
    const destroyMotion = initializeMotion({
        setup: setupPageMotion,
        setupInteractions: initializeNavigation,
    });

    const destroyWorkspace = setupWorkspace();
    const destroyTicketQr = initializeTicketQr();

    if (import.meta.hot) {
        import.meta.hot.dispose(() => {
            destroyMotion();
            destroyWorkspace();
            destroyTicketQr();
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
