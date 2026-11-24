<?php
/**
 * Read-only rating overview in WordPress admin.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Admin;

use Fyldtechokolader\Core\Ratings\RatingRepository;

defined( 'ABSPATH' ) || exit;

final class RatingsPage {
	private RatingRepository $ratings;

	public function __construct( RatingRepository $ratings ) {
		$this->ratings = $ratings;
	}

	public function register(): void {
		add_management_page(
			__( 'Chokoladeratings', 'fyldtechokolader-core' ),
			__( 'Chokoladeratings', 'fyldtechokolader-core' ),
			'manage_options',
			'fyldtechokolader-ratings',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du har ikke adgang til denne side.', 'fyldtechokolader-core' ) );
		}

		$rows = $this->ratings->overview();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Chokoladeratings', 'fyldtechokolader-core' ); ?></h1>
			<p><?php echo esc_html__( 'Oversigten er skrivebeskyttet. Legacy-import udføres kun via WP-CLI og starter som dry-run.', 'fyldtechokolader-core' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Chokolade', 'fyldtechokolader-core' ); ?></th>
						<th><?php echo esc_html__( 'Gennemsnit', 'fyldtechokolader-core' ); ?></th>
						<th><?php echo esc_html__( 'Antal', 'fyldtechokolader-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="3"><?php echo esc_html__( 'Der er endnu ingen ratings i den nye tabel.', 'fyldtechokolader-core' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $row['chokolade_id'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['average'], 2 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['count'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
