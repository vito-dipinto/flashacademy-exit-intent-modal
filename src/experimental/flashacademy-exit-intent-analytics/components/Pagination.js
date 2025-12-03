// src/flashacademy-exit-intent-analytics/components/AnalyticsTable.js

export function AnalyticsTable({ rows }) {
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
