<?php
/**
 * Plugin Name: WBCOM_Theme_Demo_Exporter
 * Plugin URI: http://WBCOM.com/
 * Description: Wordpress extension to remove plugin-update link from plugin listing page. Helpful to avoid comitting mistake in case you don't want to update any plugin.
 * Version: 1.0.0
 * Author: WBCOM
 * Author URI: http://WBCOM.com/
 * Requires at least: 4.0
 * Tested up to: 4.7
 *
 * Text Domain: wbcom-theme-demo-exporter
 * Domain Path: /i18n/languages/
 *
 * @package WBCOM_Theme_Demo_Exporter
 * @category Core
 * @author WBCOM
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WBCOM_Theme_Demo_Exporter' ) ) :

/**
 * Main WBCOM_Theme_Demo_Exporter Class.
 *
 * @class WBCOM_Theme_Demo_Exporter
 * @version	1.0.0
 */
class WBCOM_Theme_Demo_Exporter {

	/**
	 * WBCOM_Theme_Demo_Exporter version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';
	
	/**
	 * The single instance of the class.
	 *
	 * @var WBCOM_Theme_Demo_Exporter
	 * @since 1.0.0
	 */
	protected static $_instance = null;
	
	/**
	 * Main WBCOM_Theme_Demo_Exporter Instance.
	 *
	 * Ensures only one instance of WBCOM_Theme_Demo_Exporter is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see INSTANTIATE_WBCOM_Theme_Demo_Exporter()
	 * @return WBCOM_Theme_Demo_Exporter - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	/**
	 * WBCOM_Theme_Demo_Exporter Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'wbcom_theme_demo_exporter_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 * @since  1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'plugin_action_links_'.WBCOM_Theme_Demo_Exporter_PLUGIN_BASENAME, array( $this, 'alter_plugin_action_links' ) );
	}

	function alter_plugin_action_links( $plugin_links ) {
		$settings_link = '<a href="admin.php?page=wbcom-theme-demo-exporter">Settings</a>';
		array_unshift( $plugin_links, $settings_link );
		return $plugin_links;
	}

	/**
	 * Define WBCOM_Theme_Demo_Exporter Constants.
	 */
	private function define_constants() {
		$this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_FILE', __FILE__ );
		$this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WBCOM_Theme_Demo_Exporter_VERSION', $this->version );
		$this->define( 'WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN', 'wbcom-theme-demo-exporter' );
		$this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		include_once 'core/admin-settings.php';
		include_once 'core/importer-request-handler.php';
		include_once 'core/generate-demo-data.php';
	}

	/**
	 * Load Localisation files.
	 */
	public function load_plugin_textdomain() {
// 		$option_value = 'a:2:{s:11:\"reign_pages\";a:3:{s:16:\"reign_login_page\";s:2:\"38\";s:19:\"reign_register_page\";s:2:\"-1\";s:14:\"reign_404_page\";s:2:\"59\";}s:19:\"reign_buddyextender\";a:10:{s:24:\"avatar_thumb_size_select\";s:2:\"75\";s:23:\"avatar_full_size_select\";s:3:\"150\";s:22:\"avatar_max_size_select\";s:3:\"960\";s:20:\"avatar_default_image\";s:67:\"{{*home_url}}\/wp-content\/uploads\/2017\/10\/home_illo.png\";s:23:\"avatar_default_image_id\";s:2:\"58\";s:24:\"group_auto_join_checkbox\";s:2:\"on\";s:29:\"default_group_cover_image_url\";s:67:\"{{*home_url}}\/wp-content\/uploads\/2017\/10\/home_illo.png\";s:30:\"default_group_cover_image_size\";s:8:\"1024x800\";s:32:\"default_xprofile_cover_image_url\";s:72:\"{{*home_url}}\/wp-content\/uploads\/2017\/10\/WordPress-Logo.png\";s:33:\"default_xprofile_cover_image_size\";s:8:\"1024x500\";}}';

// 		$option_value = stripslashes( $option_value );
// 		print_r($option_value);
// 		$option_value = maybe_unserialize( $option_value );
// 		print_r($option_value);


// 		// $option_value = unserialize( $option_value );
// 		// var_export($option_value);
// //$value = untrailingslashit( $value );

// 		// $v = get_option( 'sdsdsdsdsd', $option_value );
// 		// print_r($option_value);
// 		// $option_value = json_decode( $option_value, true );
// 		// $option_value = maybe_unserialize( $option_value );
// 		// print_r($option_value);
// 		die("COOL");
		$locale = apply_filters( 'wbcom_theme_demo_exporter_plugin_locale', get_locale(), WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN );
		load_textdomain( WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN, WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH .'language/'.WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN.'-' . $locale . '.mo' );
		load_plugin_textdomain( WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/language' );
	}

}

endif;

/**
 * Main instance of WBCOM_Theme_Demo_Exporter.
 *
 * Returns the main instance of WBCOM_Theme_Demo_Exporter to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return WBCOM_Theme_Demo_Exporter
 */
function instantiate_wbcom_theme_demo_exporter() {
	return WBCOM_Theme_Demo_Exporter::instance();
}

// Global for backwards compatibility.
$GLOBALS['wbcom_theme_demo_exporter'] = instantiate_wbcom_theme_demo_exporter();
?>