<?php
/*
Plugin Name: Click To Tweet WordPress Plugin
Description: This plugin integrates with the Click To Tweet web app (located at clicktotweet.com) and allows you to insert Click To Tweet boxes in your blog posts.
Version: 1.0.8
Author: ClickToTweet.com
Author URI: http://ctt.ec/
Plugin URI: http://ctt.ec/
*/

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

// Check for existing class
if ( !class_exists( 'ctt' ) ) {
	/**
	 * Main Class
	 */
	class ctt {

		/**
		 * Class constructor: initializes class variables and adds actions and filters.
		 */
		public function __construct() {
			$this->ctt();
		}

		public function ctt() {
			register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivation' ) );

			// Register global hooks
			$this->register_global_hooks();

			// Register admin only hooks
			if ( is_admin() ) {
				$this->register_admin_hooks();
			}
		}

		/**
		 * Print the contents of an array
		 * @param $array
		 */
		public function debug( $array ) {
			echo '<pre>';
			var_dump( $array );
			echo '</pre>';
		}

		/**
		 * Handles activation tasks, such as registering the uninstall hook.
		 */
		public function activation() {
			register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
		}

		/**
		 * Handles deactivation tasks, such as deleting plugin options.
		 */
		public function deactivation() {

		}

		/**
		 * Handles uninstallation tasks, such as deleting plugin options.
		 */
		public function uninstall() {
			delete_option( 'twitter-handle' );
		}

		/**
		 * Registers global hooks, these are added to both the admin and front-end.
		 */
		public function register_global_hooks() {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_css' ) );
			add_filter( 'the_content', array( $this, 'replace_tags' ) );
			add_shortcode( 'ctt', array( $this, 'ctt_shortcode_handler' ) );
		}

		/**
		 * Registers admin only hooks.
		 */
		public function register_admin_hooks() {
			// Cache bust tinymce
			add_filter( 'tiny_mce_version', array( $this, 'refresh_mce' ) );

			// Add Settings Link
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			// Add settings link to plugins listing page
			add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 2, 2 );

			// Add button plugin to TinyMCE
			add_action( 'init', array( $this, 'tinymce_button' ) );

			// AJAX dialog form
			add_action( 'wp_ajax_ctt_show_dialog', array( $this, 'ctt_show_dialog_callback' ) );

			// AJAX post form data
			add_action( 'wp_ajax_ctt_api_post', array( $this, 'ctt_api_post_callback' ) );
		}

		/**
		 * Show plugin dialog
		 */
		public function ctt_show_dialog_callback() {
			$ajax_nonce = wp_create_nonce( 'ctt_nonce_string' );
			$token = get_option( 'ctt-token' );
			$res = wp_remote_get( 'http://wp.clicktotweet.com/Wp/listCTTs?token=' . $token );
			if ( is_wp_error( $res ) ) {
				$content = 'Error: ' . $res->get_error_message();
			} else {
				$content = $res['body'];
			}
			include( plugin_dir_path( __FILE__ ) . 'ctt_dialog.php' );
			exit;
		}

		/**
		 * Post data via ctt.ec API
		 */
		public function ctt_api_post_callback() {
			check_ajax_referer( 'ctt_nonce_string', 'security' );
			$token = get_option( 'ctt-token' );
			$url = 'http://wp.clicktotweet.com/Wp/createSubmit?' . $_POST['data'];
			$res = wp_remote_get( $url );
			print_r( $res['body'] );
			exit;
		}

		public function tinymce_button() {
			if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
				return;
			}

			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( 'mce_external_plugins', array( $this, 'tinymce_register_plugin' ) );
				add_filter( 'mce_buttons', array( $this, 'tinymce_register_button' ) );
			}
		}

		public function tinymce_register_button( $buttons ) {
			array_push( $buttons, "|", "ctt" );

			return $buttons;
		}

		public function tinymce_register_plugin( $plugin_array ) {
			$plugin_array['ctt'] = plugins_url( '/ctt.js', __FILE__ );

			return $plugin_array;
		}

		/**
		 * Admin: Add settings link to plugin management page
		 */
		public function plugin_settings_link( $actions, $file ) {
			if ( false !== strpos( $file, 'ctt' ) ) {
				$actions['settings'] = '<a href="options-general.php?page=ctt">Settings</a>';
			}

			return $actions;
		}

		/**
		 * Admin: Add Link to sidebar admin menu
		 */
		public function admin_menu() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_options_page( 'Click To Tweet Options', 'Click To Tweet', 'manage_options', 'ctt', array(
				$this,
				'settings_page'
			) );
		}

		/**
		 * Admin: Settings page
		 */
		public function settings_page() {
			if ( !current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			} ?>

			<style type="text/css">
				.ctt__settings {
					text-align: center;
				}

				.ctt__settings h3 {
					font-size: 2em;
				}
			</style>

			<?php
			$token = ( isset( $_GET['token'] ) ) ? $_GET['token'] : get_option( 'ctt-token' );
			if ( $token ) {
				$screen_name = explode( '-', $token );
				$screen_name = $screen_name[0];
			} else {
				$screen_name = '';
			}

			$ref = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			?>
			<div class="wrap">
				<h2>ClickToTweet.com Integration</h2>
				<hr />
				<div class="ctt__settings">
					<?php if ( isset( $_GET['token'] ) && !isset( $_GET['settings-updated'] ) ) { ?>

					<?php } elseif ( $token || ( isset( $_GET['token'] ) && isset( $_GET['settings-updated'] ) ) ) { ?>
						<h3>You are connected to your @<?php echo $screen_name; ?> ClickToTweet.com account</h3>
						<p>
							<a href="http://ctt.ec/user/login?source=wp&ref=<?=$ref?>" class="button button-primary">Connect to different account</a>
						</p>
					<?php } else { ?>
						<a href="http://ctt.ec/user/login?source=wp&ref=<?=$ref?>" class="button button-primary">Sign-in with Twitter to connect to ClickToTweet.com</a>
					<?php } ?>

					<form method="post" action="options.php">
						<?php settings_fields( 'ctt-options' ); ?>
						<input type="hidden" name="ctt-token" value="<?php echo $token; ?>" />
						<?php if ( isset( $_GET['token'] ) && isset( $_GET['settings-updated'] ) ) { ?>

						<?php } elseif ( isset( $_GET['token'] ) ) { ?>
							<h3>Connecting to &ldquo;@<?= $screen_name ?>&rdquo;</h3>
							<p>Click "Save Changes" to finish connecting your ClickToTweet.com account.</p>
							<?php submit_button(); ?>
						<?php } ?>
					</form>
				</div>
			</div>
		<?php
		}

		/**
		 * Admin: Whitelist the settings used on the settings page
		 */
		public function register_settings() {
			register_setting( 'ctt-options', 'twitter-handle', array( $this, 'validate_settings' ) );
			register_setting( 'ctt-options', 'ctt-token', array( $this, 'validate_settings' ) );
		}

		/**
		 * Admin: Validate settings
		 */
		public function validate_settings( $input ) {
			return str_replace( '@', '', strip_tags( stripslashes( $input ) ) );
		}

		/**
		 * Add CSS needed for styling the plugin
		 */
		public function add_css() {
			wp_register_style( 'ctt', plugins_url( '/styles.css', __FILE__ ) );
			wp_enqueue_style( 'ctt' );
		}

		/**
		 * Shorten text length to 100 characters.
		 */
		public function shorten( $input, $length, $ellipses = true, $strip_html = true ) {
			if ( $strip_html ) {
				$input = strip_tags( $input );
			}
			if ( strlen( $input ) <= $length ) {
				return $input;
			}
			$last_space = strrpos( substr( $input, 0, $length ), ' ' );
			$trimmed_text = substr( $input, 0, $last_space );
			if ( $ellipses ) {
				$trimmed_text .= '...';
			}

			return $trimmed_text;
		}

		/**
		 * Replacement of Tweet tags with the correct HTML
		 */
		public function tweet( $matches ) {
			$handle = get_option( 'twitter-handle' );
			if ( !empty( $handle ) ) {
				$handle_code = "&via=" . $handle;
			} else {
				$handle_code = '';
			}
			$text = $matches[1];
			$short = $this->shorten( $text, 100 );

			return "<div style='clear:both'></div><div class='click-to-tweet'><div class='ctt-text'><a href='https://twitter.com/share?text=" . urlencode( $short ) . $handle_code . "&url=" . get_permalink() . "' target='_blank'>" . $short . "</a></div><a href='https://twitter.com/share?text=" . urlencode( $short ) . "" . $handle_code . "&url=" . get_permalink() . "' target='_blank' class='ctt-btn'>Click To Tweet</a><div class='ctt-tip'></div></div>";
		}

		/**
		 * Replacement of Tweet tags with the correct HTML for a rss feed
		 */
		public function tweet_feed( $matches ) {
			$handle = get_option( 'twitter-handle' );
			if ( !empty( $handle ) ) {
				$handle_code = "&via=" . $handle;
			} else {
				$handle_code = '';
			}
			$text = $matches[1];
			$short = $this->shorten( $text, 100 );

			return "<hr /><p><em>" . $short . "</em><br /><a href='https://twitter.com/share?text=" . urlencode( $short ) . $handle_code . "&url=" . get_permalink() . "' target='_blank'>Click To Tweet</a></p><hr />";
		}

		/**
		 * Regular expression to locate tweet tags
		 */
		public function replace_tags( $content ) {
			if ( !is_feed() ) {
				$content = preg_replace_callback( "/\[tweet \"(.*?)\"]/i", array( $this, 'tweet' ), $content );
			} else {
				$content = preg_replace_callback( "/\[tweet \"(.*?)\"]/i", array( $this, 'tweet_feed' ), $content );
			}

			return $content;
		}


		/**
		 * Shortcode handler for: ctt
		 */
		public function ctt_shortcode_handler( $atts ) {
			extract( shortcode_atts( array(
				'title' => 'default-title',
				'tweet' => 'default-tweet',
				'coverup' => 'default-coverup',
			), $atts ) );
			
			if ($title == 'default-title') { $title = $tweet; }

			return "<div style='clear:both'></div><div class='click-to-tweet'><div class='ctt-text'><a href='http://ctt.ec/{$coverup}' target='_blank'>" . $title . "</a></div><a href='http://ctt.ec/{$coverup}' target='_blank' class='ctt-btn'>Click To Tweet</a><div class='ctt-tip'></div></div>";
		}


		/**
		 * Cache bust tinymce
		 */
		public function refresh_mce( $ver ) {
			$ver += 3;

			return $ver;
		}

	} // End ctt class

	// Init Class
	new ctt();
}

?>