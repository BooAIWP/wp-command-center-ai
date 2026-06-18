<?php
/**
 * Master administration page.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Admin;

defined( 'ABSPATH' ) || exit;

final class AdminPage {
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

		$clients       = get_option( 'wpccai_master_clients', array() );
		$clients       = is_array( $clients ) ? $clients : array();
		$shared_secret = (string) get_option( 'wpccai_master_shared_secret', '' );
		?>
		<div class="wrap wpccai-wrap">
			<h1><?php esc_html_e( 'WP Command Center AI', 'wp-command-center-ai-master' ); ?></h1>
			<p><?php esc_html_e( 'Connected sites report their status through the heartbeat API.', 'wp-command-center-ai-master' ); ?></p>
			<div class="wpccai-card">
				<strong><?php echo esc_html( number_format_i18n( count( $clients ) ) ); ?></strong>
				<span><?php esc_html_e( 'connected sites', 'wp-command-center-ai-master' ); ?></span>
			</div>
			<h2><?php esc_html_e( 'Client connection', 'wp-command-center-ai-master' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Master site URL', 'wp-command-center-ai-master' ); ?></th>
					<td><code><?php echo esc_html( home_url( '/' ) ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Shared secret', 'wp-command-center-ai-master' ); ?></th>
					<td>
						<input class="regular-text code" type="text" readonly value="<?php echo esc_attr( $shared_secret ); ?>">
						<p class="description"><?php esc_html_e( 'Copy this value into each Client plugin. Keep it private.', 'wp-command-center-ai-master' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
