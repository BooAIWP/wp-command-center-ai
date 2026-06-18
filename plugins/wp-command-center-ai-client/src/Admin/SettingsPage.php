<?php
/**
 * Client settings page.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Admin;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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
			'wpccai_client_shared_secret',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Command Center AI Client', 'wp-command-center-ai-client' ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpccai_client' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wpccai_client_master_url"><?php esc_html_e( 'Master site URL', 'wp-command-center-ai-client' ); ?></label></th>
						<td><input class="regular-text code" id="wpccai_client_master_url" name="wpccai_client_master_url" type="url" value="<?php echo esc_attr( (string) get_option( 'wpccai_client_master_url', '' ) ); ?>" placeholder="https://example.com"></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpccai_client_shared_secret"><?php esc_html_e( 'Shared secret', 'wp-command-center-ai-client' ); ?></label></th>
						<td><input class="regular-text code" id="wpccai_client_shared_secret" name="wpccai_client_shared_secret" type="password" value="<?php echo esc_attr( (string) get_option( 'wpccai_client_shared_secret', '' ) ); ?>" autocomplete="new-password"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
