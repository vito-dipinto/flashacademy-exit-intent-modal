// src/flashacademy-exit-intent-analytics/components/SearchBar.js

export function SearchBar({ value, onChange, total }) {
	return (
		<div className="faeim-searchbar">
			<input
				type="search"
				placeholder="Search modals by title or ID…"
				value={value}
				onChange={(e) => onChange(e.target.value)}
			/>
			<span className="faeim-count">
				{total} modal{total === 1 ? '' : 's'}
			</span>
		</div>
	);
}
