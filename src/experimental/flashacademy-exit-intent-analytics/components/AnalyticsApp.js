// src/flashacademy-exit-intent-analytics/components/AnalyticsApp.js

import { useState, useEffect } from '@wordpress/element';
import { SearchBar } from './SearchBar';
import { Pagination } from './Pagination';
import { AnalyticsTable } from './AnalyticsTable';

export function AnalyticsApp() {
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
				console.error('[FAEIM] Failed to load analytics', err);
				setError(err);
			} finally {
				setLoading(false);
			}
		}

		loadStats();
	}, []);

	// Filter by search (title or ID).
	const needle = search.trim().toLowerCase();
	const filtered = needle
		? stats.filter((row) => {
				const title = (row.title || '').toLowerCase();
				const id = String(row.id || '');
				return title.includes(needle) || id.includes(needle);
		  })
		: stats;

	// Pagination.
	const totalItems = filtered.length;
	const totalPages = totalItems > 0 ? Math.ceil(totalItems / perPage) : 1;
	const safePage = Math.min(Math.max(currentPage, 1), totalPages);
	const start = (safePage - 1) * perPage;
	const pageItems = filtered.slice(start, start + perPage);

	// Reset to page 1 when search changes.
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
