// src/faeim-modal-frontend/core/show.js

import { markShown } from './frequency';
import { sendAnalyticsEvent } from './analytics';

let hasShown = false;

export function showModalOnce(el, cfg) {
	if (hasShown) return;
	hasShown = true;

	if (!window.bootstrap || !window.bootstrap.Modal) {
		// eslint-disable-next-line no-console
		console.warn('[FAEIM] Bootstrap.Modal missing in showModalOnce()');
		return;
	}

	const modal = new window.bootstrap.Modal(el);

	// Record "seen" for frequency before opening.
	try {
		markShown(cfg);
	} catch (e) {
		// eslint-disable-next-line no-console
		console.warn('[FAEIM] markShown failed', e);
	}

	// 🔥 Analytics: impression.
	try {
		sendAnalyticsEvent('shown', cfg);
	} catch (e) {
		// eslint-disable-next-line no-console
		console.warn('[FAEIM] sendAnalyticsEvent(\"shown\") failed', e);
	}

	// Optional GTM event.
	if (window.dataLayer) {
		window.dataLayer.push({
			event: 'faeim_modal_shown',
			modalId: cfg.modalId,
		});
	}

	modal.show();
}
