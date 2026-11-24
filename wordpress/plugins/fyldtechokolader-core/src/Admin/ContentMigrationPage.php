<?php
/**
 * Controlled content migration in WordPress admin.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Admin;

use Fyldtechokolader\Core\Content\TextNormalizer;
use Fyldtechokolader\Core\Migration\ContentMigrator;
use Throwable;

defined( 'ABSPATH' ) || exit;

final class ContentMigrationPage {
	private const PAGE_SLUG           = 'fyldtechokolader-content-migration';
	private const DRY_RUN_ACTION      = 'fyldtechokolader_content_dry_run';
	private const IMPORT_ACTION       = 'fyldtechokolader_content_import';
	private const IMPORT_CONFIRMATION = 'MIGRER 28';

	private ContentMigrator $migrator;

	public function __construct( ContentMigrator $migrator ) {
		$this->migrator = $migrator;
	}

	public function register(): void {
		add_management_page(
			__( 'Chokoladeindhold', 'fyldtechokolader-core' ),
			__( 'Chokoladeindhold', 'fyldtechokolader-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function handle_dry_run(): void {
		$this->assert_access( self::DRY_RUN_ACTION );
		$this->run( false );
	}

	public function handle_import(): void {
		$this->assert_access( self::IMPORT_ACTION );

		$backup_confirmed = isset( $_POST['backup_confirmed'] )
			&& '1' === sanitize_text_field( wp_unslash( $_POST['backup_confirmed'] ) );
		$confirmation     = isset( $_POST['import_confirmation'] )
			? sanitize_text_field( wp_unslash( $_POST['import_confirmation'] ) )
			: '';

		if ( ! $backup_confirmed || self::IMPORT_CONFIRMATION !== $confirmation ) {
			$this->store_notice( 'error', __( 'Migreringen blev ikke startet. Bekræft en frisk backup og skriv den præcise kontroltekst.', 'fyldtechokolader-core' ) );
			$this->redirect();
		}

		$this->run( true );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du har ikke adgang til denne side.', 'fyldtechokolader-core' ) );
		}

		$notice = get_transient( $this->notice_key() );
		if ( false !== $notice ) {
			delete_transient( $this->notice_key() );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Migrering af chokoladeindhold', 'fyldtechokolader-core' ); ?></h1>
			<?php $this->render_notice( is_array( $notice ) ? $notice : null ); ?>

			<p><?php echo esc_html__( 'Migreringen opretter produktionsår, valgfri produktionsdato og præcis én normaliseret skaltype for hver af de 28 chokolader. Legacy-felterne bevares.', 'fyldtechokolader-core' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::DRY_RUN_ACTION ); ?>">
				<?php wp_nonce_field( self::DRY_RUN_ACTION ); ?>
				<?php submit_button( __( 'Kør dry-run', 'fyldtechokolader-core' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php echo esc_html__( 'Udfør migrering', 'fyldtechokolader-core' ); ?></h2>
			<p><?php echo esc_html__( 'Kør og kontrollér dry-run først. Installationen af pluginet starter ikke migreringen.', 'fyldtechokolader-core' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>">
				<?php wp_nonce_field( self::IMPORT_ACTION ); ?>
				<p><label><input type="checkbox" name="backup_confirmed" value="1"> <?php echo esc_html__( 'Jeg bekræfter, at der findes en frisk backup fra før migreringen.', 'fyldtechokolader-core' ); ?></label></p>
				<p>
					<label for="fyldtechokolader-content-confirmation"><?php echo esc_html__( 'Bekræft ved at skrive MIGRER 28:', 'fyldtechokolader-core' ); ?></label><br>
					<input id="fyldtechokolader-content-confirmation" class="regular-text" type="text" name="import_confirmation" autocomplete="off">
				</p>
				<?php submit_button( __( 'Migrer 28 chokolader', 'fyldtechokolader-core' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	private function run( bool $commit ): never {
		try {
			$report  = $this->migrator->run( $commit );
			$success = (bool) $report['matches_expectation'];
			$message = $commit
				? __( 'Indholdsmigreringen blev gennemført for alle 28 chokolader.', 'fyldtechokolader-core' )
				: __( 'Dry-run fandt og validerede alle 28 chokolader. Ingen data blev ændret.', 'fyldtechokolader-core' );
			$this->store_notice( $success ? 'success' : 'error', $success ? $message : __( 'Dry-run fandt ikke præcis 28 chokolader. Migrering er blokeret.', 'fyldtechokolader-core' ), $report );
		} catch ( Throwable $exception ) {
			$this->store_notice( 'error', $exception->getMessage() );
		}

		$this->redirect();
	}

	/**
	 * @param array<string, mixed>|null $notice Stored notice.
	 */
	private function render_notice( ?array $notice ): void {
		if ( null === $notice ) {
			return;
		}

		$type = 'success' === ( $notice['type'] ?? '' ) ? 'success' : 'error';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible"><p><?php echo esc_html( (string) ( $notice['message'] ?? '' ) ); ?></p></div>
		<?php
		if ( empty( $notice['report']['rows'] ) || ! is_array( $notice['report']['rows'] ) ) {
			return;
		}
		?>
		<table class="widefat striped" style="max-width: 1200px; margin: 12px 0 24px;">
			<thead><tr>
				<th><?php echo esc_html__( 'Chokolade', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Legacy-dato', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Produktionsår', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Produktionsdato', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Legacy-skal', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Ny skaltype', 'fyldtechokolader-core' ); ?></th>
				<th><?php echo esc_html__( 'Status', 'fyldtechokolader-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $notice['report']['rows'] as $row ) : ?>
				<tr>
					<td><?php echo esc_html( TextNormalizer::plain( (string) $row['title'] ) ); ?></td>
					<td><?php echo esc_html( '' !== $row['legacy_date'] ? (string) $row['legacy_date'] : '—' ); ?></td>
					<td><?php echo esc_html( (string) $row['production_year'] ); ?></td>
					<td><?php echo esc_html( null !== $row['production_date'] ? (string) $row['production_date'] : '—' ); ?></td>
					<td><?php echo esc_html( (string) $row['legacy_shell'] ); ?></td>
					<td><?php echo esc_html( (string) $row['shell_name'] ); ?></td>
					<td><?php echo esc_html( $row['changed'] ? __( 'Skal ændres', 'fyldtechokolader-core' ) : __( 'Allerede korrekt', 'fyldtechokolader-core' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function assert_access( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du har ikke adgang til denne handling.', 'fyldtechokolader-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * @param array<string, mixed>|null $report Optional migration report.
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
		return 'fyldtechokolader_content_notice_' . get_current_user_id();
	}

	private function redirect(): never {
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}
}
