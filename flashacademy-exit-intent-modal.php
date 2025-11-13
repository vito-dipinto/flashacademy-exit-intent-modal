<?php
/**
 * Plugin Name:       FlashAcademy Exit Intent Modal
 * Description:       OptinMonster-style exit-intent modals as campaigns (CPT + ACF + Gutenberg).
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            FlashAcademy
 * License:           GPL-2.0-or-later
 * Text Domain:       flashacademy-exit-intent-modal
 */

defined( 'ABSPATH' ) || exit;

// 1) Load our namespaced bootstrap.
require_once __DIR__ . '/inc/namespace.php';

// 2) Run setup() once when plugins are loaded.
if ( ! defined( 'FAEIM_BOOTSTRAPPED' ) ) {
	define( 'FAEIM_BOOTSTRAPPED', true );

	add_action( 'plugins_loaded', function () {
		static $ran = false;
		if ( $ran ) {
			return;
		}
		$ran = true;

		if ( function_exists( '\FlashAcademy\FlashModals\setup' ) ) {
			\FlashAcademy\FlashModals\setup();
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'FlashModals: setup() not found' );
		}
	}, 20 );
}
