<?php
/**
 * Plugin Name:       FBV — Favourite Bible Verses
 * Plugin URI:        https://github.com/aparatofan/fbv
 * Description:        Curated, bilingual (PL/EN) collection of favourite Bible verses from the New World Translation, displayed as responsive, searchable, tag-filterable cards with a frontend admin panel and semi-automatic text extraction from jw.org.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Mariusz Mirecki
 * Author URI:        https://mariuszmirecki.pl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fbv
 *
 * @package FBV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FBV_VERSION', '1.0.0' );
define( 'FBV_PLUGIN_FILE', __FILE__ );
define( 'FBV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'FBV_MIN_PHP', '8.0' );
define( 'FBV_MIN_WP', '6.0' );

/**
 * Check that the environment meets the plugin's minimum requirements.
 *
 * @return string Empty string if all good, otherwise a human-readable reason.
 */
function fbv_requirements_error() {
	if ( version_compare( PHP_VERSION, FBV_MIN_PHP, '<' ) ) {
		return sprintf(
			/* translators: 1: required PHP version, 2: current PHP version. */
			__( 'FBV requires PHP %1$s or higher. This server is running PHP %2$s.', 'fbv' ),
			FBV_MIN_PHP,
			PHP_VERSION
		);
	}

	if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], FBV_MIN_WP, '<' ) ) {
		return sprintf(
			/* translators: 1: required WordPress version, 2: current WordPress version. */
			__( 'FBV requires WordPress %1$s or higher. This site is running WordPress %2$s.', 'fbv' ),
			FBV_MIN_WP,
			$GLOBALS['wp_version']
		);
	}

	return '';
}

$fbv_requirements_error = fbv_requirements_error();
if ( '' !== $fbv_requirements_error ) {
	// Show an admin notice instead of loading the plugin (which would fatal).
	add_action(
		'admin_notices',
		function () use ( $fbv_requirements_error ) {
			echo '<div class="notice notice-error"><p><strong>FBV — Favourite Bible Verses:</strong> '
				. esc_html( $fbv_requirements_error ) . '</p></div>';
		}
	);

	// Fail activation cleanly with a readable message rather than a fatal error.
	register_activation_hook(
		__FILE__,
		function () use ( $fbv_requirements_error ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html( $fbv_requirements_error ),
				esc_html__( 'Plugin activation error', 'fbv' ),
				array( 'back_link' => true )
			);
		}
	);

	// Stop loading the rest of the plugin.
	return;
}

require_once FBV_PLUGIN_DIR . 'includes/class-fbv-bible-books.php';
require_once FBV_PLUGIN_DIR . 'includes/class-fbv-post-type.php';
require_once FBV_PLUGIN_DIR . 'includes/class-fbv-parser.php';
require_once FBV_PLUGIN_DIR . 'includes/class-fbv-rest-api.php';
require_once FBV_PLUGIN_DIR . 'includes/class-fbv-shortcode.php';
require_once FBV_PLUGIN_DIR . 'includes/class-fbv-importer.php';

/**
 * Main plugin bootstrap.
 */
final class FBV_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var FBV_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return FBV_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up hooks.
	 */
	private function __construct() {
		add_action( 'init', array( 'FBV_Post_Type', 'register' ) );

		$rest_api = new FBV_REST_API();
		add_action( 'rest_api_init', array( $rest_api, 'register_routes' ) );

		$shortcode = new FBV_Shortcode();
		add_shortcode( 'fbv', array( $shortcode, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $shortcode, 'maybe_enqueue_assets' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'fbv', 'FBV_Importer' );
		}
	}
}

FBV_Plugin::instance();

/**
 * Activation: register the CPT/taxonomy then flush rewrite rules.
 */
function fbv_activate() {
	FBV_Post_Type::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'fbv_activate' );

/**
 * Deactivation: flush rewrite rules.
 */
function fbv_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'fbv_deactivate' );
