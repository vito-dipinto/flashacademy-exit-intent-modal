<?php
namespace FlashAcademy\FlashModals\Admin;

use function FlashAcademy\FlashModals\asset_path;
use function FlashAcademy\FlashModals\asset_url;

defined( 'ABSPATH' ) || exit;

function setup(): void {
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Add "Settings" submenu under the Flash Modals CPT menu.
 */
function register_menu(): void {
	// Parent slug is the CPT screen.
	$parent_slug = 'edit.php?post_type=flash_modal';

	add_submenu_page(
		$parent_slug,
		'Settings — Flash Modals',
		'Settings',
		'manage_options',
		'faeim-settings',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * React mount point.
 */
function render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	} ?>
	<div class="wrap">
		<h1>Flash Modals — Settings</h1>
		<div id="flashacademy-exit-modal-admin">
			<p>Loading…</p>
		</div>
	</div>
<?php }

/**
 * Enqueue admin React bundle only on our Settings page.
 */
function enqueue_assets( string $hook ): void {
	// For parent = edit.php?post_type=flash_modal and slug = faeim-settings
	// the hook suffix becomes:
	//   flash_modal_page_faeim-settings
	if ( 'flash_modal_page_faeim-settings' !== $hook ) {
		return;
	}

	$rel  = 'build/flashacademy-exit-intent-modal/admin.js';
	$file = asset_path( $rel );
	$url  = asset_url( $rel );

	if ( ! file_exists( $file ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_add_inline_script(
				'jquery',
				'console.warn("Flash Modals: missing admin bundle at ' . esc_js( $rel ) . ' — run npm run build.");',
				'after'
			);
		}
		return;
	}

	// Load React bundle.
	wp_enqueue_script(
		'faeim-admin',
		$url,
		[ 'wp-element', 'wp-components', 'wp-api-fetch' ],
		filemtime( $file ),
		true
	);
	wp_enqueue_style( 'wp-components' );

	// Safety: ensure the mount div exists.
	wp_add_inline_script(
		'faeim-admin',
		'(function(){var m=document.getElementById("flashacademy-exit-modal-admin");if(!m){var c=document.querySelector("#wpbody-content");if(c){m=document.createElement("div");m.id="flashacademy-exit-modal-admin";c.prepend(m);}}})();',
		'before'
	);
}
