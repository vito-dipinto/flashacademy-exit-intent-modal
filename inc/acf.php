<?php
namespace FlashAcademy\FlashModals\ACF;

defined( 'ABSPATH' ) || exit;

function setup(): void {
	add_action( 'acf/init', __NAMESPACE__ . '\\register_field_group' );
}

/**
 * Register local ACF fields for flash_modal CPT.
 * (Requires ACF plugin to be active.)
 */
function register_field_group(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'    => 'group_faeim_modal_settings',
		'title'  => 'Flash Modal Settings',
		'fields' => [
			[
				'key'   => 'faeim_active',
				'label' => 'Active',
				'name'  => 'faeim_active',
				'type'  => 'true_false',
				'ui'    => 1,
				'default_value' => 1,
			],
			[
				'key'   => 'faeim_priority',
				'label' => 'Priority',
				'name'  => 'faeim_priority',
				'type'  => 'number',
				'instructions' => 'Higher priority wins when multiple modals match.',
				'default_value' => 10,
			],
			[
				'key'   => 'faeim_tab_triggers',
				'label' => 'Triggers',
				'type'  => 'tab',
				'placement' => 'top',
			],
			[
				'key'   => 'faeim_trigger_exit_intent',
				'label' => 'Exit intent (desktop)',
				'name'  => 'faeim_trigger_exit_intent',
				'type'  => 'true_false',
				'ui'    => 1,
				'default_value' => 1,
			],
			[
				'key'   => 'faeim_trigger_min_time',
				'label' => 'Minimum time on page (seconds)',
				'name'  => 'faeim_trigger_min_time',
				'type'  => 'number',
				'default_value' => 0,
				'min'   => 0,
				'max'   => 120,
			],
			[
				'key'   => 'faeim_trigger_min_scroll',
				'label' => 'Minimum scroll depth (%)',
				'name'  => 'faeim_trigger_min_scroll',
				'type'  => 'number',
				'default_value' => 0,
				'min'   => 0,
				'max'   => 100,
			],
			[
				'key'   => 'faeim_tab_display',
				'label' => 'Display Rules',
				'type'  => 'tab',
				'placement' => 'top',
			],
			[
				'key'   => 'faeim_show_everywhere',
				'label' => 'Show on all pages',
				'name'  => 'faeim_show_everywhere',
				'type'  => 'true_false',
				'ui'    => 1,
				'default_value' => 1,
			],
			[
				'key'   => 'faeim_include_pages',
				'label' => 'Limit to specific pages/posts',
				'name'  => 'faeim_include_pages',
				'type'  => 'relationship',
				'post_type' => [ 'page', 'post' ],
				'filters'   => [ 'search', 'post_type' ],
				'return_format' => 'id',
				'conditional_logic' => [
					[
						[
							'field'    => 'faeim_show_everywhere',
							'operator' => '!=',
							'value'    => '1',
						],
					],
				],
			],
			[
				'key'   => 'faeim_tab_form',
				'label' => 'Form & Frequency',
				'type'  => 'tab',
				'placement' => 'top',
			],
			[
				'key'   => 'faeim_gravity_form_id',
				'label' => 'Gravity Form ID',
				'name'  => 'faeim_gravity_form_id',
				'type'  => 'number',
				'instructions' => 'ID of the Gravity Form to embed inside this modal.',
				'min'   => 0,
			],
			[
				'key'   => 'faeim_frequency_days',
				'label' => 'Hide after conversion/close (days)',
				'name'  => 'faeim_frequency_days',
				'type'  => 'number',
				'default_value' => 7,
				'min'   => 0,
				'max'   => 365,
			],
		],
		'location' => [
			[
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'flash_modal',
				],
			],
		],
		'position' => 'normal',
		'style'    => 'default',
	] );
}
