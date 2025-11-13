<?php
/**
 * Gravity Form dynamic block render logic.
 */

namespace FlashAcademy\FlashModals;

defined( 'ABSPATH' ) || exit;

/**
 * Attach render callback for flashacademy/gravity-form block
 * after it's registered from metadata.
 */
function hook_gravity_form_block_render_callback() {
	if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
		return;
	}

	$registry = \WP_Block_Type_Registry::get_instance();
	$block    = $registry->get_registered( 'flashacademy/gravity-form' );

	if ( ! $block ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[FAEIM] flashacademy/gravity-form block not registered; cannot attach render callback.' );
		}
		return;
	}

	$block->render_callback = __NAMESPACE__ . '\\render_gravity_form_block';

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] render callback attached for flashacademy/gravity-form.' );
	}
}
add_action( 'init', __NAMESPACE__ . '\\hook_gravity_form_block_render_callback', 20 );

/**
 * Server-side renderer for the FlashAcademy Gravity Form block.
 */
function render_gravity_form_block( $attributes, $content = '', $block = null ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] render_gravity_form_block() called' );
	}

	$form_id = isset( $attributes['formId'] ) ? (int) $attributes['formId'] : 0;

	// Optional: fallback to ACF field for legacy modals.
	if ( ! $form_id && function_exists( 'get_field' ) ) {
		$post_id = get_the_ID();
		if ( $post_id ) {
			$acf_form_id = (int) get_field( 'faeim_gravity_form_id', $post_id );
			if ( $acf_form_id ) {
				$form_id = $acf_form_id;
			}
		}
	}

	// No form selected → non-empty placeholder (prevents "Block rendered as empty").
	if ( ! $form_id ) {
		return '<p class="faeim-gform-placeholder">'
			. esc_html__( 'Select a Gravity Form from the block settings.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Ensure Gravity Forms is available.
	if ( ! function_exists( 'gravity_form' ) || ! class_exists( '\GFAPI' ) ) {
		return '<p class="faeim-gform-error">'
			. esc_html__( 'Gravity Forms is not active.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Ensure the selected form exists.
	$form = \GFAPI::get_form( $form_id );
	if ( ! $form ) {
		return '<p class="faeim-gform-error">'
			. esc_html__( 'Selected Gravity Form not found.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Capture the real Gravity Forms HTML.
	ob_start();

	// (id, display_title, display_description, display_inactive, field_values, ajax)
	gravity_form( $form_id, false, false, false, null, true );

	$html = ob_get_clean();

	if ( ! $html ) {
		return '<p class="faeim-gform-error">'
			. esc_html__( 'Gravity Form could not be rendered.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	return $html;
}
