import QRCode from 'qrcode';
import { Html5Qrcode } from 'html5-qrcode';

/**
 * Ticket QR rendering and the door scanner.
 *
 * The camera is only started on a secure origin and only once the scanner
 * element is on screen; the reader is always stopped on teardown so a single
 * page never leaves the camera running.
 */
export const initializeTicketQr = () => {
    const cleanups = [];

    const canvas = document.querySelector('[data-ticket-qr]');

    if (canvas?.dataset.token) {
        QRCode.toCanvas(canvas, canvas.dataset.token, { width: 240, margin: 1 }).catch(() => {
            // A missing QR is not fatal: the printed ticket number below it is a
            // complete fallback for the door.
        });
    }

    document.querySelectorAll('[data-print-button]').forEach((button) => {
        const onClick = () => window.print();
        button.addEventListener('click', onClick);
        cleanups.push(() => button.removeEventListener('click', onClick));
    });

    const scanner = document.querySelector('[data-ticket-scanner]');
    const input = document.querySelector('[data-ticket-token-input]');

    if (scanner && input) {
        const reader = new Html5Qrcode(scanner.id);
        let started = false;

        const start = async () => {
            if (started || !window.isSecureContext) return;

            try {
                await reader.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 220 },
                    async (token) => {
                        if (!token) return;

                        input.value = token;

                        try {
                            await reader.stop();
                        } catch {
                            // Already stopped.
                        }

                        started = false;
                        input.form?.requestSubmit();
                    },
                    () => {
                        // No frame decoded: keep scanning.
                    },
                );

                started = true;
            } catch {
                // No camera, or permission denied. Manual entry still works.
            }
        };

        start();

        cleanups.push(async () => {
            if (!started) return;

            try {
                await reader.stop();
                await reader.clear();
            } catch {
                // Nothing to clean up.
            }
        });
    }

    return () => cleanups.forEach((cleanup) => cleanup());
};
