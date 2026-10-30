/** Off-canvas admin navigation with an accessible open/close contract. */
export const setupAdminShell = () => {
    const toggle = document.querySelector('[data-admin-menu-toggle]');
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const scrim = document.querySelector('[data-admin-scrim]');

    if (!toggle || !sidebar) return;

    const setOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        if (scrim) scrim.hidden = !open;
        document.body.classList.toggle('ef-admin-nav-open', open);
    };

    const onToggle = () => setOpen(!sidebar.classList.contains('is-open'));
    const onScrim = () => setOpen(false);
    const onKeydown = (event) => {
        if (event.key === 'Escape') setOpen(false);
    };

    toggle.addEventListener('click', onToggle);
    scrim?.addEventListener('click', onScrim);
    document.addEventListener('keydown', onKeydown);

    return () => {
        toggle.removeEventListener('click', onToggle);
        scrim?.removeEventListener('click', onScrim);
        document.removeEventListener('keydown', onKeydown);
        setOpen(false);
    };
};
