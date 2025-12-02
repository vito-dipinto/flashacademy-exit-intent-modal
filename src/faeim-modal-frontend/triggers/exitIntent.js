export function attachExitIntentTrigger(el, cfg, show) {
	if (!cfg.exitIntent) return;
	if (window.innerWidth < 768) return; // desktop only

	function handler(e) {
		if (e.clientY > 0) return;
		document.removeEventListener('mouseleave', handler);
		show(el, cfg);
	}

	document.addEventListener('mouseleave', handler);
}
