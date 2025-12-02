export function attachTimeTrigger(el, cfg, show) {
	const delay = cfg.minTime ? cfg.minTime * 1000 : 2000;

	setTimeout(() => {
		show(el, cfg);
	}, delay);
}
