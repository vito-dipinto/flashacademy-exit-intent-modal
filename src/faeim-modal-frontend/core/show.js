// src/faeim-modal-frontend/core/show.js

import { markShown } from './frequency';

let hasShown = false;

export function showModalOnce(el, cfg) {
	if (hasShown) return;
	hasShown = true;

	if (!window.bootstrap || !window.bootstrap.Modal) {
		console.warn('[FAEIM] Bootstrap.Modal missing in showModalOnce()');
		return;
	}

	const modal = new window.bootstrap.Modal(el);

	// Record "shown" BEFORE modal opens
	// so even if user closes instantly, it counts.
	try {
		markShown(cfg);
	} catch (e) {
		console.warn('[FAEIM] markShown failed', e);
	}

	modal.show();
}
