<?php
/**
 * Plugin Name: Wbcom Theme Demo Exporter
 * Plugin URI: https://wbcomdesigns.com/
 * Description: Wbcom Theme Demo Exporter
 * Version: 1.0.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Requires at least: 4.0
 * Tested up to: 4.9.6
 *
 * Text Domain: wbcom-theme-demo-exporter
 * Domain Path: /i18n/languages/
 *
 * @package WBCOM_Theme_Demo_Exporter
 * @category Core
 * @author Wbcom Designs
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
