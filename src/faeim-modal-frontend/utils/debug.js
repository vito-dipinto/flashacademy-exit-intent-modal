/**
 * Determine if debug mode is enabled.
 *
 * Debug mode can be turned on via:
 *   1) faeimConfig.debug = true (from PHP)
 *   2) URL param: ?faeim_debug=1
 */
export function isDebugEnabled(cfg = {}) {
	// Config flag takes priority
	if (typeof cfg.debug !== 'undefined') {
		return !!cfg.debug;
	}

	// URL query toggle
	try {
		const params = new URLSearchParams(window.location.search);
		return params.get('faeim_debug') === '1';
	} catch (e) {
		return false;
	}
}

/**
 * Conditional debug logger.
 *
 * Usage:
 *   logDebug(cfg, 'Hello', someObject);
 */
export function logDebug(cfg, ...args) {
	if (!isDebugEnabled(cfg)) {
		return;
	}

	// eslint-disable-next-line no-console
	console.log('[FAEIM debug]', ...args);
}
