<?php
/**
 * Client settings page.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Admin;

use WPCommandCenterAI\Client\Security\KeyStore;
use WPCommandCenterAI\Client\Service\Registration;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {
	public function __construct(
		private Registration $registration,
		private KeyStore $keys
	) {
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_wpccai_client_register', array( $this, 'handle_registration' ) );
	}

	public function add_page(): void {
		add_options_page(
			__( 'WP Command Center AI', 'wp-command-center-ai-client' ),
			__( 'Command Center', 'wp-command-center-ai-client' ),
			'manage_options',
			'wp-command-center-ai-client',
			array( $this, 'render' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'wpccai_client',
			'wpccai_client_master_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			'wpccai_client',
			'wpccai_client_enrollment_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}

	public function handle_registration(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to register this site.', 'wp-command-center-ai-client' ) );
		}

		check_admin_referer( 'wpccai_client_register' );

		$result = $this->registration->run(
			(string) get_option( 'wpccai_client_master_url', '' ),
			(string) get_option( 'wpccai_client_enrollment_token', '' )
		);
		$query  = is_wp_error( $result )
			? array(
				'wpccai_notice'  => 'error',
				'wpccai_message' => $result->get_error_message(),
			)
			: array( 'wpccai_notice' => 'registered' );

		wp_safe_redirect( add_query_arg( $query, admin_url( 'options-general.php?page=wp-command-center-ai-client' ) ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$site_id  = (string) get_option( 'wpccai_client_site_id', '' );
		$key_pair = $this->keys->current();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Command Center AI Client', 'wp-command-center-ai-client' ); ?></h1>
			<?php if ( isset( $_GET['wpccai_notice'] ) && 'registered' === sanitize_key( wp_unslash( $_GET['wpccai_notice'] ) ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'The site was registered successfully.', 'wp-command-center-ai-client' ); ?></p></div>
			<?php elseif ( isset( $_GET['wpccai_notice'], $_GET['wpccai_message'] ) && 'error' === sanitize_key( wp_unslash( $_GET['wpccai_notice'] ) ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['wpccai_message'] ) ) ); ?></p></div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpccai_client' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wpccai_client_master_url"><?php esc_html_e( 'Master site URL', 'wp-command-center-ai-client' ); ?></label></th>
						<td><input class="regular-text code" id="wpccai_client_master_url" name="wpccai_client_master_url" type="url" value="<?php echo esc_attr( (string) get_option( 'wpccai_client_master_url', '' ) ); ?>" placeholder="https://example.com"></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpccai_client_enrollment_token"><?php esc_html_e( 'Enrollment token', 'wp-command-center-ai-client' ); ?></label></th>
						<td><input class="regular-text code" id="wpccai_client_enrollment_token" name="wpccai_client_enrollment_token" type="password" value="<?php echo esc_attr( (string) get_option( 'wpccai_client_enrollment_token', '' ) ); ?>" autocomplete="new-password"></td>
					</tr>
					<tr><th scope="row"><?php esc_html_e( 'Registration status', 'wp-command-center-ai-client' ); ?></th><td><?php echo esc_html( '' === $site_id ? __( 'Not registered', 'wp-command-center-ai-client' ) : __( 'Registered', 'wp-command-center-ai-client' ) ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Site ID', 'wp-command-center-ai-client' ); ?></th><td><code><?php echo esc_html( '' === $site_id ? '—' : $site_id ); ?></code></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Client key ID', 'wp-command-center-ai-client' ); ?></th><td><code><?php echo esc_html( $key_pair->key_id ); ?></code></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="wpccai_client_register">
				<?php wp_nonce_field( 'wpccai_client_register' ); ?>
				<?php submit_button( __( 'Register with Master', 'wp-command-center-ai-client' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}
}
