// src/faeim-modal-frontend/index.js

import './style.scss';

// Core helpers
import { getModalElement } from './core/dom';
import { showModalOnce } from './core/show';
import { getFrequencyDebugInfo, markConverted } from './core/frequency';

// Triggers
import { attachTimeTrigger } from './triggers/time';
import { attachScrollTrigger } from './triggers/scroll';
import { attachExitIntentTrigger } from './triggers/exitIntent';

// Debug
import { logDebug } from './utils/debug';

document.addEventListener('DOMContentLoaded', () => {
	const cfg = window.faeimConfig || {};

	logDebug(cfg, 'faeimConfig loaded', cfg);

	// 1. Basic sanity checks
	if (!cfg.modalId) {
		logDebug(cfg, 'No modalId in cfg, aborting');
		return;
	}

	const el = getModalElement(cfg.modalId);
	if (!el) {
		logDebug(cfg, 'Modal element not found for id', cfg.modalId);
		return;
	}

	// 2. Require Bootstrap.Modal
	if (!window.bootstrap || !window.bootstrap.Modal) {
		// eslint-disable-next-line no-console
		console.warn(
			'[FAEIM] Bootstrap.Modal not found. Make sure your theme exposes window.bootstrap.'
		);
		logDebug(cfg, 'Bootstrap.Modal missing, aborting');
		return;
	}

	// 3. Frequency control (localStorage) WITH DEBUG INFO
	const freqInfo = getFrequencyDebugInfo(cfg);
	logDebug(cfg, 'Frequency debug info', freqInfo);

	if (!freqInfo.allowed) {
		logDebug(
			cfg,
			'Frequency check blocked modal display',
			typeof freqInfo.remainingDays === 'number'
				? `Remaining days: ${freqInfo.remainingDays.toFixed(2)}`
				: '',
			freqInfo
		);
		return;
	} else {
		logDebug(cfg, 'Frequency check passed; modal allowed to show', freqInfo);
	}

	// 4. Trigger strategy
	const exitIntentEnabled = !!cfg.exitIntent;

	const minTimeRaw = parseInt(cfg.minTime, 10);
	const timeEnabled =
		!Number.isNaN(minTimeRaw) && minTimeRaw > 0;

	const minScrollRaw = parseInt(cfg.minScroll, 10);
	const scrollEnabled =
		!Number.isNaN(minScrollRaw) && minScrollRaw > 0;

	const isFinePointer =
		window.matchMedia &&
		window.matchMedia('(pointer:fine)').matches;

	logDebug(cfg, 'Trigger strategy', {
		exitIntentEnabled,
		timeEnabled,
		scrollEnabled,
		isFinePointer,
	});

	if (!exitIntentEnabled && !timeEnabled && !scrollEnabled) {
		logDebug(
			cfg,
			'No triggers configured (exit intent off, time=0, scroll=0); modal will never show automatically.'
		);
		return;
	}

	if (exitIntentEnabled && isFinePointer) {
		// DESKTOP + EXIT INTENT ON → ONLY exit intent
		logDebug(
			cfg,
			'Exit-intent enabled on desktop: attaching ONLY exit-intent trigger'
		);
		attachExitIntentTrigger(el, cfg, showModalOnce);
	} else {
		// MOBILE / TABLET OR exit intent off → time/scroll
		logDebug(
			cfg,
			'Using time/scroll triggers (either exit intent is off, or device is touch)'
		);

		if (timeEnabled) {
			attachTimeTrigger(el, cfg, showModalOnce);
		} else {
			logDebug(cfg, 'Time trigger disabled (minTime = 0)');
		}

		if (scrollEnabled) {
			attachScrollTrigger(el, cfg, showModalOnce);
		} else {
			logDebug(cfg, 'Scroll trigger disabled (minScroll = 0)');
		}
	}

	/**
	 * 5. Conversion tracking (Gravity Forms)
	 *
	 * We mark the modal as "converted" when Gravity Forms fires
	 * its confirmation event for the configured form.
	 */

	let hasMarkedConverted = false;

	function handleConversion(source, formId) {
		// If a specific GF ID is configured, only convert for that form
		if (cfg.gravityFormId) {
			if (!formId || Number(formId) !== Number(cfg.gravityFormId)) {
				logDebug(cfg, `${source}: Form ID does not match; conversion ignored`, {
					modalId: cfg.modalId,
					formId,
					configuredFormId: cfg.gravityFormId,
				});
				return;
			}
		}

		if (hasMarkedConverted) {
			logDebug(cfg, `${source}: conversion already recorded, skipping`, {
				modalId: cfg.modalId,
				formId,
			});
			return;
		}

		hasMarkedConverted = true;

		markConverted(cfg);

		logDebug(cfg, `${source}: Modal marked as converted for this user/browser`, {
			modalId: cfg.modalId,
			formId,
		});

		// Optional: dataLayer event
		if (window.dataLayer) {
			window.dataLayer.push({
				event: 'faeim_modal_converted',
				modalId: cfg.modalId,
				formId: formId || null,
			});
		}
	}

	// 5a. DOM CustomEvent listener (if someone dispatches it manually)
	document.addEventListener('gform_confirmation_loaded', (event) => {
		const formId =
			event?.detail?.formId ??
			event?.formId ??
			null;

		logDebug(cfg, 'gform_confirmation_loaded (DOM) received', {
			modalId: cfg.modalId,
			formId,
			configuredFormId: cfg.gravityFormId,
		});

		handleConversion('DOM', formId);
	});

	// 5b. jQuery fallback – this is what Gravity Forms actually triggers
	if (window.jQuery && window.jQuery(document) && window.jQuery(document).on) {
		window.jQuery(document).on(
			'gform_confirmation_loaded',
			(event, formId) => {
				logDebug(cfg, 'gform_confirmation_loaded (jQuery) received', {
					modalId: cfg.modalId,
					formId,
					configuredFormId: cfg.gravityFormId,
				});

				handleConversion('jQuery', formId);
			}
		);
	}
});
