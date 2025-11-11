<?php
/**
 * Admin settings screen for managing NFSA API keys.
 *
 * @package NF_Submissions_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VWPNFSA_Settings handles generation and revocation of API keys.
 */
class VWPNFSA_Settings {

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Add admin menu item.
	 */
	public static function register_menu() {
		add_options_page(
			'NF API Keys',
			'NF API Keys',
			'manage_options',
			'vwpnfsa-api-keys',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Handle create/delete API key actions.
	 */
	public static function handle_actions() {
		if ( isset( $_POST['vwpnfsa_generate_key'] ) && check_admin_referer( 'vwpnfsa_generate_key_action' ) ) {
			$form_ids = isset( $_POST['vwpnfsa_form_ids'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['vwpnfsa_form_ids'] ) ) : array( 'all' );
			VWPNFSA_Auth::generate_key( $form_ids );
			wp_safe_redirect( admin_url( 'options-general.php?page=vwpnfsa-api-keys' ) );
			exit;
		}

		if ( isset( $_POST['vwpnfsa_revoke_key'] ) && check_admin_referer( 'vwpnfsa_revoke_key_action' ) ) {
			if ( isset( $_POST['vwpnfsa_api_key'] ) ) {
				$key = sanitize_text_field( wp_unslash( $_POST['vwpnfsa_api_key'] ) );
				VWPNFSA_Auth::revoke_key( $key );
				wp_safe_redirect( admin_url( 'options-general.php?page=vwpnfsa-api-keys' ) );
			}
		}
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		$keys = get_option( 'vwpnfsa_api_keys', array() );
		?>
		<div class="wrap">
			<h1>Ninja Forms API Keys</h1>

			<h2>Generate New Key</h2>
			<form method="post">
				<?php wp_nonce_field( 'vwpnfsa_generate_key_action' ); ?>
				<p>
					<label for="vwpnfsa_form_ids">Form IDs (comma-separated or "all"):</label><br>
					<input type="text" name="vwpnfsa_form_ids[]" id="vwpnfsa_form_ids" value="all" />
				</p>
				<p><input type="submit" name="vwpnfsa_generate_key" class="button button-primary" value="Generate API Key"></p>
			</form>

			<h2>Existing Keys</h2>
			<table class="widefat">
				<thead>
					<tr>
						<th>Key</th>
						<th>Allowed Forms</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $keys as $key => $meta ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', $meta['forms'] ) ); ?></td>
						<td><?php echo esc_html( $meta['created'] ); ?></td>
						<td>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'vwpnfsa_revoke_key_action' ); ?>
								<input type="hidden" name="vwpnfsa_api_key" value="<?php echo esc_attr( $key ); ?>">
								<input type="submit" name="vwpnfsa_revoke_key" class="button" value="Revoke">
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

add_action( 'admin_menu', array( 'VWPNFSA_Settings', 'init' ) );
