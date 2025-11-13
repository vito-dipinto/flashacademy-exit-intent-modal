<?php
/**
 * REST API endpoints for Gravity Forms integration.
 */

namespace FlashAcademy\FlashModals\GFAPI;

defined( 'ABSPATH' ) || exit;

use GFAPI;

/**
 * Register REST route for Gravity Forms list.
 */
function register_routes() {
	register_rest_route(
		'flashacademy/v1',
		'/gravity-forms',
		[
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\get_forms',
			'permission_callback' => function () {
				return \current_user_can( 'edit_posts' );
			},
		]
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

/**
 * REST callback: return available Gravity Forms.
 */
function get_forms() {
	if ( ! class_exists( '\GFAPI' ) ) {
		return new \WP_Error(
			'gf_not_active',
			__( 'Gravity Forms is not active.', 'flashacademy-exit-intent-modal' ),
			[ 'status' => 500 ]
		);
	}

	$forms = \GFAPI::get_forms();

	if ( ! is_array( $forms ) ) {
		return [];
	}

	return array_map(
		function ( $form ) {
			return [
				'id'    => isset( $form['id'] ) ? (int) $form['id'] : 0,
				'title' => isset( $form['title'] ) ? $form['title'] : '',
			];
		},
		$forms
	);
}
