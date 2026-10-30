/**
 * Door check-in console.
 *
 * A busy door cannot afford a page reload per guest, so the scan and verify
 * forms post in the background and the result is rendered in place. Every
 * failure mode still ends with a readable message, and the manual entry field
 * keeps working when the network drops.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const postJson = async (url, body) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        const error = new Error('The check-in service is unavailable.');
        error.status = response.status;
        throw error;
    }

    return response.json();
};

const getJson = async (url, params) => {
    const query = new URLSearchParams(params).toString();
    const response = await fetch(`${url}?${query}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const error = new Error('The check-in service is unavailable.');
        error.status = response.status;
        throw error;
    }

    return response.json();
};

export const setupCheckIn = () => {
    const root = document.querySelector('[data-checkin-root]');
    if (!root) return;

    const processUrl = root.dataset.checkinUrl;
    const verifyUrl = root.dataset.verifyUrl;

    const form = root.querySelector('[data-checkin-form]');
    const codeInput = root.querySelector('[data-ticket-token-input]');
    const result = root.querySelector('[data-checkin-result]');
    const panel = result?.querySelector('.ef-scan-result__panel');
    const stateEl = root.querySelector('[data-checkin-state]');
    const detailEl = root.querySelector('[data-checkin-detail]');
    const factsEl = root.querySelector('[data-checkin-facts]');
    const ticketEl = root.querySelector('[data-checkin-ticket]');
    const timeEl = root.querySelector('[data-checkin-time]');
    const statusEl = root.querySelector('[data-checkin-status]');

    const countEl = root.querySelector('[data-checkin-count]');
    const outstandingEl = root.querySelector('[data-checkin-outstanding]');
    const recentEl = root.querySelector('[data-checkin-recent]');
    const recentCountEl = root.querySelector('[data-checkin-recent-count]');
    const progress = root.querySelector('[data-checkin-progress]');
    const progressTrack = progress?.closest('[role="progressbar"]');
    const issuedEl = root.querySelector('.ef-checkin-stats__grid div:nth-child(3) dd');
    const rateEl = root.querySelector('.ef-checkin-stats__grid div:nth-child(4) dd');

    const verifyForm = root.querySelector('[data-checkin-verify-form]');
    const verifyInput = root.querySelector('[data-verify-input]');
    const verifyResult = root.querySelector('[data-verify-result]');
    const verifyPanel = verifyResult?.querySelector('.ef-scan-result__panel');
    const verifyStateEl = root.querySelector('[data-verify-state]');
    const verifyDetailEl = root.querySelector('[data-verify-detail]');

    const say = (message) => {
        if (statusEl) statusEl.textContent = message;
    };

    const paint = (container, panelEl, stateEl, detailEl, payload) => {
        if (!container || !panelEl || !stateEl) return;

        container.hidden = false;
        panelEl.dataset.tone = payload?.tone ?? 'neutral';
        stateEl.textContent = payload?.message ?? 'Ready to scan';

        if (detailEl) {
            detailEl.textContent = payload?.attendee
                ? `${payload.attendee}${payload.checked_in_at ? ` · ${payload.checked_in_at}` : ''}`
                : payload?.attendee === null
                  ? 'No matching ticket for this event.'
                  : '';
        }
    };

    const updateCounters = (payload) => {
        if (payload?.state !== 'checked_in' || !countEl) return;

        const issued = Number(issuedEl?.textContent ?? 0);
        const checkedIn = Number(countEl.textContent) + 1;
        const outstanding = Math.max(0, issued - checkedIn);
        const rate = issued > 0 ? Math.round((checkedIn / issued) * 1000) / 10 : 0;

        countEl.textContent = String(checkedIn);

        if (outstandingEl) outstandingEl.textContent = String(outstanding);
        if (rateEl) rateEl.textContent = `${rate}%`;

        if (progress) progress.style.setProperty('--ef-progress-value', `${rate}%`);

        if (progressTrack) {
            progressTrack.setAttribute('aria-valuenow', String(Math.floor(rate)));
        }

        if (recentEl && payload.attendee) {
            const item = document.createElement('li');
            item.className = 'ef-list__item';

            const body = document.createElement('div');
            const title = document.createElement('p');
            title.className = 'ef-list__title';
            title.textContent = payload.attendee;

            const meta = document.createElement('p');
            meta.className = 'ef-list__meta';
            meta.textContent = [payload.ticket_number, payload.checked_in_at].filter(Boolean).join(' · ');

            body.append(title, meta);
            item.append(body);

            const empty = recentEl.querySelector('.ef-meta');
            empty?.closest('li')?.remove();

            recentEl.prepend(item);

            while (recentEl.children.length > 12) {
                recentEl.lastElementChild?.remove();
            }

            if (recentCountEl) {
                recentCountEl.textContent = String(recentEl.children.length);
            }
        }
    };

    if (form && codeInput && result) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const code = codeInput.value.trim();
            if (!code) return;

            codeInput.disabled = true;
            say('Checking ticket…');

            try {
                const payload = await postJson(processUrl, { code });
                paint(result, panel, stateEl, detailEl, payload);
                updateCounters(payload);
                say('');
            } catch (error) {
                paint(result, panel, stateEl, detailEl, {
                    tone: 'danger',
                    message: 'Could not reach the door',
                    attendee: null,
                });
                say(error.message);
            } finally {
                codeInput.disabled = false;
                codeInput.value = '';
                codeInput.focus();
            }
        });
    }

    if (verifyForm && verifyInput && verifyResult) {
        verifyForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const code = verifyInput.value.trim();
            if (!code) return;

            verifyInput.disabled = true;

            try {
                const payload = await getJson(verifyUrl, { code });
                paint(verifyResult, verifyPanel, verifyStateEl, verifyDetailEl, payload);
            } catch (error) {
                paint(verifyResult, verifyPanel, verifyStateEl, verifyDetailEl, {
                    tone: 'danger',
                    message: 'Could not reach the door',
                    attendee: null,
                });
            } finally {
                verifyInput.disabled = false;
                verifyInput.focus();
            }
        });
    }

    // Keep the counter honest when the device wakes up from sleep at a door.
    const onVisible = () => {
        if (document.visibilityState === 'visible' && navigator.onLine) say('');
    };

    document.addEventListener('visibilitychange', onVisible);
    window.addEventListener('online', onVisible);

    if (!navigator.onLine) {
        say('You are offline. Scans will resume once the connection returns.');
    }

    return () => {
        document.removeEventListener('visibilitychange', onVisible);
        window.removeEventListener('online', onVisible);
    };
};
