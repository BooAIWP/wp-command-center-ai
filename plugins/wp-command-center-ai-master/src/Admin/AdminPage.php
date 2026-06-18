<?php
/**
 * Master administration page.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Admin;

use WPCommandCenterAI\Master\Client\ClientRepository;
use WPCommandCenterAI\Master\Security\KeyStore;

defined( 'ABSPATH' ) || exit;

final class AdminPage {
	public function __construct(
		private ClientRepository $clients,
		private KeyStore $keys
	) {
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu_page(): void {
		add_menu_page(
			__( 'WP Command Center AI', 'wp-command-center-ai-master' ),
			__( 'Command Center', 'wp-command-center-ai-master' ),
			'manage_options',
			'wp-command-center-ai',
			array( $this, 'render' ),
			'dashicons-networking',
			58
		);
	}

	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_wp-command-center-ai' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wpccai-master-admin',
			plugins_url( 'assets/css/admin.css', WPCCAI_MASTER_FILE ),
			array(),
			WPCCAI_MASTER_VERSION
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$clients          = $this->clients->all();
		$counts           = $this->clients->counts();
		$enrollment_token = (string) get_option( 'wpccai_master_enrollment_token', '' );
		$master_key       = $this->keys->current();
		?>
		<div class="wrap wpccai-wrap">
			<h1><?php esc_html_e( 'WP Command Center AI', 'wp-command-center-ai-master' ); ?></h1>
			<p><?php esc_html_e( 'Connected sites report their status through the heartbeat API.', 'wp-command-center-ai-master' ); ?></p>
			<div class="wpccai-card">
				<strong><?php echo esc_html( number_format_i18n( count( $clients ) ) ); ?></strong>
				<span><?php esc_html_e( 'connected sites', 'wp-command-center-ai-master' ); ?></span>
			</div>
			<div class="wpccai-card">
				<strong><?php echo esc_html( number_format_i18n( $counts['online'] ) ); ?></strong>
				<span><?php esc_html_e( 'online sites', 'wp-command-center-ai-master' ); ?></span>
			</div>
			<div class="wpccai-card">
				<strong><?php echo esc_html( number_format_i18n( $counts['offline'] ) ); ?></strong>
				<span><?php esc_html_e( 'offline sites', 'wp-command-center-ai-master' ); ?></span>
			</div>
			<h2><?php esc_html_e( 'Client connection', 'wp-command-center-ai-master' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Master site URL', 'wp-command-center-ai-master' ); ?></th>
					<td><code><?php echo esc_html( home_url( '/' ) ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enrollment token', 'wp-command-center-ai-master' ); ?></th>
					<td>
						<input class="regular-text code" type="text" readonly value="<?php echo esc_attr( $enrollment_token ); ?>">
						<p class="description"><?php esc_html_e( 'Use this token only during client registration. Keep it private.', 'wp-command-center-ai-master' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Master key ID', 'wp-command-center-ai-master' ); ?></th>
					<td><code><?php echo esc_html( $master_key->key_id ); ?></code></td>
				</tr>
			</table>
			<h2><?php esc_html_e( 'Registered clients', 'wp-command-center-ai-master' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Site', 'wp-command-center-ai-master' ); ?></th><th><?php esc_html_e( 'Status', 'wp-command-center-ai-master' ); ?></th><th><?php esc_html_e( 'Last seen', 'wp-command-center-ai-master' ); ?></th><th><?php esc_html_e( 'Key ID', 'wp-command-center-ai-master' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $clients as $client ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( (string) $client['site_url'] ); ?>"><?php echo esc_html( (string) $client['site_name'] ); ?></a></td>
							<td><?php echo esc_html( $this->clients->status( $client ) ); ?></td>
							<td><?php echo esc_html( empty( $client['last_seen_at'] ) ? '—' : wp_date( 'Y-m-d H:i:s', absint( $client['last_seen_at'] ) ) ); ?></td>
							<td><code><?php echo esc_html( (string) $client['current_key_id'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
