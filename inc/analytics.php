<?php
namespace FlashAcademy\FlashModals\Analytics;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_Error;

/**
 * Boot analytics module.
 */
function setup(): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[FAEIM] Analytics\\setup() called' );
	}

	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

	// Safety: ensure events table exists (runs once, stores an option).
	add_action( 'admin_init', __NAMESPACE__ . '\\maybe_create_events_table' );
}

/**
 * Name of the events table.
 *
 * @return string
 */
function get_events_table_name(): string {
	global $wpdb;

	return $wpdb->prefix . 'faeim_events';
}

/**
 * Create the wp_faeim_events table.
 *
 * Columns:
 * - id            BIGINT PK
 * - modal_id      related flash_modal post ID
 * - event_type    'shown' | 'converted' | (future)
 * - occurred_at   DATETIME (GMT)
 * - page_url      URL where event happened
 * - referer       raw HTTP referer
 * - user_agent    browser UA (truncated)
 * - ip_address    IP (v4/v6)
 */
function create_events_table(): void {
	global $wpdb;

	$table           = get_events_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		modal_id BIGINT(20) UNSIGNED NOT NULL,
		event_type VARCHAR(20) NOT NULL,
		occurred_at DATETIME NOT NULL,
		page_url TEXT NULL,
		referer TEXT NULL,
		user_agent VARCHAR(255) NULL,
		ip_address VARCHAR(45) NULL,
		PRIMARY KEY  (id),
		KEY modal_id (modal_id),
		KEY modal_event (modal_id, event_type, occurred_at)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Run create_events_table() once and remember via an option.
 */
function maybe_create_events_table(): void {
	$flag = get_option( 'faeim_events_table_installed' );
	if ( '1' === $flag ) {
		return;
	}

	create_events_table();
	update_option( 'faeim_events_table_installed', '1' );
}

/**
 * Insert a single analytics event row.
 *
 * @param int    $modal_id   flash_modal post ID.
 * @param string $event_type 'shown' or 'converted'.
 */
function log_event( int $modal_id, string $event_type ): void {
	global $wpdb;

	$table = get_events_table_name();

	// Page URL.
	$page_url = '';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$page_url = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	// Referer.
	$referer = wp_get_raw_referer() ?: '';

	// User agent (truncate to 255 for safety).
	$user_agent = '';
	if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$user_agent = substr(
			sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ),
			0,
			255
		);
	}

	// IP (very rough; behind proxies/CDNs you’d adapt).
	$ip = '';
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	$wpdb->insert(
		$table,
		[
			'modal_id'    => $modal_id,
			'event_type'  => $event_type,
			'occurred_at' => current_time( 'mysql', true ), // GMT
			'page_url'    => $page_url,
			'referer'     => $referer,
			'user_agent'  => $user_agent,
			'ip_address'  => $ip,
		],
		[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
	);
}

/**
 * Register REST API routes for analytics.
 */
function register_routes(): void {
	register_rest_route(
		'faeim/v1',
		'/event',
		[
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\handle_event',
			'permission_callback' => '__return_true', // public — just increments counters + logs
			'args'                => [
				'modalId'   => [
					'type'     => 'integer',
					'required' => true,
				],
				'eventType' => [
					'type'     => 'string',
					'required' => true,
				],
			],
		]
	);

	register_rest_route(
		'faeim/v1',
		'/stats',
		[
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\get_stats',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		]
	);
}

/**
 * Handle frontend analytics events: "shown" or "converted".
 *
 * This:
 * - Updates post meta aggregates (impressions / conversions / timestamps)
 * - Logs a row in wp_faeim_events
 *
 * @param WP_REST_Request $request Request.
 * @return array|WP_Error
 */
function handle_event( WP_REST_Request $request ) {
	$modal_id  = (int) $request->get_param( 'modalId' );
	$eventType = sanitize_text_field( $request->get_param( 'eventType' ) );

	if ( ! $modal_id || ! in_array( $eventType, [ 'shown', 'converted' ], true ) ) {
		return new WP_Error( 'faeim_bad_request', 'Invalid event payload', [ 'status' => 400 ] );
	}

	if ( 'shown' === $eventType ) {
		$count = (int) get_post_meta( $modal_id, '_faeim_impressions', true );
		update_post_meta( $modal_id, '_faeim_impressions', $count + 1 );
		update_post_meta( $modal_id, '_faeim_last_shown', current_time( 'mysql' ) );
	} elseif ( 'converted' === $eventType ) {
		$count = (int) get_post_meta( $modal_id, '_faeim_conversions', true );
		update_post_meta( $modal_id, '_faeim_conversions', $count + 1 );
		update_post_meta( $modal_id, '_faeim_last_converted', current_time( 'mysql' ) );
	}

	// 🔥 NEW: log the granular event.
	log_event( $modal_id, $eventType );

	return [ 'success' => true ];
}

/**
 * Return stats per modal for the admin (aggregated from post meta).
 *
 * @param WP_REST_Request $request Request.
 * @return array
 */
function get_stats( WP_REST_Request $request ): array {
	$modals = get_posts(
		[
			'post_type'      => 'flash_modal',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		]
	);

	$data = [];

	foreach ( $modals as $modal ) {
		$impressions = (int) get_post_meta( $modal->ID, '_faeim_impressions', true );
		$conversions = (int) get_post_meta( $modal->ID, '_faeim_conversions', true );
		$rate        = $impressions > 0
			? round( ( $conversions / max( $impressions, 1 ) ) * 100, 2 )
			: 0;

		$data[] = [
			'id'             => $modal->ID,
			'title'          => get_the_title( $modal ),
			'impressions'    => $impressions,
			'conversions'    => $conversions,
			'conversionRate' => $rate,
			'lastShown'      => get_post_meta( $modal->ID, '_faeim_last_shown', true ),
			'lastConverted'  => get_post_meta( $modal->ID, '_faeim_last_converted', true ),
		];
	}

	return $data;
}
