<?php
/**
 * Rating overview and controlled legacy migration in WordPress admin.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Admin;

use Fyldtechokolader\Core\Content\TextNormalizer;
use Fyldtechokolader\Core\Migration\LegacyRatingImporter;
use Fyldtechokolader\Core\Ratings\RatingRepository;
use Throwable;

defined( 'ABSPATH' ) || exit;

final class RatingsPage {
	private const PAGE_SLUG           = 'fyldtechokolader-ratings';
	private const DRY_RUN_ACTION      = 'fyldtechokolader_legacy_dry_run';
	private const IMPORT_ACTION       = 'fyldtechokolader_legacy_import';
	private const IMPORT_CONFIRMATION = 'IMPORTER 206';

	private RatingRepository $ratings;
	private LegacyRatingImporter $importer;

	public function __construct( RatingRepository $ratings, LegacyRatingImporter $importer ) {
		$this->ratings  = $ratings;
		$this->importer = $importer;
	}

	public function register(): void {
		add_management_page(
			__( 'Chokoladeratings', 'fyldtechokolader-core' ),
			__( 'Chokoladeratings', 'fyldtechokolader-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function handle_dry_run(): void {
		$this->assert_access( self::DRY_RUN_ACTION );

		try {
			$report = $this->importer->run( false );
			$this->store_notice(
				$report['matches_expectation'] ? 'success' : 'error',
				$report['matches_expectation']
					? __( 'Dry-run fandt præcis 206 sikre ratings. Ingen data blev ændret.', 'fyldtechokolader-core' )
					: __( 'Dry-run matcher ikke de forventede 206 ratings. Import er blokeret.', 'fyldtechokolader-core' ),
				$report
			);
		} catch ( Throwable $exception ) {
			$this->store_notice( 'error', $exception->getMessage() );
		}

		$this->redirect();
	}

	public function handle_import(): void {
		$this->assert_access( self::IMPORT_ACTION );

		$backup_confirmed = isset( $_POST['backup_confirmed'] )
			&& '1' === sanitize_text_field( wp_unslash( $_POST['backup_confirmed'] ) );
		$confirmation     = isset( $_POST['import_confirmation'] )
			? sanitize_text_field( wp_unslash( $_POST['import_confirmation'] ) )
			: '';

		if ( ! $backup_confirmed || self::IMPORT_CONFIRMATION !== $confirmation ) {
			$this->store_notice(
				'error',
				__( 'Importen blev ikke startet. Bekræft en frisk backup og skriv den præcise kontroltekst.', 'fyldtechokolader-core' )
			);
			$this->redirect();
		}

		try {
			$report = $this->importer->run( true );
			$this->store_notice(
				'success',
				sprintf(
					/* translators: 1: inserted count, 2: existing count. */
					__( '%1$d ratings blev importeret. %2$d fandtes allerede.', 'fyldtechokolader-core' ),
					$report['inserted'],
					$report['already_imported']
				),
				$report
			);
		} catch ( Throwable $exception ) {
			$this->store_notice( 'error', $exception->getMessage() );
		}

		$this->redirect();
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du har ikke adgang til denne side.', 'fyldtechokolader-core' ) );
		}

		$rows   = $this->ratings->overview();
		$notice = get_transient( $this->notice_key() );
		if ( false !== $notice ) {
			delete_transient( $this->notice_key() );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Chokoladeratings', 'fyldtechokolader-core' ); ?></h1>

			<?php $this->render_notice( is_array( $notice ) ? $notice : null ); ?>

			<h2><?php echo esc_html__( 'Legacy-import', 'fyldtechokolader-core' ); ?></h2>
			<p><?php echo esc_html__( 'Kør altid dry-run først. Den læser Elementor-data og ændrer ingenting.', 'fyldtechokolader-core' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::DRY_RUN_ACTION ); ?>">
				<?php wp_nonce_field( self::DRY_RUN_ACTION ); ?>
				<?php submit_button( __( 'Kør dry-run', 'fyldtechokolader-core' ), 'secondary', 'submit', false ); ?>
			</form>

			<h3><?php echo esc_html__( 'Importer de verificerede ratings', 'fyldtechokolader-core' ); ?></h3>
			<p><?php echo esc_html__( 'Importen er idempotent, men den skriver til ratingtabellen. Den genkontrollerer, at der findes præcis 206 sikre ratings, og ruller alle ændringer tilbage ved fejl.', 'fyldtechokolader-core' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>">
				<?php wp_nonce_field( self::IMPORT_ACTION ); ?>
				<p>
					<label>
						<input type="checkbox" name="backup_confirmed" value="1">
						<?php echo esc_html__( 'Jeg bekræfter, at der findes en frisk backup fra før importen.', 'fyldtechokolader-core' ); ?>
					</label>
				</p>
				<p>
					<label for="fyldtechokolader-import-confirmation">
						<?php echo esc_html__( 'Bekræft importen ved at skrive IMPORTER 206:', 'fyldtechokolader-core' ); ?>
					</label><br>
					<input id="fyldtechokolader-import-confirmation" class="regular-text" type="text" name="import_confirmation" autocomplete="off">
				</p>
				<?php submit_button( __( 'Importer 206 ratings', 'fyldtechokolader-core' ), 'primary', 'submit', false ); ?>
			</form>

			<hr>
			<h2><?php echo esc_html__( 'Aktuelle ratings', 'fyldtechokolader-core' ); ?></h2>
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
							<td><?php echo esc_html( TextNormalizer::plain( get_the_title( $row['chokolade_id'] ) ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['average'], 2 ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row['count'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed>|null $notice Stored result notice.
	 */
	private function render_notice( ?array $notice ): void {
		if ( null === $notice ) {
			return;
		}

		$type = 'success' === ( $notice['type'] ?? '' ) ? 'success' : 'error';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( (string) ( $notice['message'] ?? '' ) ); ?></p>
		</div>
		<?php

		if ( isset( $notice['report'] ) && is_array( $notice['report'] ) ) {
			$this->render_report( $notice['report'] );
		}
	}

	/**
	 * @param array<string, mixed> $report Import report.
	 */
	private function render_report( array $report ): void {
		?>
		<table class="widefat striped" style="max-width: 900px; margin: 12px 0 24px;">
			<tbody>
				<tr><th><?php echo esc_html__( 'Tilstand', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( (string) $report['mode'] ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Indsendelser', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['submissions'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Gyldige scores', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['valid_scores'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Sikre kandidater', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['safe_candidates'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Uden chokolade-ID', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['missing_chocolate'] ) ); ?></td></tr>
				<?php if ( 'commit' === $report['mode'] ) : ?>
					<tr><th><?php echo esc_html__( 'Importeret', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['inserted'] ) ); ?></td></tr>
					<tr><th><?php echo esc_html__( 'Fandtes allerede', 'fyldtechokolader-core' ); ?></th><td><?php echo esc_html( number_format_i18n( (int) $report['already_imported'] ) ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php

		if ( empty( $report['by_chocolate'] ) || ! is_array( $report['by_chocolate'] ) ) {
			return;
		}
		?>
		<table class="widefat striped" style="max-width: 900px; margin: 12px 0 24px;">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Chokolade', 'fyldtechokolader-core' ); ?></th>
					<th><?php echo esc_html__( 'Ratings', 'fyldtechokolader-core' ); ?></th>
					<th><?php echo esc_html__( 'Forventet gennemsnit', 'fyldtechokolader-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $report['by_chocolate'] as $chocolate_report ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $chocolate_report['title'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $chocolate_report['count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (float) $chocolate_report['average'], 2 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function assert_access( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Du har ikke adgang til denne handling.', 'fyldtechokolader-core' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * @param array<string, mixed>|null $report Optional import report.
	 */
	private function store_notice( string $type, string $message, ?array $report = null ): void {
		set_transient(
			$this->notice_key(),
			array(
				'type'    => $type,
				'message' => $message,
				'report'  => $report,
			),
			10 * MINUTE_IN_SECONDS
		);
	}

	private function notice_key(): string {
		return 'fyldtechokolader_rating_notice_' . get_current_user_id();
	}

	private function redirect(): never {
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}
