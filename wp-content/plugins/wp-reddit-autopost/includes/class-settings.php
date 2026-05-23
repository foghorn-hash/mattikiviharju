<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Reddit_Autopost_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		add_options_page(
			'Reddit Auto-Post',
			'Reddit Auto-Post',
			'manage_options',
			'wp-reddit-autopost',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'wp_reddit_autopost_options_group', 'wp_reddit_autopost_client_id' );
		register_setting( 'wp_reddit_autopost_options_group', 'wp_reddit_autopost_client_secret' );
		register_setting( 'wp_reddit_autopost_options_group', 'wp_reddit_autopost_username' );
		register_setting( 'wp_reddit_autopost_options_group', 'wp_reddit_autopost_password' );
		register_setting( 'wp_reddit_autopost_options_group', 'wp_reddit_autopost_subreddit' );
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Reddit Auto-Post Settings</h1>
			<p>Aseta Reddit Script Appin tunnukset tänne jotta postaukset voidaan lähettää automaattisesti.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'wp_reddit_autopost_options_group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Reddit Client ID</th>
						<td><input type="text" name="wp_reddit_autopost_client_id" value="<?php echo esc_attr( get_option( 'wp_reddit_autopost_client_id' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Reddit Client Secret</th>
						<td><input type="password" name="wp_reddit_autopost_client_secret" value="<?php echo esc_attr( get_option( 'wp_reddit_autopost_client_secret' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Reddit Username</th>
						<td><input type="text" name="wp_reddit_autopost_username" value="<?php echo esc_attr( get_option( 'wp_reddit_autopost_username' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Reddit Password</th>
						<td><input type="password" name="wp_reddit_autopost_password" value="<?php echo esc_attr( get_option( 'wp_reddit_autopost_password' ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Target Subreddit (without r/)</th>
						<td><input type="text" name="wp_reddit_autopost_subreddit" value="<?php echo esc_attr( get_option( 'wp_reddit_autopost_subreddit' ) ); ?>" class="regular-text" placeholder="e.g. i4ware_modpilot_dev" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
