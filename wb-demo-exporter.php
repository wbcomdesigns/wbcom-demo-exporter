<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.wbcomdesigns.com
 * @since             1.0.0
 * @package           Wb_Demo_Exporter
 *
 * @wordpress-plugin
 * Plugin Name:       WB Demo Exporter
 * Plugin URI:        http://www.wbcomdesigns.com
 * Description:       This plugin allows the site administrataor to export the demo data which will then be installed by the installer plugin.
 * Version:           1.0.0
 * Author:            Wbcom Designs
 * Author URI:        http://www.wbcomdesigns.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wb-demo-exporter
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$wbde_upload = wp_upload_dir();

define( 'WBDE_PLUGIN_VERSION', '1.0.0' );

if ( ! defined( 'WBDE_PLUGIN_PATH' ) ) {
	define( 'WBDE_PLUGIN_PATH', plugin_dir_path(__FILE__) );
}

if ( ! defined( 'WBDE_TEXT_DOMAIN' ) ) {
	define( 'WBDE_TEXT_DOMAIN', 'wb-demo-exporter' );
}

if ( ! defined( 'WBDE_PLUGIN_URL' ) ) {
	define( 'WBDE_PLUGIN_URL', plugin_dir_url(__FILE__) );
}

if ( ! defined( 'WBDE_IS_BP_ACTIVE' ) ) {
	define( 'WBDE_IS_BP_ACTIVE', in_array( 'buddypress/bp-loader.php', get_option( 'active_plugins' ) ) );
}

if( WBDE_IS_BP_ACTIVE ) {
	$bp_active_components = get_option( 'bp-active-components', true );
	//Groups Component
	if( ! defined( 'WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE' ) ) {
		if ( ! array_key_exists( 'groups' ,$bp_active_components ) ) {
			define( 'WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE', false );
		} else {
			define( 'WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE', true );
		}
	}

	//Activity Component
	if( ! defined( 'WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE' ) ) {
		if ( ! array_key_exists( 'activity' ,$bp_active_components ) ) {
			define( 'WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE', false );
		} else {
			define( 'WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE', true );
		}
	}
}

if( ! defined( 'WBDE_UPLOADS_PATH' ) ) {
	$wbde_upload_dir = $wbde_upload['basedir'];
	$wbde_upload_dir = $wbde_upload_dir . '/wb-demo-exporter/';
	define( 'WBDE_UPLOADS_PATH', $wbde_upload_dir );
}

if( ! defined( 'WBDE_UPLOADS_URL' ) ) {
	$wbde_upload_dir_url = $wbde_upload['baseurl'];
	$wbde_upload_dir_url = $wbde_upload_dir_url . '/wb-demo-exporter/';
	define( 'WBDE_UPLOADS_URL', $wbde_upload_dir_url );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wb-demo-exporter-activator.php
 */
function activate_wb_demo_exporter() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wb-demo-exporter-activator.php';
	Wb_Demo_Exporter_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wb-demo-exporter-deactivator.php
 */
function deactivate_wb_demo_exporter() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wb-demo-exporter-deactivator.php';
	Wb_Demo_Exporter_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wb_demo_exporter' );
register_deactivation_hook( __FILE__, 'deactivate_wb_demo_exporter' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wb_demo_exporter() {
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-wb-demo-exporter.php';
	$plugin = new Wb_Demo_Exporter();
	$plugin->run();

}

/**
 * Actions performed on hook: plugins loaded.
 */
add_action( 'plugins_loaded', 'wbde_plugin_init' );
function wbde_plugin_init() {
	
	run_wb_demo_exporter();
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wbde_plugin_links' );
	
}

/**
 * Actions performed to generate plugin links
 */
function wbde_plugin_links( $links ) {
	$wbdi_links = array(
		'<a href="' . admin_url( 'options-general.php?page=wb-demo-exporter' ) . '">' . __( 'Settings', WBDE_TEXT_DOMAIN ) . '</a>',
		'<a href="https://wbcomdesigns.com/contact/" target="_blank" title="' . __( 'Go for any custom development.', WBDE_TEXT_DOMAIN ) . '">' . __( 'Support', WBDE_TEXT_DOMAIN ) . '</a>'
	);
	return array_merge( $links, $wbdi_links );
}