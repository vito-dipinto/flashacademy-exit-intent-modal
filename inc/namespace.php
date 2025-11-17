<?php
namespace FlashAcademy\FlashModals;

defined( 'ABSPATH' ) || exit;

use FlashAcademy\FlashModals\Admin;
use FlashAcademy\FlashModals\CPT;
use FlashAcademy\FlashModals\ACF;
use FlashAcademy\FlashModals\Frontend;
use FlashAcademy\FlashModals\Blocks;
use FlashAcademy\FlashModals\GF_API;

/**
 * Main plugin bootstrap.
 */
function setup(): void {
	// Base plugin dir.
	if ( ! defined( 'FAEIM_PLUGIN_DIR' ) ) {
		// namespace.php is in /inc, so plugin_dir_path( dirname(__FILE__) ) -> plugin root.
		define( 'FAEIM_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
	}

	// Load modules.
	require_once __DIR__ . '/acf.php';
	require_once __DIR__ . '/cpt.php';
	require_once __DIR__ . '/admin.php';
	require_once __DIR__ . '/frontend.php';
	require_once __DIR__ . '/blocks.php';
	require_once __DIR__ . '/gf-api.php';
	require_once __DIR__ . '/block-gravity-form.php';

	// Boot modules (only if their setup() exists to avoid fatals).
	if ( function_exists( __NAMESPACE__ . '\\CPT\\setup' ) ) {
		CPT\setup();
	}

	if ( function_exists( __NAMESPACE__ . '\\Admin\\setup' ) ) {
		Admin\setup();
	}

	if ( function_exists( __NAMESPACE__ . '\\ACF\\setup' ) ) {
		ACF\setup();
	}

	// 🔴 You were missing these:

	if ( function_exists( __NAMESPACE__ . '\\Frontend\\setup' ) ) {
		Frontend\setup();
	}

	if ( function_exists( __NAMESPACE__ . '\\Blocks\\setup' ) ) {
		Blocks\setup();
	}

	if ( function_exists( __NAMESPACE__ . '\\GF_API\\setup' ) ) {
		GF_API\setup();
	}
}

/**
 * Resolve an absolute filesystem path for a plugin asset.
 */
function asset_path( string $relative ): string {
	return trailingslashit( FAEIM_PLUGIN_DIR ) . ltrim( $relative, '/\\' );
}

/**
 * Resolve a URL for a plugin asset.
 */
function asset_url( string $relative ): string {
	$plugin_file = trailingslashit( FAEIM_PLUGIN_DIR ) . 'flashacademy-exit-intent-modal.php';

	return plugins_url(
		ltrim( $relative, '/\\' ),
		$plugin_file
	);
}
