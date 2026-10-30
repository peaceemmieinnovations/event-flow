/**
 * Small, progressive-enhancement helpers for the workspace forms.
 *
 * Everything here is additive: the pages still work with JavaScript disabled,
 * because the server owns pricing, capacity and validation.
 */

/** Filter selects submit their form as soon as a value is chosen. */
export const setupAutoSubmit = () => {
    const cleanups = [];

    document.querySelectorAll('[data-auto-submit]').forEach((control) => {
        const form = control.form || control.closest('form');
        if (!form) return;

        const submit = () => form.requestSubmit();
        control.addEventListener('change', submit);
        cleanups.push(() => control.removeEventListener('change', submit));
    });

    return () => cleanups.forEach((cleanup) => cleanup());
};

/**
 * Paid events ask for an amount and currency; free events do not. Hidden fields
 * are disabled so a stale amount is never posted for a free event.
 */
export const setupPricingForm = () => {
    const cleanups = [];

    document.querySelectorAll('[data-pricing-form]').forEach((form) => {
        const type = form.querySelector('[data-pricing-type]');
        if (!type) return;

        const fields = Array.from(form.querySelectorAll('[data-pricing-field]'));

        const apply = () => {
            const paid = type.value === 'paid';

            fields.forEach((field) => {
                field.hidden = !paid;

                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !paid;
                });
            });
        };

        type.addEventListener('change', apply);
        apply();
        cleanups.push(() => type.removeEventListener('change', apply));
    });

    return () => cleanups.forEach((cleanup) => cleanup());
};

/** Keep the slug mirroring the title until an editor types their own. */
export const setupSlugField = () => {
    const cleanups = [];

    document.querySelectorAll('[data-slug-source]').forEach((slug) => {
        const source = document.querySelector(slug.dataset.slugSource);
        if (!source) return;

        let touched = slug.value.trim() !== '';

        const slugify = (value) =>
            value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 120);

        const mirror = () => {
            if (!touched) slug.value = slugify(source.value);
        };

        const markTouched = () => {
            touched = true;
        };

        const clearTouched = () => {
            touched = false;
            mirror();
        };

        slug.addEventListener('input', markTouched);
        slug.addEventListener('blur', clearTouched);
        source.addEventListener('input', mirror);

        cleanups.push(() => {
            slug.removeEventListener('input', markTouched);
            slug.removeEventListener('blur', clearTouched);
            source.removeEventListener('input', mirror);
        });
    });

    return () => cleanups.forEach((cleanup) => cleanup());
};

/** Preview a picked cover image locally before the upload finishes. */
export const setupImagePreview = () => {
    const cleanups = [];

    document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
        const target = document.querySelector(input.dataset.imagePreviewTarget || '[data-image-preview]');
        if (!target) return;

        let objectUrl = null;

        const render = () => {
            const file = input.files?.[0];

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            if (!file) {
                target.replaceChildren();
                target.hidden = true;
                return;
            }

            objectUrl = URL.createObjectURL(file);

            const preview = document.createElement('img');
            preview.className = 'ef-media-preview';
            preview.src = objectUrl;
            preview.alt = 'Selected cover preview';
            preview.width = 320;
            preview.height = 180;

            target.replaceChildren(preview);
            target.hidden = false;
        };

        input.addEventListener('change', render);
        cleanups.push(() => {
            input.removeEventListener('change', render);
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        });
    });

    return () => cleanups.forEach((cleanup) => cleanup());
};
