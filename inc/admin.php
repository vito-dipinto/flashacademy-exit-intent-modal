<?php
namespace FlashAcademy\FlashModals\Admin;

use function FlashAcademy\FlashModals\asset_path;
use function FlashAcademy\FlashModals\asset_url;
use FlashAcademy\FlashModals\Analytics; // for events table name

defined( 'ABSPATH' ) || exit;

function setup(): void {
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Add "Settings" and "Analytics" submenus under the Flash Modals CPT menu.
 */
function register_menu(): void {
	$parent_slug = 'edit.php?post_type=flash_modal';

	// Settings page (React app).
	add_submenu_page(
		$parent_slug,
		'Settings — Flash Modals',
		'Settings',
		'manage_options',
		'faeim-settings',
		__NAMESPACE__ . '\\render_settings_page'
	);

	// Analytics page (WP_List_Table + CSV + detail view).
	add_submenu_page(
		$parent_slug,
		'Analytics — Flash Modals',
		'Analytics',
		'manage_options',
		'faeim-analytics',
		__NAMESPACE__ . '\\render_analytics_page'
	);
}

/**
 * React mount point for Settings.
 */
function render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	} ?>
	<div class="wrap">
		<h1>Flash Modals — Settings</h1>
		<div id="flashacademy-exit-modal-admin">
			<p>Loading…</p>
		</div>
	</div>
<?php }

/**
 * Analytics page router:
 * - ?faeim_delete_all=1&_faeim_nonce=… → delete all analytics
 * - ?faeim_export=csv                  → CSV export (all modals or per modal)
 * - ?modal=ID                          → Detail view for that modal
 * - default                            → List table of all modals
 */
function render_analytics_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle "Delete all analytics" (truncate events + reset meta).
	if (
		isset( $_GET['faeim_delete_all'], $_GET['_faeim_nonce'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_verify_nonce( wp_unslash( $_GET['_faeim_nonce'] ), 'faeim_delete_all_action' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	) {
		delete_all_analytics_data();

		echo '<div class="notice notice-success"><p>' .
			esc_html__( 'All analytics data has been deleted.', 'flashacademy-exit-intent-modal' ) .
		'</p></div>';
	}

	// CSV export (all modals or a single one).
	if ( isset( $_GET['faeim_export'] ) && 'csv' === $_GET['faeim_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$modal_id = isset( $_GET['modal'] ) ? (int) $_GET['modal'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		export_analytics_csv( $modal_id );
		return;
	}

	// Detail view for a single modal.
	if ( isset( $_GET['modal'] ) && (int) $_GET['modal'] > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		render_modal_detail_page( (int) $_GET['modal'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Default: list table summary.
	render_analytics_list_page();
}

/**
 * Helper: render the main Analytics list (WP_List_Table).
 */
function render_analytics_list_page(): void {
	// Ensure WP_List_Table is available.
	if ( ! class_exists( '\WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	// Define our table class once.
	if ( ! class_exists( __NAMESPACE__ . '\\Analytics_List_Table' ) ) {
		class Analytics_List_Table extends \WP_List_Table {

			public function get_columns(): array {
				return [
					'title'          => __( 'Modal', 'flashacademy-exit-intent-modal' ),
					'impressions'    => __( 'Impressions', 'flashacademy-exit-intent-modal' ),
					'conversions'    => __( 'Conversions', 'flashacademy-exit-intent-modal' ),
					'conv_rate'      => __( 'Conv. rate', 'flashacademy-exit-intent-modal' ),
					'last_shown'     => __( 'Last shown', 'flashacademy-exit-intent-modal' ),
					'last_converted' => __( 'Last converted', 'flashacademy-exit-intent-modal' ),
				];
			}

			public function prepare_items(): void {
				$per_page     = 20;
				$current_page = $this->get_pagenum();

				$search = isset( $_REQUEST['s'] )
					? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) )
					: '';

				$args = [
					'post_type'      => 'flash_modal',
					'post_status'    => 'publish',
					'posts_per_page' => $per_page,
					'paged'          => $current_page,
					'orderby'        => 'title',
					'order'          => 'ASC',
				];

				if ( '' !== $search ) {
					$args['s'] = $search;
				}

				$query = new \WP_Query( $args );

				$items = [];

				foreach ( $query->posts as $post ) {
					$id = $post->ID;

					$impressions = (int) \get_post_meta( $id, '_faeim_impressions', true );
					$conversions = (int) \get_post_meta( $id, '_faeim_conversions', true );
					$last_shown  = \get_post_meta( $id, '_faeim_last_shown', true );
					$last_conv   = \get_post_meta( $id, '_faeim_last_converted', true );

					$rate = $impressions > 0
						? round( ( $conversions / max( $impressions, 1 ) ) * 100, 2 )
						: 0;

					$items[] = [
						'ID'             => $id,
						'title'          => \get_the_title( $post ),
						'impressions'    => $impressions,
						'conversions'    => $conversions,
						'conv_rate'      => $rate,
						'last_shown'     => $last_shown,
						'last_converted' => $last_conv,
					];
				}

				$this->_column_headers = [ $this->get_columns(), [], [] ];
				$this->items           = $items;

				$this->set_pagination_args(
					[
						'total_items' => (int) $query->found_posts,
						'per_page'    => $per_page,
						'total_pages' => (int) $query->max_num_pages,
					]
				);
			}

			public function column_title( $item ) {
				$link = \get_edit_post_link( $item['ID'] );

				// Link to detail view as well.
				$detail_url = add_query_arg(
					[
						'post_type' => 'flash_modal',
						'page'      => 'faeim-analytics',
						'modal'     => (int) $item['ID'],
					],
					admin_url( 'edit.php' )
				);

				$title_link = sprintf(
					'<a href="%s">%s</a> <span class="description">(#%d)</span>',
					esc_url( $link ),
					esc_html( $item['title'] ),
					(int) $item['ID']
				);

				$detail_link = sprintf(
					'<br><a href="%s">%s</a>',
					esc_url( $detail_url ),
					esc_html__( 'View analytics details', 'flashacademy-exit-intent-modal' )
				);

				return $title_link . $detail_link;
			}

			public function column_default( $item, $column_name ) {
				switch ( $column_name ) {
					case 'impressions':
					case 'conversions':
						return (int) $item[ $column_name ];

					case 'conv_rate':
						return esc_html( $item['conv_rate'] ) . '%';

					case 'last_shown':
					case 'last_converted':
						return $item[ $column_name ]
							? esc_html( $item[ $column_name ] )
							: '—';

					default:
						return isset( $item[ $column_name ] )
							? esc_html( $item[ $column_name ] )
							: '';
				}
			}
		}
	}

	$table = new Analytics_List_Table();
	$table->prepare_items();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Flash Modals — Analytics', 'flashacademy-exit-intent-modal' ); ?></h1>
		<p><?php esc_html_e( 'Impressions and conversions for all Flash Modals.', 'flashacademy-exit-intent-modal' ); ?></p>

		<p style="margin-bottom: 20px;">
			<a class="button button-secondary"
			   href="<?php echo esc_url( add_query_arg(
				   [
					   'post_type'    => 'flash_modal',
					   'page'         => 'faeim-analytics',
					   'faeim_export' => 'csv',
				   ],
				   admin_url( 'edit.php' )
			   ) ); ?>">
				<?php esc_html_e( 'Export all events as CSV', 'flashacademy-exit-intent-modal' ); ?>
			</a>

			<a class="button button-danger"
			   style="color:#fff;background:#d63638;border-color:#d63638;margin-left:10px;"
			   href="<?php
			   echo esc_url(
				   wp_nonce_url(
					   add_query_arg(
						   [
							   'post_type'       => 'flash_modal',
							   'page'            => 'faeim-analytics',
							   'faeim_delete_all'=> 1,
						   ],
						   admin_url( 'edit.php' )
					   ),
					   'faeim_delete_all_action',
					   '_faeim_nonce'
				   )
			   );
			   ?>"
			   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete ALL analytics data? This cannot be undone.', 'flashacademy-exit-intent-modal' ) ); ?>');"
			>
				<?php esc_html_e( 'Delete all analytics', 'flashacademy-exit-intent-modal' ); ?>
			</a>
		</p>

		<form method="get">
			<input type="hidden" name="post_type" value="flash_modal" />
			<input type="hidden" name="page" value="faeim-analytics" />
			<?php
			$table->search_box( __( 'Search Modals', 'flashacademy-exit-intent-modal' ), 'faeim-modal' );
			$table->display();
			?>
		</form>
	</div>
	<?php
}

/**
 * Detail view for a single modal.
 *
 * @param int $modal_id Modal post ID.
 */
function render_modal_detail_page( int $modal_id ): void {
	global $wpdb;

	$modal = get_post( $modal_id );
	if ( ! $modal || 'flash_modal' !== $modal->post_type ) {
		wp_die( esc_html__( 'Invalid modal.', 'flashacademy-exit-intent-modal' ) );
	}

	$impressions = (int) get_post_meta( $modal_id, '_faeim_impressions', true );
	$conversions = (int) get_post_meta( $modal_id, '_faeim_conversions', true );
	$last_shown  = get_post_meta( $modal_id, '_faeim_last_shown', true );
	$last_conv   = get_post_meta( $modal_id, '_faeim_last_converted', true );

	$rate = $impressions > 0
		? round( ( $conversions / max( $impressions, 1 ) ) * 100, 2 )
		: 0;

	// Recent events from wp_faeim_events.
	$table_name = function_exists( __NAMESPACE__ . '\\Analytics\\get_events_table_name' )
		? Analytics\get_events_table_name()
		: ( function () {
			global $wpdb;
			return $wpdb->prefix . 'faeim_events';
		} )();

	$events = [];
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE modal_id = %d ORDER BY occurred_at DESC LIMIT 100",
				$modal_id
			),
			ARRAY_A
		);
	}
	?>
	<div class="wrap">
		<h1>
			<?php
			printf(
				/* translators: 1: modal title, 2: modal ID */
				esc_html__( 'Analytics — %1$s (#%2$d)', 'flashacademy-exit-intent-modal' ),
				esc_html( get_the_title( $modal ) ),
				(int) $modal_id
			);
			?>
		</h1>

		<p>
			<a href="<?php echo esc_url( add_query_arg( [ 'post_type' => 'flash_modal', 'page' => 'faeim-analytics' ], admin_url( 'edit.php' ) ) ); ?>">
				&laquo; <?php esc_html_e( 'Back to all modals', 'flashacademy-exit-intent-modal' ); ?>
			</a>
		</p>

		<h2><?php esc_html_e( 'Summary', 'flashacademy-exit-intent-modal' ); ?></h2>
		<table class="widefat striped" style="max-width:600px;">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Impressions', 'flashacademy-exit-intent-modal' ); ?></th>
					<td><?php echo (int) $impressions; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Conversions', 'flashacademy-exit-intent-modal' ); ?></th>
					<td><?php echo (int) $conversions; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Conversion rate', 'flashacademy-exit-intent-modal' ); ?></th>
					<td><?php echo esc_html( $rate ); ?>%</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Last shown', 'flashacademy-exit-intent-modal' ); ?></th>
					<td><?php echo $last_shown ? esc_html( $last_shown ) : '—'; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Last converted', 'flashacademy-exit-intent-modal' ); ?></th>
					<td><?php echo $last_conv ? esc_html( $last_conv ) : '—'; ?></td>
				</tr>
			</tbody>
		</table>

		<p style="margin-top:1em;">
			<a class="button button-secondary"
			   href="<?php echo esc_url( add_query_arg(
				   [
					   'post_type'    => 'flash_modal',
					   'page'         => 'faeim-analytics',
					   'modal'        => $modal_id,
					   'faeim_export' => 'csv',
				   ],
				   admin_url( 'edit.php' )
			   ) ); ?>">
				<?php esc_html_e( 'Export CSV for this modal', 'flashacademy-exit-intent-modal' ); ?>
			</a>
		</p>

		<h2><?php esc_html_e( 'Recent events', 'flashacademy-exit-intent-modal' ); ?></h2>
		<?php if ( empty( $events ) ) : ?>
			<p><?php esc_html_e( 'No events recorded yet for this modal.', 'flashacademy-exit-intent-modal' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Occurred at (GMT)', 'flashacademy-exit-intent-modal' ); ?></th>
						<th><?php esc_html_e( 'Event', 'flashacademy-exit-intent-modal' ); ?></th>
						<th><?php esc_html_e( 'Page URL', 'flashacademy-exit-intent-modal' ); ?></th>
						<th><?php esc_html_e( 'Referrer', 'flashacademy-exit-intent-modal' ); ?></th>
						<th><?php esc_html_e( 'IP', 'flashacademy-exit-intent-modal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $events as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['occurred_at'] ); ?></td>
							<td><?php echo esc_html( $row['event_type'] ); ?></td>
							<td><?php echo esc_html( $row['page_url'] ); ?></td>
							<td><?php echo esc_html( $row['referer'] ); ?></td>
							<td><?php echo esc_html( $row['ip_address'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * CSV export for analytics events.
 *
 * @param int $modal_id Optional modal ID; 0 = all modals.
 */
function export_analytics_csv( int $modal_id = 0 ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to export analytics.', 'flashacademy-exit-intent-modal' ) );
	}

	global $wpdb;

	$table_name = function_exists( __NAMESPACE__ . '\\Analytics\\get_events_table_name' )
		? Analytics\get_events_table_name()
		: ( function () {
			global $wpdb;
			return $wpdb->prefix . 'faeim_events';
		} )();

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
		wp_die( esc_html__( 'Events table not found.', 'flashacademy-exit-intent-modal' ) );
	}

	$where           = '1=1';
	$params          = [];
	$filename_suffix = 'all-modals';

	if ( $modal_id > 0 ) {
		$where           = 'modal_id = %d';
		$params[]        = $modal_id;
		$filename_suffix = 'modal-' . $modal_id;
	}

	$sql = "SELECT e.*, p.post_title 
			FROM {$table_name} e 
			LEFT JOIN {$wpdb->posts} p ON e.modal_id = p.ID
			WHERE {$where}
			ORDER BY e.occurred_at DESC";

	$rows = $params
		? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A )
		: $wpdb->get_results( $sql, ARRAY_A );

	$filename = 'flash-modals-analytics-' . $filename_suffix . '-' . gmdate( 'Ymd-His' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $filename );

	$fh = fopen( 'php://output', 'w' );

	// Header row.
	fputcsv(
		$fh,
		[
			'Modal ID',
			'Modal Title',
			'Event Type',
			'Occurred At (GMT)',
			'Page URL',
			'Referrer',
			'IP Address',
			'User Agent',
		]
	);

	foreach ( $rows as $row ) {
		fputcsv(
			$fh,
			[
				$row['modal_id'],
				$row['post_title'],
				$row['event_type'],
				$row['occurred_at'],
				$row['page_url'],
				$row['referer'],
				$row['ip_address'],
				$row['user_agent'],
			]
		);
	}

	fclose( $fh );
	exit;
}

/**
 * Fully delete all analytics data from:
 * - wp_faeim_events table
 * - post meta (_faeim_impressions, _faeim_conversions, _faeim_last_shown, _faeim_last_converted)
 */
function delete_all_analytics_data(): void {
	global $wpdb;

	// 1. Truncate events table (if it exists).
	$table_name = function_exists( __NAMESPACE__ . '\\Analytics\\get_events_table_name' )
		? Analytics\get_events_table_name()
		: $wpdb->prefix . 'faeim_events';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	// 2. Reset meta for all modals.
	$modals = get_posts(
		[
			'post_type'      => 'flash_modal',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);

	foreach ( $modals as $id ) {
		delete_post_meta( $id, '_faeim_impressions' );
		delete_post_meta( $id, '_faeim_conversions' );
		delete_post_meta( $id, '_faeim_last_shown' );
		delete_post_meta( $id, '_faeim_last_converted' );
	}
}

/**
 * Enqueue admin React bundle only on our Settings page.
 *
 * Analytics page uses a native PHP table, so no JS needed there.
 */
function enqueue_assets( string $hook ): void {
	// For parent = edit.php?post_type=flash_modal and slug = faeim-settings
	// the hook suffix becomes:
	//   flash_modal_page_faeim-settings
	if ( 'flash_modal_page_faeim-settings' !== $hook ) {
		return;
	}

	$rel  = 'build/flashacademy-exit-intent-modal/admin.js';
	$file = asset_path( $rel );
	$url  = asset_url( $rel );

	if ( ! file_exists( $file ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_add_inline_script(
				'jquery',
				'console.warn("Flash Modals: missing admin bundle at ' . esc_js( $rel ) . ' — run npm run build.");',
				'after'
			);
		}
		return;
	}

	wp_enqueue_script(
		'faeim-admin',
		$url,
		[ 'wp-element', 'wp-components', 'wp-api-fetch' ],
		filemtime( $file ),
		true
	);
	wp_enqueue_style( 'wp-components' );

	wp_add_inline_script(
		'faeim-admin',
		'(function(){var m=document.getElementById("flashacademy-exit-modal-admin");if(!m){var c=document.querySelector("#wpbody-content");if(c){m=document.createElement("div");m.id="flashacademy-exit-modal-admin";c.prepend(m);}}})();',
		'before'
	);
}
