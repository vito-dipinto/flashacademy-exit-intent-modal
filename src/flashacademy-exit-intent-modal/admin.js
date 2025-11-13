/**
 * FlashAcademy Exit-Intent Modal Admin App
 * -----------------------------------------
 * This script mounts the React admin UI for the plugin's settings page.
 */

import { createRoot } from 'react-dom/client';
import { useState, useEffect } from '@wordpress/element';

/**
 * Simple test React component
 * Replace this with your actual Settings UI later.
 */
function App() {
	const [count, setCount] = useState(0);

	useEffect(() => {
		console.log('✅ Flash Modals admin React app mounted');
	}, []);

	return (
		<div style={{ padding: '20px', fontFamily: 'sans-serif' }}>
			<h2>Flash Modals — Admin Panel</h2>
			<p>This is your custom React admin interface.</p>

			<button
				onClick={() => setCount((c) => c + 1)}
				style={{
					padding: '8px 12px',
					background: '#2271b1',
					color: '#fff',
					border: 'none',
					borderRadius: '4px',
					cursor: 'pointer',
				}}
			>
				Clicked {count} times
			</button>
		</div>
	);
}

/**
 * Mount React app
 */
const el = document.getElementById('flashacademy-exit-modal-admin');

if (el) {
	console.log('✅ Mounting Flash Modals admin app...');
	const root = createRoot(el);
	root.render(<App />);
} else {
	console.error('❌ Mount point not found: #flashacademy-exit-modal-admin');
}
