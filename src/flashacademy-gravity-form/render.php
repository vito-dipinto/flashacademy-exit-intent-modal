<?php
/**
 * Server-side render for FlashAcademy Gravity Form block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return function( $attributes ) {
	$form_id = isset( $attributes['formId'] ) ? (int) $attributes['formId'] : 0;

	// Optional: fallback to ACF meta for legacy content.
	if ( ! $form_id && function_exists( 'get_field' ) ) {
		$post_id = get_the_ID();
		if ( $post_id ) {
			$acf_form_id = (int) get_field( 'faeim_gravity_form_id', $post_id );
			if ( $acf_form_id ) {
				$form_id = $acf_form_id;
			}
		}
	}

	// No form selected → show helpful placeholder (avoid empty output).
	if ( ! $form_id ) {
		return '<p class="faeim-gform-placeholder">'
			. esc_html__( 'Select a Gravity Form from the block settings.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Gravity Forms must be active.
	if ( ! function_exists( 'gravity_form' ) || ! class_exists( '\GFAPI' ) ) {
		return '<p class="faeim-gform-error">'
			. esc_html__( 'Gravity Forms is not active.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Ensure the selected form actually exists.
	$form = \GFAPI::get_form( $form_id );
	if ( ! $form ) {
		return '<p class="faeim-gform-error">'
			. esc_html__( 'Selected Gravity Form not found.', 'flashacademy-exit-intent-modal' )
			. '</p>';
	}

	// Capture and return the real Gravity Forms HTML.
	ob_start();

	// (form_id, display_title, display_description, display_inactive, field_values, ajax)
	gravity_form( $form_id, false, false, false, null, true );

	return ob_get_clean();
};
