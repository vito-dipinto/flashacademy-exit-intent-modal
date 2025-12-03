// src/faeim-modal-frontend/core/analytics.js

/**
 * Send an analytics event to the WP REST API.
 *
 * We deliberately do NOT rely on window.faeimAnalytics/wp_localize_script
 * to keep this robust. We just POST to /wp-json/faeim/v1/event, which is
 * public (permission_callback = __return_true).
 *
 * @param {"shown"|"converted"} eventType
 * @param {object} cfg
 */
export function sendAnalyticsEvent(eventType, cfg) {
	if (!cfg || !cfg.modalId) return;

	const payload = {
		modalId: cfg.modalId,
		eventType,
	};

	const url = '/wp-json/faeim/v1/event';

	try {
		const body = JSON.stringify(payload);

		// Prefer sendBeacon when available (non-blocking, survives unload).
		if (navigator.sendBeacon) {
			const blob = new Blob([body], { type: 'application/json' });
			navigator.sendBeacon(url, blob);
			return;
		}

		// Fallback: fetch
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body,
			keepalive: true,
		}).catch(() => {
			// Ignore network errors; analytics should never break UX.
		});
	} catch (e) {
		// eslint-disable-next-line no-console
		console.warn('[FAEIM] Analytics event failed:', e);
	}
}
