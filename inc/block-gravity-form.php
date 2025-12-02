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
 *
 * @param array       $attributes Block attributes.
 * @param string      $content    Block content (unused).
 * @param \WP_Block   $block      Block instance (unused).
 *
 * @return string HTML.
 */
function render_gravity_form_block( $attributes, $content = '', $block = null ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] render_gravity_form_block() called with attributes: ' . print_r( $attributes, true ) );
	}

	$form_id = isset( $attributes['formId'] ) ? (int) $attributes['formId'] : 0;

	// Optional: fallback to ACF field for legacy modals.
	if ( ! $form_id && function_exists( 'get_field' ) ) {
		$post_id = get_the_ID();
		if ( $post_id ) {
			$acf_form_id = (int) get_field( 'faeim_gravity_form_id', $post_id );
			if ( $acf_form_id ) {
				$form_id = $acf_form_id;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[FAEIM] using ACF fallback form_id=' . $form_id );
				}
			}
		}
	}

	// Are we in an editor / admin context?
	$is_editor = is_admin()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "[FAEIM] resolved form_id={$form_id}, is_editor=" . ( $is_editor ? 'yes' : 'no' ) );
	}

	// No form selected.
	if ( ! $form_id ) {
		// In editor: show helpful message.
		if ( $is_editor ) {
			return '<p class="faeim-gform-placeholder">'
				. esc_html__( 'Select a Gravity Form from the block settings.', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}

		// Frontend: fail silently.
		return '';
	}

	// Ensure Gravity Forms is available.
	if ( ! function_exists( 'gravity_form' ) || ! class_exists( '\GFAPI' ) ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-error">'
				. esc_html__( 'Gravity Forms is not active.', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}

		return '';
	}

	// Ensure the selected form exists.
	$form = \GFAPI::get_form( $form_id );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] GFAPI::get_form(' . $form_id . ') => ' . ( $form ? 'FOUND' : 'NOT FOUND' ) );
	}

	if ( ! $form ) {
		if ( $is_editor ) {
			return '<p class="faeim-gform-error">'
				. esc_html__( 'Selected Gravity Form not found (it may be trashed or deleted).', 'flashacademy-exit-intent-modal' )
				. '</p>';
		}

		return '';
	}

	// Log some status flags for debugging.
	$is_active = isset( $form['is_active'] ) ? (bool) $form['is_active'] : true;
	$is_trash  = isset( $form['is_trash'] ) ? (bool) $form['is_trash'] : false;

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(
			sprintf(
				'[FAEIM] form %d status: active=%s, trash=%s',
				$form_id,
				$is_active ? 'yes' : 'no',
				$is_trash ? 'yes' : 'no'
			)
		);
	}

	// In the editor, allow inactive forms to render so preview always works.
	// On the frontend, respect the active/inactive state.
	$display_inactive = $is_editor;

	ob_start();

	// (id, display_title, display_description, display_inactive, field_values, ajax)
	gravity_form( $form_id, false, false, $display_inactive, null, true );

	$html = ob_get_clean();

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] buffer length for form ' . $form_id . ' = ' . strlen( $html ) );
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
}
