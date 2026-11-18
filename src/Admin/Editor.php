<?php
/**
 * Editor
 *
 * The rule builder inside a set's meta box: groups of conditions, each a
 * condition, an operator and a value.
 *
 * @package     SugarCommerce\Conditions\Admin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Admin;

use SugarCommerce\Conditions\Registry;
use WP_Post;

/**
 * Class Editor
 *
 * Renders the markup and templates the builder script fills in.
 */
final class Editor {

	/**
	 * The nonce action for saving a set's rules.
	 *
	 * @param string $set_id The set.
	 *
	 * @return string
	 */
	public static function nonce_action( string $set_id ): string {
		return 'save_conditions_' . $set_id;
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post    The post object.
	 * @param array   $metabox Metabox arguments.
	 *
	 * @return void
	 */
	public static function render( WP_Post $post, array $metabox ): void {
		$set_id      = $metabox['args']['set_id'] ?? '';
		$conditions  = Registry::get_conditions( $set_id );
		$saved       = get_post_meta( $post->ID, Registry::META_KEY, true );
		$config      = Registry::get_set( $set_id );
		$description = $config['description'] ?? '';

		if ( empty( $saved ) || ! is_array( $saved ) ) {
			$saved = [];
		}

		// Nonce for security
		wp_nonce_field( self::nonce_action( $set_id ), 'conditions_nonce' );
		?>
		<div class="conditions-builder" data-set-id="<?php echo esc_attr( $set_id ); ?>">
			<?php if ( ! empty( $description ) ) : ?>
				<p class="description">
					<?php echo esc_html( $description ); ?>
				</p>
			<?php endif; ?>

			<div class="condition-groups" data-conditions='<?php echo esc_attr( wp_json_encode( $saved ) ); ?>'>
				<!-- Groups will be rendered by JavaScript -->
			</div>

			<button type="button" class="button add-group">
				<?php esc_html_e( '+ Add "OR" Group', 'sugarcommerce' ); ?>
			</button>
		</div>

		<?php self::render_templates( $conditions ); ?>
		<?php
	}

	/**
	 * Render JavaScript templates.
	 *
	 * @param array $conditions The conditions array.
	 *
	 * @return void
	 */
	private static function render_templates( array $conditions ): void {
		?>
		<!-- Templates for JavaScript -->
		<script type="text/html" id="tmpl-condition-group">
			<div class="condition-group" data-group-id="{{ data.id }}">
				<div class="condition-group-header">
					<span class="group-label">
						<# if ( data.index === 0 ) { #>
							<?php esc_html_e( 'Match all of the following rules', 'sugarcommerce' ); ?>
						<# } else { #>
							<?php esc_html_e( 'Or match all of the following rules', 'sugarcommerce' ); ?>
						<# } #>
					</span>
					<div class="group-actions">
						<a href="#" class="duplicate-group"><?php esc_html_e( 'Duplicate', 'sugarcommerce' ); ?></a>
						<a href="#" class="delete-group"><?php esc_html_e( 'Delete', 'sugarcommerce' ); ?></a>
					</div>
				</div>

				<div class="conditions-list">
					<!-- Conditions will be rendered here -->
				</div>

				<div class="conditions-list-footer">
					<button type="button" class="button add-condition">
						<?php esc_html_e( '+ Add Condition', 'sugarcommerce' ); ?>
					</button>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-condition-row">
			<div class="condition-row" data-condition-id="{{ data.id }}">
				<div class="condition-fields">
					<select class="condition-select conditions-condition-select"
							name="_conditions[{{ data.groupId }}][rules][{{ data.id }}][condition]">
						<option value=""><?php esc_html_e( 'Select condition...', 'sugarcommerce' ); ?></option>
						<?php
						// Markup, not text: render_condition_options() escapes
						// every attribute and label it emits, and escaping the
						// assembled string here would print the tags instead.
						echo self::render_condition_options( $conditions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</select>

					<select class="operator-select"
							name="_conditions[{{ data.groupId }}][rules][{{ data.id }}][operator]" disabled>
						<option value=""><?php esc_html_e( 'Select...', 'sugarcommerce' ); ?></option>
					</select>

					<div class="value-field-wrapper">
						<input type="text" class="value-input" disabled
								placeholder="<?php esc_attr_e( 'Select a condition first', 'sugarcommerce' ); ?>">
					</div>

					<span class="condition-tooltip" data-tip="<?php esc_attr_e( 'Select a condition to see what it matches.', 'sugarcommerce' ); ?>">
						<span class="dashicons dashicons-info-outline"></span>
					</span>
				</div>

				<div class="condition-row-actions">
					<button type="button" class="button-link remove-condition"
							title="<?php esc_attr_e( 'Remove', 'sugarcommerce' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-group-connector">
			<div class="group-connector"><?php esc_html_e( 'OR', 'sugarcommerce' ); ?></div>
		</script>
		<?php
	}

	/**
	 * Render condition options grouped by category.
	 *
	 * @param array $conditions The conditions to render.
	 *
	 * @return string
	 */
	private static function render_condition_options( array $conditions ): string {
		// Group conditions by their group property
		$grouped = [];
		foreach ( $conditions as $id => $condition ) {
			$group               = $condition['group'] ?? 'General';
			$grouped[ $group ][] = [
				'id'    => $id,
				'label' => $condition['label'] ?? $id,
			];
		}

		// Sort groups alphabetically.
		ksort( $grouped );

		// Sort items within each group alphabetically by label so long
		// groups (a shop's order conditions, say) aren't a wall of randomly
		// ordered options. strnatcasecmp keeps numeric labels in numeric
		// order — "Top 10" sorts before "Top 100".
		foreach ( $grouped as &$items ) {
			usort( $items, static fn( $a, $b ) => strnatcasecmp( $a['label'], $b['label'] ) );
		}
		unset( $items );

		$html = '';
		foreach ( $grouped as $group => $items ) {
			$html .= '<optgroup label="' . esc_attr( $group ) . '">';
			foreach ( $items as $item ) {
				$html .= '<option value="' . esc_attr( $item['id'] ) . '">' . esc_html( $item['label'] ) . '</option>';
			}
			$html .= '</optgroup>';
		}

		return $html;
	}
}