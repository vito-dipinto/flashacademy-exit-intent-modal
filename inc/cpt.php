<?php
namespace FlashAcademy\FlashModals\CPT;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the Flash Modals CPT.
 */
function setup(): void {
	add_action( 'init', __NAMESPACE__ . '\\register_flash_modal_cpt' );
}

/**
 * Flash Modals CPT: each post is a modal campaign.
 */
function register_flash_modal_cpt(): void {
	$labels = [
		'name'               => __( 'Flash Modals', 'flashacademy-exit-intent-modal' ),
		'singular_name'      => __( 'Flash Modal', 'flashacademy-exit-intent-modal' ),
		'add_new'            => __( 'Add New Modal', 'flashacademy-exit-intent-modal' ),
		'add_new_item'       => __( 'Add New Flash Modal', 'flashacademy-exit-intent-modal' ),
		'edit_item'          => __( 'Edit Flash Modal', 'flashacademy-exit-intent-modal' ),
		'new_item'           => __( 'New Flash Modal', 'flashacademy-exit-intent-modal' ),
		'view_item'          => __( 'View Flash Modal', 'flashacademy-exit-intent-modal' ),
		'search_items'       => __( 'Search Flash Modals', 'flashacademy-exit-intent-modal' ),
		'not_found'          => __( 'No modals found', 'flashacademy-exit-intent-modal' ),
		'not_found_in_trash' => __( 'No modals found in Trash', 'flashacademy-exit-intent-modal' ),
		'menu_name'          => __( 'Flash Modals', 'flashacademy-exit-intent-modal' ),
	];

	$args = [
		'labels'             => $labels,
		'public'             => false,                          // Not publicly queryable.
		'show_ui'            => true,                           // Show in admin.
		'show_in_menu'       => true,                           // 👈 Top-level "Flash Modals" menu.
		'menu_position'      => 25,
		'menu_icon'          => 'dashicons-welcome-widgets-menus',
		'show_in_rest'       => true,                           // Gutenberg support.
		'supports'           => [ 'title', 'editor', 'revisions' ],
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'has_archive'        => false,
		'publicly_queryable' => false,
		'rewrite'            => false,
	];

	register_post_type( 'flash_modal', $args );
}
