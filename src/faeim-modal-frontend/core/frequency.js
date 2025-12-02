// src/faeim-modal-frontend/core/frequency.js

const SEEN_PREFIX = 'faeim_seen_';
const CONVERTED_PREFIX = 'faeim_converted_';

function getStorage() {
	try {
		if (typeof window === 'undefined' || !window.localStorage) {
			return null;
		}
		return window.localStorage;
	} catch (e) {
		return null;
	}
}

function getSeenKey(cfg) {
	return SEEN_PREFIX + String(cfg.modalId);
}

function getConvertedKey(cfg) {
	return CONVERTED_PREFIX + String(cfg.modalId);
}

/**
 * Compute detailed info about frequency capping + conversion.
 *
 * Returns an object like:
 * {
 *   enabled: true/false,      // is frequency logic enabled (days > 0 and storage available)
 *   allowed: true/false,      // can the modal show?
 *   converted: true/false,    // has the user already converted?
 *   days: 7,
 *   lastSeen: '2025-12-01T12:34:56.000Z' | null,
 *   diffDays: 1.23,
 *   remainingDays: 5.76,
 *   reason: '...',
 * }
 */
export function getFrequencyDebugInfo(cfg = {}) {
	const storage = getStorage();

	const daysRaw = parseInt(cfg.frequencyDays, 10);
	const days =
		!Number.isNaN(daysRaw) && daysRaw > 0 ? daysRaw : 0;

	// If storage is unavailable, we cannot persist anything.
	if (!storage) {
		return {
			enabled: false,
			allowed: true,
			converted: false,
			days,
			lastSeen: null,
			diffDays: 0,
			remainingDays: 0,
			reason: 'localStorage not available; frequency & conversion disabled',
		};
	}

	// 1️⃣ Check conversion first: if converted, modal is always blocked.
	const convertedKey = getConvertedKey(cfg);
	const convertedRaw = storage.getItem(convertedKey);

	if (convertedRaw) {
		const convertedTimestamp = parseInt(convertedRaw, 10);
		return {
			enabled: true,
			allowed: false,
			converted: true,
			days,
			lastSeen: Number.isNaN(convertedTimestamp)
				? null
				: new Date(convertedTimestamp).toISOString(),
			diffDays: 0,
			remainingDays: 0,
			reason: 'user already converted; modal blocked permanently',
		};
	}

	// 2️⃣ If no frequency is configured, but not converted → always allowed.
	if (days === 0) {
		return {
			enabled: false,
			allowed: true,
			converted: false,
			days,
			lastSeen: null,
			diffDays: 0,
			remainingDays: 0,
			reason: 'frequencyDays is 0; no frequency capping (but no conversion recorded)',
		};
	}

	// 3️⃣ Frequency window based on "seen" timestamp.
	const seenKey = getSeenKey(cfg);
	const raw = storage.getItem(seenKey);

	if (!raw) {
		return {
			enabled: true,
			allowed: true,
			converted: false,
			days,
			lastSeen: null,
			diffDays: 0,
			remainingDays: 0,
			reason: 'no previous timestamp; modal allowed',
		};
	}

	const lastTimestamp = parseInt(raw, 10);
	if (Number.isNaN(lastTimestamp)) {
		return {
			enabled: true,
			allowed: true,
			converted: false,
			days,
			lastSeen: null,
			diffDays: 0,
			remainingDays: 0,
			reason: 'invalid stored timestamp; ignoring and allowing modal',
		};
	}

	const now = Date.now();
	const diffMs = now - lastTimestamp;
	const diffDays = diffMs / (1000 * 60 * 60 * 24);
	const allowed = diffDays >= days;
	const remainingDays = allowed ? 0 : days - diffDays;

	return {
		enabled: true,
		allowed,
		converted: false,
		days,
		lastSeen: new Date(lastTimestamp).toISOString(),
		diffDays,
		remainingDays,
		reason: allowed
			? 'frequency window passed; modal allowed'
			: 'within frequency window; modal blocked',
	};
}

/**
 * Simple boolean check used by the main flow.
 */
export function isFrequencyAllowed(cfg) {
	const info = getFrequencyDebugInfo(cfg);
	return info.allowed;
}

/**
 * Record that the modal has been shown now.
 */
export function markShown(cfg) {
	const storage = getStorage();
	if (!storage) {
		return;
	}

	const daysRaw = parseInt(cfg.frequencyDays, 10);
	const days =
		!Number.isNaN(daysRaw) && daysRaw > 0 ? daysRaw : 0;

	// If no frequency is set, no need to store anything for "seen".
	if (days === 0) {
		return;
	}

	const key = getSeenKey(cfg);
	storage.setItem(key, String(Date.now()));
}

/**
 * Record that the user has converted for this modal.
 * After this, getFrequencyDebugInfo() will always return allowed: false.
 */
export function markConverted(cfg) {
	const storage = getStorage();
	if (!storage) {
		return;
	}

	const key = getConvertedKey(cfg);
	storage.setItem(key, String(Date.now()));
}
