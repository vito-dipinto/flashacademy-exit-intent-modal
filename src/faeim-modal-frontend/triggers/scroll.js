export function attachScrollTrigger(el, cfg, show) {
	if (!cfg.minScroll || cfg.minScroll <= 0) return;

	const target = Math.min(cfg.minScroll, 100);

	function onScroll() {
		const pos = window.scrollY;
		const height = document.documentElement.scrollHeight - window.innerHeight;
		if (height <= 0) return;

		const percent = (pos / height) * 100;
		if (percent >= target) {
			window.removeEventListener('scroll', onScroll);
			show(el, cfg);
		}
	}

	window.addEventListener('scroll', onScroll, { passive: true });
}
