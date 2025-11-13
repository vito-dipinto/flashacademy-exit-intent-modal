<?php
namespace FlashAcademy\FlashModals\Frontend;

use function FlashAcademy\FlashModals\asset_url;

defined( 'ABSPATH' ) || exit;

function setup(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
	add_action( 'wp_footer', __NAMESPACE__ . '\\render_modal' );
}

/**
 * Choose the best matching active modal for current page.
 */
function get_active_modal_id(): ?int {
	if ( ! function_exists( 'get_field' ) ) {
		return null; // ACF required for rules.
	}

	$page_id = get_queried_object_id();

	$q = new \WP_Query( [
		'post_type'      => 'flash_modal',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'faeim_active',
		'meta_value'     => 1,
	] );

	if ( ! $q->have_posts() ) {
		return null;
	}

	$chosen_id   = null;
	$chosen_prio = -PHP_INT_MAX;

	while ( $q->have_posts() ) {
		$q->the_post();
		$id = get_the_ID();

		$active = (bool) get_field( 'faeim_active', $id );
		if ( ! $active ) {
			continue;
		}

		$show_everywhere = (bool) get_field( 'faeim_show_everywhere', $id );
		$include_pages   = get_field( 'faeim_include_pages', $id ); // IDs.

		$matches = false;

		if ( $show_everywhere ) {
			$matches = true;
		} elseif ( is_array( $include_pages ) && $page_id ) {
			$matches = in_array( $page_id, array_map( 'intval', $include_pages ), true );
		}

		if ( ! $matches ) {
			continue;
		}

		$priority = (int) get_field( 'faeim_priority', $id );
		if ( $priority > $chosen_prio ) {
			$chosen_prio = $priority;
			$chosen_id   = $id;
		}
	}

	wp_reset_postdata();

	return $chosen_id ?: null;
}

/**
 * Enqueue frontend JS/CSS and localize config.
 */
function enqueue_assets(): void {
	$modal_id = get_active_modal_id();
	if ( ! $modal_id ) {
		return;
	}

	wp_enqueue_style(
		'faeim-modal',
		asset_url( 'assets/modal.css' ),
		[],
		null
	);

	wp_enqueue_script(
		'faeim-modal',
		asset_url( 'assets/modal.js' ),
		[],
		null,
		true
	);

	if ( function_exists( 'get_field' ) ) {
		$config = [
			'modalId'       => $modal_id,
			'exitIntent'    => (bool) get_field( 'faeim_trigger_exit_intent', $modal_id ),
			'minTime'       => (int)  get_field( 'faeim_trigger_min_time', $modal_id ),
			'minScroll'     => (int)  get_field( 'faeim_trigger_min_scroll', $modal_id ),
			'frequencyDays' => (int)  get_field( 'faeim_frequency_days', $modal_id ),
			'pageId'        => get_queried_object_id(),
		];

		wp_localize_script(
			'faeim-modal',
			'faeimConfig',
			$config
		);
	}
}

/**
 * Output the modal HTML structure.
 * Uses Gutenberg content + ACF Gravity Form ID.
 */
function render_modal(): void {
	$modal_id = get_active_modal_id();
	if ( ! $modal_id ) {
		return;
	}

	$post = get_post( $modal_id );
	if ( ! $post ) {
		return;
	}

	$content = apply_filters( 'the_content', $post->post_content );
	$form_id = function_exists( 'get_field' )
		? (int) get_field( 'faeim_gravity_form_id', $modal_id )
		: 0;
	?>
	<div id="faeim-modal" class="faeim-hidden" aria-hidden="true" role="dialog" aria-modal="true">
		<div class="faeim-overlay" data-faeim-close></div>
		<div class="faeim-content" role="document">
			<button class="faeim-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'flashacademy-exit-intent-modal' ); ?>" data-faeim-close>&times;</button>

			<div class="faeim-body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php if ( $form_id ) : ?>
				<div class="faeim-form">
					<?php echo do_shortcode( '[gravityform id="' . (int) $form_id . '" title="false" description="false" ajax="true"]' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
