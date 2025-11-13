<?php
namespace FlashAcademy\FlashModals;

defined( 'ABSPATH' ) || exit;

/**
 * Register all blocks from the built metadata collection.
 */
function register_blocks() {
	$build_dir = FAEIM_PLUGIN_DIR . 'build';
	$manifest  = $build_dir . '/blocks-manifest.php';

	// WP 6.5+ helper.
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) && file_exists( $manifest ) ) {
		wp_register_block_types_from_metadata_collection( $build_dir, $manifest );
		return;
	}

	// Older helper.
	if ( function_exists( 'wp_register_block_metadata_collection' ) && file_exists( $manifest ) ) {
		wp_register_block_metadata_collection( $build_dir, $manifest );
	}

	// Fallback: loop over manifest entries.
	if ( file_exists( $manifest ) ) {
		$manifest_data = require $manifest;
		if ( is_array( $manifest_data ) ) {
			foreach ( array_keys( $manifest_data ) as $block_path ) {
				register_block_type( $build_dir . '/' . $block_path );
			}
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );
