// src/flashacademy-exit-intent-analytics/analytics.js

import './style.scss';
import { createRoot } from 'react-dom/client';
import { useState, useEffect } from '@wordpress/element';

/**
 * Search bar (title / ID).
 */
function SearchBar({ value, onChange, total }) {
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

/**
 * Pagination controls.
 */
function Pagination({ currentPage, totalPages, onPageChange }) {
	if (!totalPages || totalPages <= 1) return null;

	const canPrev = currentPage > 1;
	const canNext = currentPage < totalPages;

	return (
		<div className="faeim-pagination">
			<button
				type="button"
				disabled={!canPrev}
				onClick={() => canPrev && onPageChange(currentPage - 1)}
				className="button"
			>
				« Prev
			</button>
			<span>
				Page {currentPage} of {totalPages}
			</span>
			<button
				type="button"
				disabled={!canNext}
				onClick={() => canNext && onPageChange(currentPage + 1)}
				className="button"
			>
				Next »
			</button>
		</div>
	);
}

/**
 * Table with edit links.
 */
function AnalyticsTable({ rows }) {
	const base =
		window.faeimAdminAnalytics?.editPostUrlBase || '/wp-admin/post.php';

	const editUrl = (id) => `${base}?post=${id}&action=edit`;

	return (
		<table>
			<thead>
				<tr>
					<th>Modal</th>
					<th>Impressions</th>
					<th>Conversions</th>
					<th>Conv. rate</th>
					<th>Last shown</th>
					<th>Last converted</th>
				</tr>
			</thead>
			<tbody>
				{rows.length === 0 && (
					<tr>
						<td colSpan={6}>No modals match your search.</td>
					</tr>
				)}

				{rows.map((row) => (
					<tr key={row.id}>
						<td>
							<a href={editUrl(row.id)}>
								{row.title} (#{row.id})
							</a>
						</td>
						<td>{row.impressions}</td>
						<td>{row.conversions}</td>
						<td>{row.conversionRate}%</td>
						<td>{row.lastShown || '—'}</td>
						<td>{row.lastConverted || '—'}</td>
					</tr>
				))}
			</tbody>
		</table>
	);
}

/**
 * Main Analytics app: fetch + search + pagination.
 */
function AnalyticsApp() {
	const [stats, setStats] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [search, setSearch] = useState('');
	const [currentPage, setCurrentPage] = useState(1);
	const perPage = 10;

	useEffect(() => {
		async function loadStats() {
			try {
				const res = await fetch(window.faeimAdminAnalytics.restUrl, {
					headers: {
						'X-WP-Nonce': window.faeimAdminAnalytics.nonce,
					},
				});

				if (!res.ok) {
					throw new Error(`HTTP ${res.status}`);
				}

				const data = await res.json();
				setStats(data);
			} catch (err) {
				// eslint-disable-next-line no-console
				console.error('[FAEIM] Failed to load analytics', err);
				setError(err);
			} finally {
				setLoading(false);
			}
		}

		loadStats();
	}, []);

	// Filter by search (title or ID)
	const needle = search.trim().toLowerCase();
	const filtered = needle
		? stats.filter((row) => {
				const title = (row.title || '').toLowerCase();
				const id = String(row.id || '');
				return title.includes(needle) || id.includes(needle);
		  })
		: stats;

	// Pagination
	const totalItems = filtered.length;
	const totalPages = totalItems > 0 ? Math.ceil(totalItems / perPage) : 1;
	const safePage = Math.min(Math.max(currentPage, 1), totalPages);
	const start = (safePage - 1) * perPage;
	const pageItems = filtered.slice(start, start + perPage);

	// Reset page when search changes
	useEffect(() => {
		setCurrentPage(1);
	}, [search]);

	return (
		<div>
			{loading && <p>Loading analytics…</p>}
			{error && (
				<p style={{ color: 'red' }}>
					Error loading analytics: {error.message}
				</p>
			)}

			{!loading && !error && (
				<>
					<SearchBar value={search} onChange={setSearch} total={totalItems} />
					<AnalyticsTable rows={pageItems} />
					<Pagination
						currentPage={safePage}
						totalPages={totalPages}
						onPageChange={setCurrentPage}
					/>
				</>
			)}
		</div>
	);
}

// Mount the app.
const container = document.getElementById('flashacademy-exit-modal-analytics');
if (container) {
	const root = createRoot(container);
	root.render(<AnalyticsApp />);
}
