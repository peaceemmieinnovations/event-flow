import { initializeMotion } from './motion';
import { setupCategoriesMotion } from './motion/categories';
import { setupDiscoveryMotion } from './motion/discovery';
import { setupEventDetailMotion } from './motion/event-detail';
import { setupEventsMotion } from './motion/events';
import { setupFeaturedEventsMotion } from './motion/featured-events';
import { setupHomeMotion } from './motion/home';
import { initializeNavigation } from './motion/navigation';
import { setupStorytellingMotion } from './motion/storytelling';

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

const bootMotion = () => {
    const destroyMotion = initializeMotion({
        setup: setupPageMotion,
        setupInteractions: initializeNavigation,
    });

    if (import.meta.hot) {
        import.meta.hot.dispose(destroyMotion);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootMotion, { once: true });
} else {
    bootMotion();
}
