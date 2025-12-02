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

	$is_editor = is_user_logged_in() && current_user_can( 'edit_posts' );

	if ( function_exists( 'error_log' ) ) {
		error_log( 'FAEIM render.php: start, form_id=' . $form_id );
	}

	// No form selected.
	if ( ! $form_id ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-placeholder">'
				. esc_html__( 'Select a Gravity Form from the block settings.', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}
		return '';
	}

	// Gravity Forms must be active.
	if ( ! function_exists( 'gravity_form' ) || ! class_exists( '\GFAPI' ) ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-error">'
				. esc_html__( 'Gravity Forms is not active.', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}
		return '';
	}

	// Ensure the selected form actually exists.
	$form = \GFAPI::get_form( $form_id );

	if ( function_exists( 'error_log' ) ) {
		error_log( 'FAEIM render.php: GFAPI::get_form result for ' . $form_id . ': ' . print_r( $form, true ) );
	}

	if ( ! $form ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-error">'
				. esc_html__( 'Selected Gravity Form not found (it may be trashed or deleted).', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}
		return '';
	}

	// Helpful info about inactive / trashed forms.
	$is_active = isset( $form['is_active'] ) ? (bool) $form['is_active'] : true;
	$is_trash  = isset( $form['is_trash'] ) ? (bool) $form['is_trash'] : false;

	if ( $is_editor && ( ! $is_active || $is_trash ) ) {
		$status_message_parts = [];
		if ( ! $is_active ) {
			$status_message_parts[] = esc_html__( 'inactive', 'flashacademy-exit-intent-modal' );
		}
		if ( $is_trash ) {
			$status_message_parts[] = esc_html__( 'in the trash', 'flashacademy-exit-intent-modal' );
		}

		$status_message = implode( ' & ', $status_message_parts );

		// Still allow preview, but tell you what’s up.
		echo '<p class="faeim-gform-notice">'
			. sprintf(
				/* translators: 1: status (inactive / in trash) */
				esc_html__( 'Note: This form is currently %s in Gravity Forms.', 'flashacademy-exit-intent-modal' ),
				$status_message
			)
			. '</p>';
	}

	// In the editor, allow inactive forms to render so preview always works.
	// On the frontend, respect the active/inactive state.
	$display_inactive = $is_editor;

	ob_start();

	// (form_id, display_title, display_description, display_inactive, field_values, ajax)
	gravity_form( $form_id, false, false, $display_inactive, null, true );

	$html = ob_get_clean();

	if ( function_exists( 'error_log' ) ) {
		error_log( 'FAEIM render.php: buffer length for form ' . $form_id . ' = ' . strlen( $html ) );
	}

	// If GF rendered nothing, surface a clear message in the editor.
	if ( '' === trim( $html ) ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-error">'
				. esc_html__( 'The selected Gravity Form rendered no output. It may be inactive, empty, or misconfigured.', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}
		return '';
	}

	return $html;
};
